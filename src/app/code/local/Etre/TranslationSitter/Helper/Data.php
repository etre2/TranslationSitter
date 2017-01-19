<?php

/**
 * Created by PhpStorm.
 * User: tyler
 * Date: 10/3/16
 * Time: 12:56 PM
 */
class Etre_TranslationSitter_Helper_Data extends Mage_Core_Helper_Abstract
{

    protected $moduleIsEnabled;
    protected $logFrontendEnabled;
    protected $autoTranslateFrontEnabled;
    protected $logAdminEnabled;
    protected $autoTranslateAdminEnabled;

    public function __construct()
    {
        $this->setModuleIsEnabled(Mage::getStoreConfig("system/translationsitter/isEnabled") && $this->isModuleEnabled("Etre_TranslationSitter"));
        $this->setLogFrontendEnabled(Mage::getStoreConfig("system/translationsitter/isFrontendLogEnabled") && $this->getModuleIsEnabled());
        $this->setLogAdminEnabled(Mage::getStoreConfig("system/translationsitter/isAdminLogEnabled") && $this->getModuleIsEnabled());
        $this->setAutoTranslateFrontEnabled(Mage::getStoreConfig("system/translationsitter/isFrontendAutoTranslateEnabled") && $this->getLogFrontendEnabled());
        $this->setAutoTranslateAdminEnabled(Mage::getStoreConfig("system/translationsitter/isAdminAutoTranslateEnabled") && $this->getLogAdminEnabled());
    }

    /**
     * @return mixed
     */
    public function getModuleIsEnabled()
    {
        return $this->moduleIsEnabled;
    }

    /**
     * @param mixed $moduleIsEnabled
     */
    protected function setModuleIsEnabled($moduleIsEnabled)
    {
        $this->moduleIsEnabled = $moduleIsEnabled;
    }

    /**
     * @return mixed
     */
    public function getLogFrontendEnabled()
    {
        return $this->logFrontendEnabled;
    }

    /**
     * @param mixed $logFrontendEnabled
     */
    protected function setLogFrontendEnabled($logFrontendEnabled)
    {
        $this->logFrontendEnabled = $logFrontendEnabled;
    }

    /**
     * @return mixed
     */
    public function getLogAdminEnabled()
    {
        return $this->logAdminEnabled;
    }

    /**
     * @param mixed $logAdminEnabled
     */
    protected function setLogAdminEnabled($logAdminEnabled)
    {
        $this->logAdminEnabled = $logAdminEnabled;
    }

    /**
     * @return mixed
     */
    public function getAutoTranslateFrontEnabled()
    {
        return $this->autoTranslateFrontEnabled;
    }

    /**
     * @param mixed $autoTranslateFrontEnabled
     */
    protected function setAutoTranslateFrontEnabled($autoTranslateFrontEnabled)
    {
        $this->autoTranslateFrontEnabled = $autoTranslateFrontEnabled;
    }

    /**
     * @return mixed
     */
    public function getAutoTranslateAdminEnabled()
    {
        return $this->autoTranslateAdminEnabled;
    }

    /**
     * @param mixed $autoTranslateAdminEnabled
     */
    protected function setAutoTranslateAdminEnabled($autoTranslateAdminEnabled)
    {
        $this->autoTranslateAdminEnabled = $autoTranslateAdminEnabled;
    }

    public function getInstalledModulesOptionArray()
    {
        $installedModules = $this->getInstalledModules();
        $options[] = [
            'value' => null,
            'label' => $this->__("Select a module..."),
        ];
        foreach ($installedModules as $moduleNamespace => $moduleState):
            $options[] = [
                'value' => $moduleNamespace,
                'label' => $moduleNamespace,
            ];
        endforeach;

        return $options;
    }

    public function getInstalledModules()
    {
        return Mage::getConfig()->getNode('modules')->children();
    }

    public function getModuleFromTranslation($translationString)
    {
        if (is_string($translationString)):
            $hasModuleDelimiter = strpos($translationString, '::') !== false;
            if ($hasModuleDelimiter):
                $parsedTranslation = explode('::', $translationString);
                $moduleNamespace = $parsedTranslation[0];
                $appearsToBeModuleNamespace = strpos($moduleNamespace, '_') !== false;
                if ($appearsToBeModuleNamespace) return $moduleNamespace;
            endif;
        endif;

        return false;
    }

    /**
     * @param string $translationString
     * @return string
     * */
    public function getStringToTranslateFromSourceTranslation($translationString = "")
    {
        $moduleFromTranslation = $this->getModuleFromTranslation($translationString);
        if ($moduleFromTranslation):
            $search = $moduleFromTranslation . '::';
            $replace = '';
            return $translationString = str_replace($search, $replace, $translationString);
        else:
            return $translationString;
        endif;
    }
}