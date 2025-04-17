<?php
namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Services\Globals\GlobalSetting;

class GlobalConfig
{

    const CONFIG_OPTION_TEXT = 'oe_patient_sysnc_config_option_text';
    const CONFIG_OPTION_ENCRYPTED = 'oe_patient_sysnc_config_option_encrypted';
    const CONFIG_OVERRIDE_TEMPLATES = "oe_patient_sysnc_override_twig_templates";
    const CONFIG_ENABLE_MENU = "oe_patient_sysnc_add_menu_button";
    const CONFIG_ENABLE_BODY_FOOTER = "oe_patient_sysnc_add_body_footer";
    const CONFIG_ENABLE_FHIR_API = "oe_patient_sysnc_enable_fhir_api";
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
        $keys = [self::CONFIG_OPTION_TEXT, self::CONFIG_OPTION_ENCRYPTED];
        foreach ($keys as $key) {
            $value = $this->getGlobalSetting($key);
            if (empty($value)) {
                return false;
            }
        }
        return true;
    }

    public function getTextOption()
    {
        return $this->getGlobalSetting(self::CONFIG_OPTION_TEXT);
    }

    /**
     * Returns our decrypted value if we have one, or false if the value could not be decrypted or is empty.
     * @return bool|string
     */
    public function getEncryptedOption()
    {
        $encryptedValue = $this->getGlobalSetting(self::CONFIG_OPTION_ENCRYPTED);
        return $this->cryptoGen->decryptStandard($encryptedValue);
    }

    public function getGlobalSetting($settingKey)
    {
        return $this->globalsArray[$settingKey] ?? null;
    }



    public function getGlobalSettingSectionConfiguration()
    {
        $settings = [
            self::CONFIG_OPTION_TEXT => [
                'title' => 'The Residen API path'
                ,'description' => 'The Residen API path for comunication with the API'
                ,'type' => GlobalSetting::DATA_TYPE_TEXT
                ,'default' => ''
            ]
            ,self::CONFIG_OPTION_ENCRYPTED => [
                'title' => 'Security token (Encrypted)'
                ,'description' => 'The Bearer security token used for the API calls. Warning ensitive data'
                ,'type' => GlobalSetting::DATA_TYPE_ENCRYPTED
                ,'default' => ''
            ]
            ,self::CONFIG_OPTION_ENCRYPTED => [
                'title' => 'Security token (Encrypted)'
                ,'description' => 'The Bearer security token used for the API calls. Warning ensitive data'
                ,'type' => GlobalSetting::DATA_TYPE_ENCRYPTED
                ,'default' => ''
            ]
            ,self::CONFIG_OVERRIDE_TEMPLATES => [
                'title' => 'Skeleton Module enable overriding twig files'
                ,'description' => 'Shows example of overriding a twig file'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]
            ,self::CONFIG_ENABLE_MENU => [
                'title' => 'Skeleton Module add module menu item'
                ,'description' => 'Shows example of adding a menu item to the system (requires logging out and logging in again)'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]
            ,self::CONFIG_ENABLE_BODY_FOOTER => [
                'title' => 'Skeleton Module Enable Body Footer example.'
                ,'description' => 'Shows example of adding a menu item to the system (requires logging out and logging in again)'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]
            ,self::CONFIG_ENABLE_FHIR_API => [
                'title' => 'Skeleton Module Enable FHIR API Extension example.'
                ,'description' => 'Shows example of extending the FHIR api with the skeleton module.'
                ,'type' => GlobalSetting::DATA_TYPE_BOOL
                ,'default' => ''
            ]
        ];
        return $settings;
    }




    // Configuration field definitions
    public static function getGlobalSettings()
    {
        return [
            'patient_sync_api_endpoint' => [
                'title' => 'API Endpoint URL',
                'description' => 'The URL endpoint to synchronize patient data',
                'type' => 'text',
                'default' => 'https://your-platform-api.com/patients'
            ],
            'patient_sync_api_key' => [
                'title' => 'API Security Token',
                'description' => 'Security token/key for API authentication',
                'type' => 'encrypted',
                'default' => ''
            ],
            'patient_sync_enabled' => [
                'title' => 'Enable Synchronization',
                'description' => 'Turn patient synchronization on or off',
                'type' => 'bool',
                'default' => '1'
            ],
            'patient_sync_log_level' => [
                'title' => 'Log Level',
                'description' => 'Detail level for synchronization logs',
                'type' => 'select',
                'options' => [
                    'error' => 'Errors Only',
                    'info' => 'Info',
                    'debug' => 'Debug (Verbose)'
                ],
                'default' => 'info'
            ]
        ];
    }
}
