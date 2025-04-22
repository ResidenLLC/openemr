<?php
namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Services\Globals\GlobalSetting;

class GlobalConfig
{

    const CONFIG_ENABLE_PATIENTS_SYNC = "oe_patient_sysnc_patient_sync_enabled";
    const CONFIG_OPTION_API_URL = 'oe_patient_sysnc_config_option_text';
    const CONFIG_OPTION_API_TOKEN = 'oe_patient_sysnc_config_option_encrypted';
    const CONFIG_OPTION_API_PUBLIC_KEY = "oe_patient_sysnc_patient_api_public_key";



    const MODULE_NAME = 'patient_sync';

    private $globalsArray;

    /**
     * @var CryptoGen
     */
    private $cryptoGen;

    public function __construct(array $globalsArray)
    {
        $this->globalsArray = $globalsArray;
        $this->cryptoGen = new CryptoGen();
    }

    public function isConfigured()
    {
        $keys = [self::CONFIG_OPTION_API_PUBLIC_KEY, self::CONFIG_OPTION_API_TOKEN, self::CONFIG_OPTION_API_URL];
        foreach ($keys as $key) {
            $value = $this->getGlobalSetting($key);
            if (empty($value)) {
                return false;
            }
        }
        return true;
    }

    public function getApiUrl()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_API_URL);
    }

    public function getApiPublicKey()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_API_PUBLIC_KEY);
    }

    public function getApiToken()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_API_TOKEN);
    }

    /**
     * Returns our decrypted value if we have one, or false if the value could not be decrypted or is empty.
     * @return bool|string
     */
    public function getEncryptedOption()
    {
        $encryptedValue = $this->getGlobalSetting(self::CONFIG_OPTION_API_TOKEN);
        return $this->cryptoGen->decryptStandard($encryptedValue);
    }

    public function getGlobalSetting($settingKey)
    {
        return $this->globalsArray[$settingKey] ?? null;
    }



    public function getGlobalSettingSectionConfiguration()
    {
        $settings = [
            self::CONFIG_ENABLE_PATIENTS_SYNC => [
                'title' => 'Enable patient Sync OpenEMR and Residen'
                ,'description' => 'Enable the patients sync between Openemr and Residen'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ],
            self::CONFIG_OPTION_API_URL => [
                'title' => 'The Residen API url'
                ,'description' => 'The Residen API path for comunication with the API'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => ''
            ],
            self::CONFIG_OPTION_API_PUBLIC_KEY => [
                'title' => 'Public API Key'
                ,'description' => 'The Residen API used for linking the doctor'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => ''
            ]
            ,self::CONFIG_OPTION_API_TOKEN => [
                'title' => 'Security token (Encrypted)'
                ,'description' => 'The Bearer security token used for the API calls. Warning ensitive data'
                ,'type' => GlobalSetting::DATA_TYPE_ENCRYPTED
                ,'default' => ''
            ]
        ];
        return $settings;
    }

}
