<?php

/**
 * Class Etre_TranslationSitter_Adminhtml_Translation_LogController
 */
class Etre_TranslationSitter_Adminhtml_Translation_LogController extends Mage_Adminhtml_Controller_Action
{
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
    protected $EXPORT_FILE_NAME = 'captured_missing_translations';

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
        //$this->_setActiLOG_GRIDveMenu('sales/sales');
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
        $id = $this->getRequest()->getParam('id');
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
        $permission = 'admin/system/etre_translationsitter/write';
        $this->userCanWriteToLog = Mage::getSingleton('admin/session')->isAllowed($permission);
        if (!$this->userCanWriteToLog) {
            return $this->returnAccessDenied();
        }
        $isPosting = !empty($this->getRequest()->getPost());
        if ($isPosting) {
            $this->processImport();
            Mage::getSingleton('adminhtml/session')->addSuccess("Translations imported successfully.");
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
     *
     */
    protected function processImport()
    {
        if ($this->uploadedFileHasProperName($inputFileId = 'translationsitter_source')) {
            try {
                ini_set("display_errors", 1);
                $path = Mage::getBaseDir('var') . DS . 'etre_imported_translations' . DS;  //desitnation directory
                /** Make the import directory if it doesn't exist */
                $fname = strtotime('now') . '_' . $_FILES[$inputFileId]['name']; //file name
                $uploader = new Varien_File_Uploader($inputFileId); //load class
                $uploader->setAllowedExtensions(array('csv')); //Allowed extension for file
                $uploader->setAllowCreateFolders(true); //for creating the directory if not exists
                $uploader->setAllowRenameFiles(true); //if true, uploaded file's name will be changed, if file with the same name already exists directory.
                $uploader->setFilesDispersion(false);
                if ($uploader->save($path, $fname)) {
                    $filePath = $path . $fname;
                    $file = $this->loadFileToExcel($inputFileId, $filePath);
                    $translationModel = Mage::getSingleton('etre_translationsitter/translations');
                    $translationResource = $translationModel->getResource();
                    $coreWriteConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $translationTable = $translationResource->getMainTable();
                    $fileDataRows = $this->excelToArrayWithHeaders($file);
                    //Load multiple models at once
                    $key_ids = [];
                    foreach($fileDataRows as $key => $rowData){
                        $key_ids[]=$rowData['key_id'];
                    }
                    /** @var /Etre_TranslationSitter_Model_Resource_Translations_Collection $translationCollection */
                    $translationCollection = Mage::getModel('etre_translationsitter/translations')->getCollection();
                    $translationCollection->addFieldToFilter('key_id', array('in' => $key_ids));

                    //Update multiple models at once
                    $transaction = Mage::getModel('core/resource_transaction');
                    foreach($translationCollection as $translationModel){
                        $fileRowData = $this->arrayKeyValueSearch($fileDataRows, 'key_id', $translationModel->getKeyId());
                        if(empty($fileRowData)) continue;
                        //dump("Before",$translationModel,$fileRowData);
                        $fileRowData = array_shift($fileRowData);
                        $translationModel->setTranslate($fileRowData['translation']);
                        $translationModel->setLocale($fileRowData['locale']);
                        $translationModel->setTranslationsitterSource($fileRowData['source']);
                        //dd("after", $translationModel);
                        $transaction->addObject($translationModel);
                    }
                    $transaction->save();
                }
            } catch (Exception $e) {
                echo 'Error Message: ' . $e->getMessage();
            }
        }
    }

    /**
     * @param string $inputFileId
     * @return bool
     */
    protected function uploadedFileHasProperName($inputFileId = "")
    {
        return isset($_FILES[$inputFileId]['name']) && $_FILES[$inputFileId]['name'] != '';
    }

    /**
     * @param $inputFileId
     * @param $filePath
     * @return PHPExcel
     */
    protected function loadFileToExcel($inputFileId, $filePath)
    {
        if ($_FILES[$inputFileId]['type'] == 'text/csv') {
            $csvReader = new PHPExcel_Reader_CSV();
            $csvReader->setDelimiter(',');
            $csvReader->setEnclosure('');

            $file = $csvReader->load($filePath);
            return $file;
        } else {
            $file = PHPExcel_IOFactory::load($filePath);
            return $file;
        }
    }

    /**
     * @param $file
     * @return array
     */
    protected function excelToArrayWithHeaders($file)
    {
        $objWorksheet = $file->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        /** Assume KEY_ID is in column one */
        $key_idColumn = 'A1:A' . $highestRow;
        /** Set format for key_id column so no decimals are used */
        $objWorksheet->getStyle($key_idColumn)->getNumberFormat($key_idColumn)->setFormatCode('#');
        $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true);
        $headingsArray = $headingsArray[1];
        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, true);
            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                ++$r;
                foreach ($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][strtolower($columnHeading)] = $dataRow[$row][$columnKey];
                }
            }
        }
        return $namedDataArray;
    }

    /**
     *
     */
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
                    Mage::helper('')->__('An error occurred while mass deleting items. Please review log and try again.')
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
            case "xml":
                return $this->exportXml();
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
    public function exportXml()
    {
        $fileName = $this->EXPORT_FILE_NAME . '.xml';
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
    protected function redirectReferrerWithError($message)
    {
        Mage::getSingleton("adminhtml/session")->addError($message);
        return $this->_redirectReferer();
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function exportCsv()
    {
        $fileName = $this->EXPORT_FILE_NAME . '.csv';
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
        dd("here");
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/etre_translationsitter');
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    protected function arrayKeyValueSearch($array, $key, $value)
    {
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
}