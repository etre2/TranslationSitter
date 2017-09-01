<?php

/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 11/5/16
 * Time: 1:27 PM
 */
class Etre_TranslationSitter_Block_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'etre_translationsitter';
        $this->_controller = 'log';
        $this->_headerText = $this->__('Translation Overrides');
        //$this->_addButtonLabel  = $this->__('Import');

        $this->addButton("import", [
            'label'   => $this->__('Import'),
            'onclick' => "setLocation('{$this->getImportUrl()}')",
            'class'   => $this->__('add'),
        ]);

        $this->addButton("export-csv", [
            'label'   => $this->__('Export CSV'),
            'onclick' => "setLocation('{$this->getExportUrlByFormat("csv")}')",
            'class'   => $this->__('export'),
        ]);

        $this->addButton("export-excel", [
            'label'   => $this->__('Export Excel'),
            'onclick' => "setLocation('{$this->getExportUrlByFormat("xlsx")}')",
            'class'   => $this->__('export'),
        ]);
        parent::__construct();
    }

    /**
     * @param $format string xml|csv
     * @return string
     */
    public function getExportUrlByFormat($format)
    {
        return $this->getUrl('*/*/export', ['format' => $format]);
    }

    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new');
    }
    public function getImportUrl()
    {
        return $this->getUrl('*/*/import');
    }


}

