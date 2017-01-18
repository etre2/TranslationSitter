<?php

/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 11/5/16
 * Time: 1:27 PM
 */
class Etre_TranslationSitter_Block_Log_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $model = $this->_getModel();
        $modelTitle = $this->_getModelTitle();
        $form = new Varien_Data_Form([
            'id'     => 'edit_form',
            'action' => $this->getUrl('*/*/save'),
            'method' => 'post',
        ]);

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => $this->_getHelper()->__("$modelTitle Information"),
            'class'  => 'fieldset-wide',
        ]);

        if($model && $model->getId()) {
            $modelPk = $model->getResource()->getIdFieldName();
            $fieldset->addField($modelPk, 'hidden', [
                'name' => $modelPk,
            ]);
        }
        if($model && $model->getId()):
            $moduleFromTranslation = $this->_getHelper()->getModuleFromTranslation($model->getString());
            if($moduleFromTranslation):
                $search = $moduleFromTranslation . '::';
                $replace = '';
                $translationString = str_replace($search, $replace, $model->getString());
                $labelValue = "<strong>{$translationString}</strong> in <em>{$model->getString()}</em>";
            else:
                $labelValue = $model->getString();
            endif;
            $fieldset->addField('translation_string', 'text', [
                'name'               => 'translation_string',
                'label'              => $this->_getHelper()->__("String to Translate"),
                'style'              => 'display:none',
                'disabled'           => true,
                'after_element_html' => $labelValue,
            ]);
        else:
            $fieldset->addField('string', 'text', [
                'name'  => 'string',
                'label' => $this->_getHelper()->__("String to Translate"),
                'value' => '',
            ]);
        endif;
        $fieldset->addField('translate', 'text', [
            'name'     => 'translate',
            'label'    => $this->_getHelper()->__('Translation'),
            'required' => true,
        ]);

        $fieldset->addField('locale', 'select', [
            'label'    => $this->_getHelper()->__('Translation Language'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'title',
            'onclick'  => "",
            'onchange' => "",
            'values'   => array_merge($options[] = ['label' => "Select a locale..."], Mage::getModel('adminhtml/system_config_source_locale')->toOptionArray()),
            'disabled' => false,
            'readonly' => false,
        ]);

        $fieldset->addField('is_module_specific', 'select', [
            'label'  => $this->_getHelper()->__('Is translation module specific?'),
            'name'   => 'is_module_specific',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')
                ->toOptionArray(),
        ]);

        $fieldset->addField('translation_module', 'select', [
            'label'    => $this->_getHelper()->__('Translation Module'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'translation_module',
            'onclick'  => "",
            'onchange' => "",
            'values'   => $this->_getHelper()->getInstalledModulesOptionArray(),
            'disabled' => false,
            'readonly' => false,
        ]);


        $fieldset->addField('is_store_view_specific', 'select', [
            'label'  => $this->_getHelper()->__('Is translation store view specific?'),
            'name'   => 'is_store_view_specific',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')
                ->toOptionArray(),
        ]);

        $fieldset->addField('store_id', 'select', [
            'label'    => $this->_getHelper()->__('Apply only to this store view'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'store_id',
            'onclick'  => "",
            'onchange' => "",
            'values'   => array_merge($options[] = ['label' => "Select a store..."], Mage::getModel('core/store')->getCollection()->toOptionHash()),
            'disabled' => false,
            'readonly' => false,
        ]);

        $fieldset->addField('translationsitter_source', 'text', [
            'name'     => 'translationsitter_source',
            'label'    => $this->_getHelper()->__('Translation Source'),
            'disabled' => true,
            'required' => false,
        ]);

        if($model) {
            $translationModule = $this->_getHelper()->getModuleFromTranslation($model->getString());
            $model->setTranslationModule($translationModule);
            $model->setIsModuleSpecific(boolval($translationModule));
            $model->setIsStoreViewSpecific(boolval($model->getStoreId()));
            $translationSource = $model->getTranslationsitterSource() == "" ? "Magento" : $model->getTranslationsitterSource();
            $model->setTranslationsitterSource($translationSource);
            $form->addValues($model->getData());
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        // Append dependency javascript
        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap('is_module_specific', 'is_module_specific')
            ->addFieldMap('is_store_view_specific', 'is_store_view_specific')
            ->addFieldMap('translation_module', 'translation_module')
            ->addFieldMap('store_id', 'store_id')
            ->addFieldDependence('translation_module', 'is_module_specific', 1)
            ->addFieldDependence('store_id', 'is_store_view_specific', 1)
        );

        return parent::_prepareForm();
    }

    protected function _getModel()
    {
        return Mage::registry('current_model');
    }

    protected function _getModelTitle()
    {
        return 'Logged Translations';
    }

    protected function _getHelper()
    {
        return Mage::helper('etre_translationsitter');
    }

}
