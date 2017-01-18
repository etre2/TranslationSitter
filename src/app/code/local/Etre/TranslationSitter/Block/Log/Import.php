<?php

/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 11/5/16
 * Time: 1:27 PM
 */
class Etre_TranslationSitter_Block_Log_Import extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        ini_set("display_errors",1);
        // $this->_objectId = 'id';
        parent::__construct();
        $this->_blockGroup = 'etre_translationsitter';
        $this->_controller = 'log';
        $this->_mode = 'import';
        $modelTitle = $this->_getModelTitle();
        $this->_removeButton('save');
        $this->_removeButton('reset');
        $this->_addButton('save', array(
            'label'     => $this->_getHelper()->__("Import {$modelTitle}s"),
            'onclick'   => 'importForm.submit();',
            'class'     => 'save',
        ), 1);

    }

    protected function _getModelTitle()
    {
        return 'Translation';
    }

    protected function _getHelper()
    {
        return Mage::helper('etre_translationsitter');
    }

    public function getHeaderText()
    {
        return $this->_getHelper()->__("Upload {$this->_getModelTitle()}s");
    }

    protected function _getModel()
    {
        return Mage::registry('current_model');
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/index');
    }

    /**
     * Get form save URL
     *
     * @deprecated
     * @see getFormActionUrl()
     * @return string
     */
    public function getSaveUrl()
    {
        $this->setData('form_action_url', 'save');
        return $this->getFormActionUrl();
    }
}
