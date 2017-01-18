<?php

class Etre_TranslationSitter_Model_Rewrite_Core_Translate extends Mage_Core_Model_Translate
{
    /**
     * Return translated string from text.
     *
     * @param string $text
     * @param string $code
     * @return string
     */
    protected function _getTranslatedString($text, $code)
    {
        $translated = '';
        if(array_key_exists($code, $this->getData())) {
            $translated = $this->_data[$code];
        } elseif(array_key_exists($text, $this->getData())) {
            $translated = $this->_data[$text];
        } else {
            /** @var Etre_TranslationSitter_Model_Google $translated */
            $translated = Mage::getModel("etre_translationsitter/google")
                ->process($text,$code);
                //->getTranslation();
        }
        return $translated;
    }
}