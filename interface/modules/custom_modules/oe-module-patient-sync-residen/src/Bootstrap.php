<?php
namespace OpenEMR\Modules\PatientSync;






use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Events\Patient\PatientCreatedEvent;
use OpenEMR\Events\Patient\PatientUpdatedEvent;
use OpenEMR\Events\Patient\PatientBeforeDeleteEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Modules\PatientSync\GlobalConfig;
use OpenEMR\Services\PatientService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OpenEMR\Core\Kernel;
use OpenEMR\Services\Globals\GlobalSetting;
use OpenEMR\Menu\MenuEvent;


class Bootstrap
{
    const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";
    const MODULE_NAME = "oe-module-patient-sync-residen";


    /**
     * @var EventDispatcherInterface The object responsible for sending and subscribing to events through the OpenEMR system
     */
    private $eventDispatcher;

    /**
     * @var GlobalConfig Holds our module global configuration values that can be used throughout the module.
     */
    private $globalsConfig;

    /**
     * @var string The folder name of the module.  Set dynamically from searching the filesystem.
     */
    private $moduleDirectoryName;

    /**
     * @var PatientSyncService
     */
    private $syncService;

    /**
     * @var SystemLogger
     */
    private $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        global $GLOBALS;

        if (empty($kernel)) {
            $kernel = new Kernel();
        }

        $this->moduleDirectoryName = basename(dirname(__DIR__));
        $this->eventDispatcher = $eventDispatcher;

        // we inject our globals value.
        $this->globalsConfig = new GlobalConfig($GLOBALS);
        $this->syncService = new PatientSyncService();
        $this->logger = new SystemLogger();
    }
    public function addGlobalSettings()
    {
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, [$this, 'addGlobalSettingsSection']);
    }

    public function addGlobalSettingsSection(GlobalsInitializedEvent $event)
    {
        global $GLOBALS;

        $service = $event->getGlobalsService();
        $section = xlt("Patient Sync with Residen App");
        $service->createSection($section, 'Portal');

        $settings = $this->globalsConfig->getGlobalSettingSectionConfiguration();

        foreach ($settings as $key => $config) {
            $value = $GLOBALS[$key] ?? $config['default'];
            $service->appendToSection(
                $section,
                $key,
                new GlobalSetting(
                    xlt($config['title']),
                    $config['type'],
                    $value,
                    xlt($config['description']),
                    true
                )
            );
        }
    }

    public function getGlobalConfig()
    {
        return $this->globalsConfig;
    }

    public function subscribeToEvents()
    {
        $this->addGlobalSettings();
        if ($this->globalsConfig->isConfigured()) {
            $this->registerPatientCreate();
            $this->registerPatientModified();
        }
    }

    public function registerPatientCreate()
    {
        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_PATIENTS_SYNC)) {
            $this->eventDispatcher->addListener(PatientCreatedEvent::EVENT_HANDLE, [$this, 'onPatientCreated']);
        }
    }

    public function registerPatientModified()
    {
        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_PATIENTS_SYNC)) {
            $this->eventDispatcher->addListener(PatientUpdatedEvent::EVENT_HANDLE, [$this, 'onPatientUpdated']);
        }
    }

    public function onPatientCreated(PatientCreatedEvent $event)
    {
        try {
            $patientData = $event->getPatientData();
            var_dump($patientData);
            $this->syncService->syncPatientCreated($patientData);
            $this->logEvent('debug', "Patient sync: Successfully synced new patient", ['pid' => $patientData['pid']]);
        } catch (\Exception $e) {
            $this->logEvent('error', "Patient sync error on creation", ['error' => $e->getMessage()]);
        }
    }

    public function onPatientUpdated(PatientUpdatedEvent $event)
    {
        try {
            $patientData = $event->getPatientData();
            $this->syncService->syncPatientUpdated($patientData);
            $this->logEvent('debug', "Patient sync: Successfully synced updated patient", ['pid' => $patientData['pid']]);
        } catch (\Exception $e) {
            $this->logEvent('error', "Patient sync error on update", ['error' => $e->getMessage()]);
        }
    }

    private function logEvent($level, $message, $context = [])
    {
        $configuredLevel = $GLOBALS['patient_sync_log_level'] ?? 'info';

        // Only log if the configured level includes this level
        $shouldLog = false;
        switch ($configuredLevel) {
            case 'debug':
                $shouldLog = true;
                break;
            case 'info':
                $shouldLog = $level != 'debug';
                break;
            case 'error':
                $shouldLog = $level == 'error';
                break;
        }

        if ($shouldLog) {
            $this->logger->$level($message, $context);
        }
    }

    /*public function onPatientDeleted(PatientBeforeDeleteEvent $event)
    {
        // Check if sync is enabled in settings
        if ($GLOBALS['patient_sync_enabled'] !== '1') {
            return;
        }

        try {
            $pid = $event->getPid();
            $this->syncService->syncPatientDeleted($pid);
            $this->logEvent('debug', "Patient sync: Successfully synced deleted patient", ['pid' => $pid]);
        } catch (\Exception $e) {
            $this->logEvent('error', "Patient sync error on deletion", ['error' => $e->getMessage()]);
        }
    }*/
}
