<?php

/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 11/5/16
 * Time: 1:27 PM
 */
class Etre_TranslationSitter_Block_Log_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $modelTitle = $this->_getModelTitle();
        $form = new Varien_Data_Form([
            'id'     => 'importForm',
            'action' => $this->getUrl('*/*/import'),
            'method' => 'post',
            'enctype' => 'multipart/form-data',
        ]);

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => $this->_getHelper()->__("$modelTitle Data"),
            'class'  => 'fieldset-wide',
        ]);

        $fieldset->addField('translation_data', 'file', [
            'name'     => 'translationsitter_source',
            'label'    => $this->_getHelper()->__('Import Source'),
            'after_element_html' => '<p class="nm"><small>Compatible with Excel documents.</small></p>',
            'required' => false,
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getModel()
    {
        return Mage::registry('current_model');
    }

    protected function _getModelTitle()
    {
        return 'Translation Import';
    }

    protected function _getHelper()
    {
        return Mage::helper('etre_translationsitter');
    }

}
