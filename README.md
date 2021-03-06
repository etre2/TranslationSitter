# TranslationSitter

## Features

* Log strings that are determined to not have a translation
* Turn on Google API Translations so that end-users never see the wrong language
 - If a translation is generated by the Google API, it is stored in the Magento core_translate table so that the API does not have to be consumed again
 - You can review the Google translations and modify them within the Magento Backend
* Review strings with missing translations from Magento Backend
* Add, modify and export translations from the Magento Backend
* The import feature assumes that the imported document has columns matching those created during export.

Translation management and module configuration can be accessed from **System > TranslationSitter**
