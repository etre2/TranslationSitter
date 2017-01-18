<?php

class Etre_TranslationSitter_Model_Resource_Translations extends Mage_Core_Model_Resource_Db_Abstract{

    protected function _construct()
    {
        $this->_init('etre_translationsitter/translations', 'key_id');
    }
}