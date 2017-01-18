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
                ini_set("display_errors",1);
                $path = Mage::getBaseDir('var') . DS . 'etre_imported_translations' . DS;  //desitnation directory
                /** Make the import directory if it doesn't exist */
                $fname = strtotime('now') . '_' . $_FILES[$inputFileId]['name']; //file name
                $uploader = new Varien_File_Uploader($inputFileId); //load class
                $uploader->setAllowedExtensions(array('csv')); //Allowed extension for file
                $uploader->setAllowCreateFolders(true); //for creating the directory if not exists
                $uploader->setAllowRenameFiles(true); //if true, uploaded file's name will be changed, if file with the same name already exists directory.
                $uploader->setFilesDispersion(false);
                if ($uploader->save($path, $fname)) {
                    $file = PHPExcel_IOFactory::load($path.$fname);
                    $activeSheet = $file->getActiveSheet();
                    dd($activeSheet->toArray());
                    $csv = new Varien_File_Csv();
                    $csv->getData($path.$fname);
                    dd($csv);
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
}