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
    ->addColumn($coreTranslateTable, 'translationsitter_source', [
        'type'=> Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'=>false,
        'after'=>"is_from_translationsitter",
        'comment'=>'Where did this translation come from?'
    ]);


$installer->endSetup();