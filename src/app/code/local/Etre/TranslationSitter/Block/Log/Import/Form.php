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
        /** @var Varien_Data_Form $form */
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


        $field =$fieldset->addField('store_id', 'select', array(
            'name'      => 'store_id',
            'label'     => Mage::helper('cms')->__('Store View'),
            'title'     => Mage::helper('cms')->__('Store View'),
            'required'  => true,
            'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
        ));
        $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');

        $field->setRenderer($renderer);

        $fieldset->addField('unset_module_translation', 'select',[
           'name' => 'unset_module_translation',
           'label' => 'Remove module matches?',
           'value'  => '0',
           'values' => array(
               '-1'=>'Please Select..',
               '1' => array(
                   'value'=> '1',
                   'label' => 'Yes'
               ),
               '2' => array(
                   'value'=> '0',
                   'label' => 'No'
               ),

           ),
           'after_element_html' => $this->__('<p class="nm"><small>If translation detected without module prefix (i.e. <em><u><strong>Module_Namespace::</strong></u>Translation string</em>), replace the module-specific translations. </small></p>'),
           'class'     => 'required-entry',
           'required'  => true,
        ]);

        $fieldset->addField('translation_data', 'file', [
            'name'     => 'translationsitter_source',
            'label'    => $this->_getHelper()->__('Import Source'),
            'after_element_html' => '<p class="nm"><small>Compatible with Excel documents.</small></p>',
            'class'     => 'required-entry',
            'required'  => true,
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
