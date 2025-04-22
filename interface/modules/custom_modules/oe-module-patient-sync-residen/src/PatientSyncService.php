<?php
namespace OpenEMR\Modules\PatientSync;

use GuzzleHttp\Client;
use OpenEMR\Common\Logging\SystemLogger;

class PatientSyncService
{
    private $client;
    private $apiEndpoint;
    private $apiKey;
    private $apiToken;
    private $logger;
    private $globalsConfig;

    public function __construct()
    {
        global $GLOBALS;

        $this->logger = new SystemLogger();

        // Initialize GlobalConfig with GLOBALS
        $this->globalsConfig = new GlobalConfig($GLOBALS);

        // Get values from GlobalConfig
        $this->apiEndpoint = $this->globalsConfig->getApiUrl();
        $this->apiKey = $this->globalsConfig->getApiPublicKey();
        $this->apiToken = $this->globalsConfig->getApiToken();

        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json; charset=utf-8',
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function syncPatientCreated($patientData)
    {
        // Format and sanitize patient data
        $formattedData = $this->formatPatientData($patientData);

        try {
            return $this->client->post($this->apiEndpoint . '/patient/' . $this->apiToken, [
                'json' => $formattedData,
                'encode_content' => 'json'
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync patient creation", [
                'error' => $e->getMessage(),
                'data' => $formattedData
            ]);
            throw $e;
        }
    }

    public function syncPatientUpdated($patientData)
    {
        $formattedData = $this->formatPatientData($patientData);
        $patientUuid = $patientData['uuid'];

        try {
            return $this->client->put($this->apiEndpoint . '/patient/' . $this->apiKey .'/' .$patientUuid , [
                'json' => $formattedData,
                'encode_content' => 'json'
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync patient update", [
                'error' => $e->getMessage(),
                'pid' => $patientUuid
            ]);
            throw $e;
        }
    }

    public function syncPatientDeleted($pid)
    {
        try {
            return $this->client->delete($this->apiEndpoint . '/' . $pid);
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync patient deletion", [
                'error' => $e->getMessage(),
                'pid' => $pid
            ]);
            throw $e;
        }
    }

    private function formatPatientData($patientData)
    {
        // Ensure all string values are properly UTF-8 encoded
        $sanitizedData = array_map(function ($value) {
            if (is_string($value)) {
                // Remove invalid UTF-8 characters and normalize encoding
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
            return $value;
        }, $patientData);

        // Convert OpenEMR patient data format to your API format
        return [
            'uuid' => $sanitizedData['uuid'] ?? '',
            'first_name' => $sanitizedData['fname'] ?? '',
            'last_name' => $sanitizedData['lname'] ?? '',
            'phone_home' => $sanitizedData['phone_home'] ?? '',
        ];
    }
}
