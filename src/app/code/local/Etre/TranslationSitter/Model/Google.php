<?php

class Etre_TranslationSitter_Model_Google
{
    const CODE_INTEGRITY_CONSTRAINT = 23000;
    /** @var  string $apiKey */
    protected $apiKey;
    protected $sourceLanguage = "en";
    protected $locale;
    protected $storeId;
    protected $apiBaseUrl = "https://www.googleapis.com/language/translate/v2";

    public function __construct()
    {
        $this->setLocale(Mage::app()->getLocale()->getLocaleCode());
        $this->setStoreId(Mage::app()->getStore()->getId());
        $this->setApiKey(Mage::getStoreConfig('system/translationsitter/googleApiKey'));
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function process($text, $code)
    {
        if ($this->logAreaEnabled()):
            if ($this->autoGoogleTranslateEnabled()):
                return $this->getTranslation($text, $code);
            else:
                $this->logTranslation($code, $text);
            endif;

            return $text;
        else:
            return $text;
        endif;
    }

    public function logAreaEnabled()
    {
        $isAdmin = Mage::app()->getStore()->isAdmin();
        $isFrontend = !$isAdmin;
        $helper = Mage::helper("etre_translationsitter");
        if ($isAdmin && $helper->getLogAdminEnabled()) return true;
        if ($isFrontend && $helper->getLogFrontendEnabled()) return true;
        return false;
    }

    public function autoGoogleTranslateEnabled()
    {
        $isAdmin = Mage::app()->getStore()->isAdmin();
        $isFrontend = !$isAdmin;
        $helper = Mage::helper("etre_translationsitter");
        if ($isAdmin && $helper->getAutoTranslateAdminEnabled()) return true;
        if ($isFrontend && $helper->getAutoTranslateFrontEnabled()) return true;
        return false;
    }

    public function getTranslation($text, $code)
    {
        try {
            $translated = $this->getApiTranslation($text, $code);

            if (!empty($translated)):
                $this->logTranslation($code, $translated, "Translation Sitter: Google API");
            else:
                throw new Exception($this->__("There was a problem getting the translation from Google: {$translated}"));
            endif;
        } catch (Exception $exception) {
            if (!$this->integirtyConstraintViolatio($exception)):
                Mage::logException($exception);
            endif;
            $translated = $text;
        }
        return $translated;
    }

    /**
     * @param string $text
     * @return string
     */
    protected function getApiTranslation($text = "")
    {
        if(empty($text)) return "";

        $stringToTranslate = urlencode($text);
        $destinationLanguage = strtok($this->getLocale(), "_");
        $request = new Zend_Http_Client();
        $uri = "{$this->apiBaseUrl}?key={$this->apiKey}&q={$stringToTranslate}&source={$this->sourceLanguage}&target={$destinationLanguage}";
        $request
            ->setUri($uri)
            ->request("GET");
        $googleResponse = $request->getLastResponse()->getBody();
        $googleTranslation = json_decode($googleResponse);
        if (is_object($googleTranslation)):
            if (!$googleTranslation->error):
                return $translated = $googleTranslation->data->translations[0]->translatedText;
            endif;
        endif;

        return "";
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param $string
     * @param $source
     * @param $translated
     */
    protected function logTranslation($string, $translated, $source = "Translation Sitter Log")
    {
        $translationSitter = Mage::getModel("etre_translationsitter/translations");
        $translationSitter->setString($string);
        $translationSitter->setTranslationsitterSource($source);
        $translationSitter->setStoreId($this->getStoreId());
        $translationSitter->setTranslate($translated);
        $translationSitter->setLocale($this->getLocale());
        $translationSitter->setCrcString(crc32($string));
        try {
            $translationSitter->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        /*$coreResource = Mage::getSingleton("core/resource");
        $write = $coreResource->getConnection("core_write");
        $query = "insert into {$coreResource->getTableName("core/translate")} "
            . "(string, store_id, translate, locale, crc_string, is_from_translationsitter) values "
            . "(:string, :store_id, :translate, :locale, :crc_string, 1)";

        $binds = [
            'string'     => $string,
            'store_id'   => $this->getStoreId(),
            'translate'  => $translated,
            'locale'     => $this->getLocale(),
            'crc_string' => crc32($string),
        ];
        $write->query($query, $binds);*/
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param mixed $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @param $exception
     * @return bool
     */
    protected function integirtyConstraintViolatio($exception): bool
    {
        return $exception->getCode() !== self::CODE_INTEGRITY_CONSTRAINT;
    }

}