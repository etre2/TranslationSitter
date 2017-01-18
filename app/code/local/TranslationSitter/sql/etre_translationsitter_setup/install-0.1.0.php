<?php
/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 10/3/16
 * Time: 12:56 PM
 */
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$coreTranslateTable = $installer->getTable("core/translate");
$installerConnection = $installer->getConnection();

$installerConnection
    ->addColumn($coreTranslateTable, 'is_from_translationsitter', [
        'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'nullable' => false,
        'default'  => 0,
        'length'   => 1,
        'after'    => null, // column name to insert new column after
        'comment'  => 'Translation provided by Etre_TranslationSitter',
    ])
    ->addColumn($coreTranslateTable, 'source', [
        'type'=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'default'   => "Magento",
        'nullable'=>false,
        'after'=>"is_from_translationsitter",
        'comment'=>''
    ]);


$installer->endSetup();