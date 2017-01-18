<?php

class Etre_TranslationSitter_Model_Resource_Translations_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('etre_translationsitter/translations');
    }
    /**
     * Convert items array to hash for select options
     *
     * @return array
     */
    public function toOptionHashSource()
    {
        return $this->_toOptionHash('translationsitter_source', 'translationsitter_source');
    }
}