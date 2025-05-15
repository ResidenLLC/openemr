<?php
namespace OpenEMR\Modules\PatientSync;

use GuzzleHttp\Client;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Services\PatientService;

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

        $this->patientService = new PatientService();

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
            ],
            'verify' => false
        ]);
    }

    public function syncPatientCreated($patientData)
    {
        // Format and sanitize patient data
        $formattedData = $this->formatPatientData($patientData);

        $this->logger->debug("SAVE API TRIMIS: " , ['data' => $formattedData]);

        try {
            $url = rtrim($this->apiEndpoint, '/') . '/patient/' . $this->apiKey;
            $this->logger->debug("Making POST API call to: " . $url);

            $response = $this->client->post($url, [
                'json' => $formattedData,
                'encode_content' => 'json',
                'http_errors' => false // Don't throw exceptions for HTTP errors
            ]);

            // Log the response status and body
            $this->logger->debug("API Response Status: " . $response->getStatusCode());
            $this->logger->debug("API Response Body: " . $response->getBody());

            if ($response->getStatusCode() >= 400) {
                throw new \Exception("Add API returned error status: " . $response->getStatusCode() . " - " . $response->getBody());
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync patient creation", [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $formattedData
            ]);
            throw $e;
        }
    }

    public function syncPatientUpdated($patientData)
    {
        $formattedData = $this->formatPatientData($patientData);
        $patientUuid = UuidRegistry::uuidToString($this->patientService->getUuid($patientData['pid']));

        if (!$patientUuid) {
            throw new \Exception("Could not find UUID for patient with PID: " . $patientData['pid']);
        }

        $this->logger->debug("UPDATE API TRIMIS: " , ['data' => $formattedData]);

        try {
            $url = rtrim($this->apiEndpoint, '/') . '/patient/' . $this->apiKey . '/' . $patientUuid;
            $this->logger->debug("Making PUT API call to: " . $url);
            return $this->client->put($url, [
                'json' => $formattedData,
                'encode_content' => 'json',
                'http_errors' => false // Don't throw exceptions for HTTP errors
            ]);

            // Log the response status and body
            $this->logger->debug("API Response Status: " . $response->getStatusCode());
            $this->logger->debug("API Response Body: " . $response->getBody());

            if ($response->getStatusCode() >= 400) {
                throw new \Exception("Update API returned error status: " . $response->getStatusCode() . " - " . $response->getBody());
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync patient update", [
                'error' => $e->getMessage(),
                'url' => $url,
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
            if (is_string($value) && $value !== '') {
                // Remove invalid UTF-8 characters and normalize encoding
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
            return $value;
        }, $patientData);

        // Convert OpenEMR patient data format to your API format
        return [
            'id' => $sanitizedData['pid'] ?? '',
            'uuid' => UuidRegistry::uuidToString($this->patientService->getUuid($sanitizedData['pid'])),
            'first_name' => $sanitizedData['fname'] ?? '',
            'last_name' => $sanitizedData['lname'] ?? '',
            'phone_cell' => $sanitizedData['phone_cell'] ?? '',
            'phone_home' => $sanitizedData['phone_home'] ?? '',
            'phone_biz' => $sanitizedData['phone_biz'] ?? '',
            'hipaa_notice' => $sanitizedData['hipaa_notice'] ?? '',
            'billing_note' => $sanitizedData['billing_note'] ?? '',
        ];
    }
}
