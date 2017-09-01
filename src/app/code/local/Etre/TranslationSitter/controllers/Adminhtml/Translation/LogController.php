<?php

/**
 * Class Etre_TranslationSitter_Adminhtml_Translation_LogController
 */
class Etre_TranslationSitter_Adminhtml_Translation_LogController extends Mage_Adminhtml_Controller_Action
{
    const SQL_LIKE_MODULE = '%_%::';
    const ZEND_DB_BINDER = '?';
    protected $newTranslations;
    protected $updatedByKey = [];
    protected $hasError = false;
    /**
     * @var string
     */
    protected $BLOCK_TRANSLATION_GRID = 'etre_translationsitter/log_grid';

    /**
     * @var string
     */
    protected $BLOCK_TRANSlATION_GRID_CONTAINER = 'etre_translationsitter/log';
    /**
     * @var string
     */
    protected $BLOCK_TRANSLATION_EDIT = 'etre_translationsitter/log_edit';
    /**
     * @var string
     */
    protected $BLOCK_TRANSLATION_IMPORT = 'etre_translationsitter/log_import';

    /**
     * @var string
     */
    protected $EXPORT_FILE_NAME = 'absent_template_translations';

    /**
     * @var
     */
    protected $helper;

    /**
     * @var
     */
    protected $userCanWriteToLog;

    /**
     *
     */
    public function indexAction()
    {
        $this->_title($this->__('Log'))->_title($this->__('Translation Sitter'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock($this->BLOCK_TRANSlATION_GRID_CONTAINER));
        $this->renderLayout();
    }

    /**
     * New Action.
     * Forward to Edit Action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     *
     */
    public function editAction()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = Mage::getModel('etre_translationsitter/translations');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->_getSession()->addError(
                    Mage::helper('etre_translationsitter')->__('This translation no longer exists.')
                );
                $this->_redirect('*/*/');

                return;
            }
        }

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('current_model', $model);

        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock($this->BLOCK_TRANSLATION_EDIT));
        $this->renderLayout();
    }

    /**
     * @return Etre_TranslationSitter_Adminhtml_Translation_LogController
     */
    public function importAction()
    {
        $permission              = 'admin/system/etre_translationsitter/write';
        $this->userCanWriteToLog = Mage::getSingleton('admin/session')->isAllowed($permission);
        if (!$this->userCanWriteToLog) {
            return $this->returnAccessDenied();
        }
        $isPosting = !empty($this->getRequest()->getPost());
        if ($isPosting) {
            if ($this->isAdminStore()) {
                $this->processImport();
            } else {
                $this->addError(
                    (new Exception("Attempting to upload translations to Admin store ID is not allowd")),
                    "Please choose a store"
                );
            }
            $this->_redirect('*/*/index');
        } else {
            $this->loadLayout();
            $this->_addContent($this->getLayout()->createBlock($this->BLOCK_TRANSLATION_IMPORT));
            $this->renderLayout();
        }

    }

    /**
     * @return $this
     */
    protected function returnAccessDenied()
    {
        $this->_forward('denied');
        $this->setFlag('', self::FLAG_NO_DISPATCH, true);

        return $this;
    }

    /**
     * @return bool
     */
    protected function isAdminStore(): bool
    {
        return intval($this->getRequest()->getParam('store_id')) !== 0;
    }

    /**
     *
     */
    protected function processImport()
    {
        if ($this->validFileName($fileFieldName = 'translationsitter_source')) {

            try {
                $path = Mage::getBaseDir('var').DS.'etre_imported_translations'.DS;  //desitnation directory
                /** Make the import directory if it doesn't exist */

                $tmpFileName = strtotime('now').'_'.$_FILES[$fileFieldName]['name'];

                $uploader = $this->getUploader($fileFieldName);
                if ($uploader->save($path, $tmpFileName)) {

                    $filePath = $path.$uploader->getUploadedFileName();
                    $excelDoc = $this->loadFile($fileFieldName, $filePath);

                    $this->newTranslations = $this->excelToArrayWithHeaders($excelDoc);

                    $keyIds = $this->getKeyIds();

                    $this->updateByKey($keyIds);

                    $this->deleteOld($this->newTranslations, $keyIds);

                    $this->insertNew();

                    if (!$this->hasError) {
                        $this->_getSession()->addSuccess(
                            Mage::helper('etre_translationsitter')->__("Translations imported successfully")
                        );
                    }
                }
            } catch (Exception $exception) {
                $this->addError(
                    $exception,
                    "There was a problem processing your new translations. Please check the Magento log for more info."
                );
            }
        }

    }

    /**
     * @param string $inputFileId
     * @return bool
     */
    protected function validFileName($inputFileId = "")
    {
        return isset($_FILES[$inputFileId]['name']) && $_FILES[$inputFileId]['name'] != '';
    }

    /**
     * @param $fileFieldName
     * @return Varien_File_Uploader
     */
    protected function getUploader(
        $fileFieldName
    ): Varien_File_Uploader {
        $uploader = new Varien_File_Uploader($fileFieldName);
        $uploader->setAllowedExtensions(array('csv', 'xls', 'xlsx'));
        $uploader->setAllowCreateFolders(true);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        return $uploader;
    }

    /**
     * @param $inputFileId
     * @param $filePath
     * @return PHPExcel
     */
    protected function loadFile($inputFileId, $filePath)
    {
        if ($_FILES[$inputFileId]['type'] == 'text/csv') {
            $csvReader = new PHPExcel_Reader_CSV();
            $csvReader->setDelimiter(',');
            $csvReader->setEnclosure('');
            try {
                $file = $csvReader->load($filePath);
            } catch (Exception $exception) {
                $this->hasError = true;
                Mage::getSingleton('adminhtml/session')->addWarning($exception->getMessage());
            }

            return $file;
        } else {
            $file = PHPExcel_IOFactory::load($filePath);

            return $file;
        }
    }

    /**
     * @param PHPExcel $file
     * @return array
     */
    protected function excelToArrayWithHeaders(PHPExcel $file)
    {
        $worksheet     = $file->getActiveSheet();
        $highestRow    = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        /** Assume KEY_ID is in column one */
        $keyIdColumn   = 'A1:A'.$highestRow;
        $headingsArray = $worksheet->rangeToArray('A1:'.$highestColumn.'1', null, true, true, true);
        $headingsArray = $headingsArray[1];

        /** Set format for key_id column so no decimals are used */
        $keyIdColumnStyle = $this->getColumnStyle('key_id', $headingsArray, $highestRow, $worksheet);
        $keyIdColumnStyle->getNumberFormat($keyIdColumn)->setFormatCode('#');

        $sourceColumnId      = $this->getColumnLetter('source', $headingsArray);
        $localeColumnId      = $this->getColumnLetter('locale', $headingsArray);
        $translationColumnId = $this->getColumnLetter('translation', $headingsArray);

        $r              = -1;
        $namedDataArray = array();

        for ($row = 2; $row <= $highestRow; ++$row) {
            $sourceCell      = $worksheet->getCellByColumnAndRow($this->getColumnIndex($sourceColumnId), $row);
            $localeCell      = $worksheet->getCellByColumnAndRow($this->getColumnIndex($localeColumnId), $row);
            $translationCell = $worksheet->getCellByColumnAndRow($this->getColumnIndex($translationColumnId), $row);

            if (empty($sourceCell->getValue()) || empty($localeCell->getValue()) || empty(
                $translationCell->getValue()
                )) {
                continue;
            }

            $rowArray = $worksheet->rangeToArray('A'.$row.':'.$highestColumn.$row, '', false, false, true);

            /**
             * $rowArray takes cell string values of TRUE or FALSE and converts them into booleans.
             * $rowArray[$row][$sourceColumnId] AND PHPEXCEL_Cell->getValue() reference the same thing
             * but in two different formats.
             */

            $rowArray[$row][$sourceColumnId]      = $this->stringifyIfBoolean($rowArray[$row][$sourceColumnId]);
            $rowArray[$row][$translationColumnId] = $this->stringifyIfBoolean($rowArray[$row][$translationColumnId]);


            ++$r;
            foreach ($headingsArray as $columnKey => $columnHeading) {
                $namedDataArray[$r][strtolower($columnHeading)] = $rowArray[$row][$columnKey];
            }

        }

        return $namedDataArray;
    }

    /**
     * @param $headingsArray
     * @param $highestRow
     * @param $worksheet
     * @return PHPExcel_Style
     */
    protected function getColumnStyle(
        $columnName,
        $headingsArray,
        $highestRow,
        $worksheet
    ) {
        $sourceColumnId = $this->getColumnLetter($columnName, $headingsArray);
        $sourceColumn   = sprintf('%s1:%s%s', $sourceColumnId, $sourceColumnId, $highestRow);

        return $worksheet->getStyle($sourceColumn);
    }

    /**
     * @param $headingsArray
     * @return false|int|string
     */
    protected function getColumnLetter(
        $columnName,
        $headingsArray
    ) {
        $sourceColumnId = array_search($columnName, $headingsArray);

        return $sourceColumnId;
    }

    /**
     * @param $sourceColumnId
     * @return int
     */
    protected function getColumnIndex(
        $sourceColumnId
    ): int {
        return PHPExcel_Cell::columnIndexFromString($sourceColumnId) - 1;
    }

    /**
     * @param string
     */
    protected function stringifyIfBoolean($string)
    {
        if (is_bool($string) && $string) {
            $string = 'TRUE';
        } elseif (is_bool($string) && !$string) {
            $string = 'FALSE';
        }

        return $string;
    }

    /**
     * @return array
     */
    protected function getKeyIds(): array
    {
        $keyIds = [];
        foreach ($this->newTranslations as $key => $rowData) {
            if (!is_numeric($rowData['key_id'])) {
                continue;
            }
            $keyIds[] = $rowData['key_id'];
        }

        return $keyIds;
    }

    /**
     * @param $keyIds
     * return void;
     */
    protected function updateByKey(
        $keyIds
    ) {

        /** @var /Etre_TranslationSitter_Model_Resource_Translations_Collection $translationsByKey */
        $translationsByKey = Mage::getModel('etre_translationsitter/translations')
            ->getCollection()
            ->addFieldToFilter('key_id', array('in' => $keyIds));

        if ($translationsByKey->getSize() <= 0) {
            return;
        }

        $this->updateItemsByKey($translationsByKey);
    }

    /**
     * @param $translationCollection
     */
    protected function updateItemsByKey(
        $translationCollection
    ) {

        $transaction = Mage::getModel('core/resource_transaction');
        foreach ($translationCollection as $translationModel) {
            $fileRowData = $this->arrayKeyValueSearch($this->newTranslations, 'key_id', $translationModel->getKeyId());
            if (!empty($fileRowData)) {
                $fileRowData = array_shift($fileRowData);
                $translationModel->setTranslate($fileRowData['translation']);
                $translationModel->setLocale($fileRowData['locale']);
                $translationModel->setTranslationsitterSource($fileRowData['source']);
                $transaction->addObject($translationModel);

                $this->updatedByKey[] = $translationModel->getKeyId();
                continue;
            }
        }
        try {
            $transaction->save();
        } catch (Exception $exception) {
            $this->addError(
                $exception,
                "There was a problem inserting the new records. Please check Magento logs for more info."
            );
        }
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    protected function arrayKeyValueSearch(
        $array,
        $key,
        $value
    ) {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, $this->arrayKeyValueSearch($subarray, $key, $value));
            }
        }

        return $results;
    }

    /**
     * @param Exception $exception
     * @param $errorMessage
     */
    protected function addError(
        $exception,
        $errorMessage
    ) {
        $this->hasError = true;
        Mage::logException($exception);
        $this->_getSession()->addError(
            Mage::helper('etre_translationsitter')->__($errorMessage)
        );
    }

    /**
     * @param $newTranslations
     * @param $keyIds
     */
    protected function deleteOld($newTranslations, $keyIds)
    {
        foreach ($newTranslations as $updatedTranslation) {
            $updatedTranslation = (new Varien_Object())->setData($updatedTranslation);

            if (!$this->hasRequiredFields($updatedTranslation)) {
                continue;
            }

            $unboundSourceString = str_replace(self::ZEND_DB_BINDER, '_', $updatedTranslation->getSource());

            $stringLookup = [
                ['like' => $unboundSourceString],
            ];

            if ($this->replaceModuleTranslations()) {
                $moduleStringLookup = ['like' => $this->getLikeModuleString($unboundSourceString)];
                $stringLookup[]     = $moduleStringLookup;
            }

            /** @var Etre_TranslationSitter_Model_Resource_Translations_Collection $oldTranslations */
            $oldTranslations = Mage::getResourceModel('etre_translationsitter/translations_collection')
                ->addFieldToFilter('store_id', array('eq' => $this->getRequest()->getParam('store_id')))
                ->addFieldToFilter(
                    'string',
                    $stringLookup
                );

            if ($oldTranslations->getSize() > 0) {
                try {
                    foreach ($oldTranslations as $oldTranslation) {
                        if (!($updatedTranslation->getSource() === $this->stringWithoutModule($oldTranslation))) {
                            continue;
                        }
                        $oldTranslation->delete();
                    }
                } catch (Exception $exception) {
                    $this->addError($exception, "There was a problem deleting some old records.");
                }
            }
        }
    }

    /**
     * @param $updatedTranslation
     * @return bool
     */
    protected function hasRequiredFields(
        $updatedTranslation
    ): bool {
        return $updatedTranslation->getSource() && $updatedTranslation->getTranslation(
            ) && $updatedTranslation->getLocale();
    }

    /**
     * @return int
     */
    protected function replaceModuleTranslations(): int
    {
        return (int)$this->getRequest()->getParam('unset_module_translation');
    }

    /**
     * @param $sourceText
     * @return string
     */
    protected function getLikeModuleString(
        $sourceText
    ): string {
        return sprintf(
            '%s%s',
            self::SQL_LIKE_MODULE,
            $sourceText
        );
    }

    /**
     * @param $oldTranslation
     * @return mixed
     */
    protected function stringWithoutModule(
        $oldTranslation
    ) {
        return array_reverse(explode('::', $oldTranslation->getString(), 2))[0];
    }

    /**
     * @param $keyIds
     * @param $fileDataRows
     */
    protected function insertNew()
    {
        $updateable  = $this->getUpdatableTranslations();
        $updated     = [];
        $transaction = Mage::getModel('core/resource_transaction');
        foreach ($updateable as $updatedTranslation) {
            $sourceString = $updatedTranslation['source'];

            /** Avoid duplication errors */
            if (in_array($sourceString, $updated)) {
                continue;
            }

            $updated[] = $sourceString;

            $translation = Mage::getModel('etre_translationsitter/translations');
            $translation->setStoreId($this->getRequest()->getParam('store_id'));
            $translation->setString($sourceString);
            $translation->setTranslate($updatedTranslation['translation']);
            $translation->setLocale($updatedTranslation['locale']);
            $translation->setTranslationsitterSource(
                $updatedTranslation['translation_source'] ? $updatedTranslation['translation_source'] : 'Import'
            );
            $translation->setCrcString(crc32($sourceString));

            $transaction->addObject($translation);
        }
        try {
            $transaction->save();
        } catch (Exception $exception) {
            $this->addError($exception, "Skipping \"{$translation->getString()}\". This may be a duplicate entry");
        }

    }

    /**
     * @return array
     */
    protected function getUpdatableTranslations(): array
    {
        if (empty($this->updatedByKey)) {
            return $this->newTranslations;
        }

        $updatedKeys = $this->updatedByKey;
        $updateable  = array_filter(
            $this->newTranslations,
            function ($newTranslations) use ($updatedKeys) {
                return !in_array($newTranslations['obj_id'], $updatedKeys);
            }
        );

        return $updateable;
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            $translation = Mage::getSingleton('etre_translationsitter/translations')->load($id);
            $translation->delete();
            $this->_getSession()->addSuccess(Mage::helper('etre_translationsitter')->__('Translation deleted.'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError(
                Mage::helper('')->__('An error occurred while mass deleting items. Please review log and try again.')
            );
            Mage::logException($e);

            return;

        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('ids');
        if (!is_array($ids)) {
            $this->_getSession()->addError($this->__('Please select (s).'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = Mage::getSingleton('etre_translationsitter/translations')->load($id);
                    $model->delete();
                }

                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) have been deleted.', count($ids))
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('')->__(
                        'An error occurred while mass deleting items. Please review log and try again.'
                    )
                );
                Mage::logException($e);

                return;
            }
        }
        $this->_redirect('*/*/');

    }

    /**
     *
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock($this->BLOCK_TRANSLATION_GRID)->toHtml()
        );
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function exportAction()
    {
        switch ($this->getRequest()->getParam("format")):
            case "xlsx":
                return $this->exportXlsx();
                break;
            case "csv":
                return $this->exportCsv();
                break;
            default:
                return $this->redirectReferrerWithError($this->__("Invalid export format requested."));
        endswitch;
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function exportXlsx()
    {
        $fileName = $this->EXPORT_FILE_NAME.'.xml';
        if ($grid = $this->getLayout()->createBlock($this->BLOCK_TRANSLATION_GRID)):
            $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
        else:
            return $this->redirectReferrerWithError("Could not build grid for export.");
        endif;
    }

    /**
     * @param $message
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function redirectReferrerWithError(
        $message
    ) {
        Mage::getSingleton("adminhtml/session")->addError($message);

        return $this->_redirectReferer();
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function exportCsv()
    {
        $fileName = sprintf('%s-%s.csv', $this->EXPORT_FILE_NAME, Mage::getModel('core/date')->date('YmdHis'));

        /** @var Etre_TranslationSitter_Block_Log_Grid $grid */
        if ($grid = $this->getLayout()->createBlock($this->BLOCK_TRANSLATION_GRID)):
            $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
        else:
            return $this->redirectReferrerWithError("Invalid grid provided for download.");
        endif;
    }

    /**
     *
     */
    public function saveAction()
    {

        $request = $this->getRequest();
        $keyId   = $request->getParam('key_id');
        if (!empty($keyId)) {
            $translation = Mage::getModel('etre_translationsitter/translations')->load($keyId);
            if (!$translation->getData()) {
                return $this->redirectReferrerWithError(
                    Mage::helper('etre_translationsitter')->__(
                        "Model with id %s could not be loaded. It may have been removed.",
                        $keyId
                    )
                );
            }
        } else {
            $translation = Mage::getModel('etre_translationsitter/translations');
        }
        if (!$request->getParam('translate')) {
            return $this->redirectReferrerWithError(
                Mage::helper('etre_translationsitter')->__("Translation string cannot be empty.")
            );
        } else {
            $translation->setTranslate($request->getParam('translate'));
        }
        if (!$request->getParam('store_id')) {
            return $this->redirectReferrerWithError(
                Mage::helper('etre_translationsitter')->__("Core Translations require that a store be selected.")
            );
        } else {
            $store = Mage::getModel('core/store')->load($request->getParam('store_id'));
            if (!$store->getId()) {
                return $this->redirectReferrerWithError(
                    Mage::helper('etre_translationsitter')->__("Selected store no longer exists.")
                );
            }
            $translation->setStoreId($request->getParam('store_id'));
        }
        if (!$request->getParam('locale')) {
            return $this->redirectReferrerWithError(
                Mage::helper('etre_translationsitter')->__("Translation language required.")
            );
        } else {
            $translation->setLocale($request->getParam('locale'));
        }
        $isModuleSpecific = $request->getParam('is_module_specific') == 1;
        if ($isModuleSpecific && $request->getParam('translation_module')) {
            $stringToTranslate = $request->getParam('string') ? $request->getParam('string') : $translation->getString(
            );
            $sourceTranslation = Mage::helper('etre_translationsitter')->getStringToTranslateFromSourceTranslation(
                $stringToTranslate
            );
            $translation->setString($request->getParam('translation_module').'::'.$sourceTranslation);
        } elseif ($request->getParam('is_module_specific') == 0) {
            $stringToTranslate = $request->getParam('string') ? $request->getParam('string') : $translation->getString(
            );
            $translation->setString($stringToTranslate);
        }
        try {
            $userInfo = Mage::getSingleton('admin/session')->getUser()->getUsername()." (ID:".Mage::getSingleton(
                    'admin/session'
                )->getUser()->getUserId().")";
            $translation->setTranslationsitterSource(
                Mage::helper('etre_translationsitter')->__('Modified By %s', $userInfo)
            );
            $translation->save();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('etre_translationsitter')->__('Translation updated')
            );
            if ($request->getParam('back')) {
                return $this->_redirect('*/*/edit/', ['id' => $translation->getId()]);
            }

            return $this->_redirect('*/*/');
        } catch (Exception $e) {
            return $this->redirectReferrerWithError(
                Mage::helper('etre_translationsitter')->__(
                    'There was a problem saving the translation: %s',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/etre_translationsitter');
    }
}