<?php

/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 11/5/16
 * Time: 1:27 PM
 */
class Etre_TranslationSitter_Block_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

        $this->setId('translationGrid11111112');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //$this->setVarNameFilter('product_filter');
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }

    /**
     * Ensure AJAX Uses Grid URL
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('etre_translationsitter/translations')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('key_id',
            [
                'header' => $this->__('Key ID'),
                'width'  => '50px',
                'index'  => 'key_id',
            ]
        );

        $this->addColumn('store_id',
            [
                'header'  => $this->__('Store ID'),
                'width'   => '50px',
                'width'   => '70px',
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
            ]
        );

        $this->addColumn('translationsitter_source',
            [
                'header' => $this->__('Source'),
                'width'  => '50px',
                'type'    => 'options',
                'options' => Mage::getModel('etre_translationsitter/translations')->getCollection()->toOptionHashSource(),
                'index'  => 'translationsitter_source',
            ]
        );

        $this->addColumn('locale',
            [
                'header' => $this->__('Locale'),
                'width'  => '50px',
                'index'  => 'locale',
            ]
        );

        $this->addColumn('string',
            [
                'header' => $this->__('String'),
                'width'  => '200px',
                'index'  => 'string',
            ]
        );

        $this->addColumn('translate',
            [
                'header' => $this->__('Translation'),
                'width'  => '200px',
                'index'  => 'translate',
            ]
        );

        $this->addExportType('*/*/exportCsv', $this->__('CSV'));

        $this->addExportType('*/*/exportExcel', $this->__('Excel XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $translateResource = Mage::getModel('etre_translationsitter/translations')->getResource();
        $modelPk = $translateResource->getIdFieldName();
        $this->setMassactionIdField($modelPk);
        $this->getMassactionBlock()->setFormFieldName('ids');
        // $this->getMassactionBlock()->setUseSelectAll(false);
        $this->getMassactionBlock()->addItem('delete', [
            'label' => $this->__('Delete'),
            'url'   => $this->getUrl('*/*/massDelete'),
        ]);
        return $this;
    }

    /**
     * Retrieve Headers row array for Export
     *
     * @return array
     */
    protected function _getExportHeaders()
    {
        $row = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $row[] = $column->getIndex();
            }
        }

        return $row;
    }

    /**
     * Write item data to csv export file
     *
     * @param Varien_Object $item
     * @param Varien_Io_File $adapter
     */
    protected function _exportCsvItem(Varien_Object $item, Varien_Io_File $adapter)
    {
        $row = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                if(($column->getIndex()=='store_id')){
                    $row[] = $item->getStoreId();
                }else {
                    $row[] = $column->getRowFieldExport($item);
                }
            }
        }

        $adapter->streamWriteCsv(
            Mage::helper("core")->getEscapedCSVData($row)
        );
    }
}
