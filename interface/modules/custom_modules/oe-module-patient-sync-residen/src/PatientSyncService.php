<?php
namespace OpenEMR\Modules\PatientSync;

use GuzzleHttp\Client;
use OpenEMR\Common\Logging\SystemLogger;

class PatientSyncService
{
    private $client;
    private $apiEndpoint;
    private $apiKey;
    private $logger;

    public function __construct()
    {
        $this->logger = new SystemLogger();
        $this->apiEndpoint = $GLOBALS['patient_sync_api_endpoint'] ?? 'https://residen.com/api/patients';
        $this->apiKey = $GLOBALS['patient_sync_api_key'] ?? '';

        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function syncPatientCreated($patientData)
    {
        // Format patient data for your API
        $formattedData = $this->formatPatientData($patientData);
        $ss = $this->client->post($this->apiEndpoint, [
            'json' => $formattedData
        ]);
        var_dump($formattedData, $ss);die();



        // Make API call to create patient
        return $this->client->post($this->apiEndpoint, [
            'json' => $formattedData
        ]);
    }

    public function syncPatientUpdated($patientData)
    {
        // Format patient data for your API
        $formattedData = $this->formatPatientData($patientData);
        $patientId = $patientData['pid'];

        // Make API call to update patient
        return $this->client->put($this->apiEndpoint . '/' . $patientId, [
            'json' => $formattedData
        ]);
    }

    public function syncPatientDeleted($pid)
    {
        // Make API call to delete patient
        return $this->client->delete($this->apiEndpoint . '/' . $pid);
    }

    private function formatPatientData($patientData)
    {
        // Convert OpenEMR patient data format to your API format
        return [
            'uuid' => $patientData['uuid'],
            'first_name' => $patientData['fname'],
            'last_name' => $patientData['lname'],
            'phone_home' => $patientData['phone_home'],
        ];
    }
}
