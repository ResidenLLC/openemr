<?php

namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Logging\SystemLogger;

class PaymentRestController
{
    private $logger;
    private $paymentService;

    public function __construct()
    {
        $this->logger = new SystemLogger();
        $this->paymentService = new PaymentService();
    }

    public function processPayment($pid, $aid, $data)
    {
        try {
            if (!isset($data['amount'], $data['method'], $data['source'])) {
                return [
                    'statusCode' => 400,
                    'success' => false,
                    'error' => "Missing required fields: amount, method, or source."
                ];
            }

            // Validate numeric values
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                return [
                    'statusCode' => 400,
                    'success' => false,
                    'error' => "Invalid amount value. Must be a positive number."
                ];
            }

            if (!is_string($data['method']) || empty(trim($data['method']))) {
                return [
                    'statusCode' => 400,
                    'success' => false,
                    'error' => "Invalid payment method. Must be a non-empty string."
                ];
            }

            if (!is_string($data['source']) || empty(trim($data['source']))) {
                return [
                    'statusCode' => 400,
                    'success' => false,
                    'error' => "Invalid payment source. Must be a non-empty string."
                ];
            }

            $result = $this->paymentService->recordPayment(
                $pid,
                $aid,
                $data['amount'],
                $data['method'],
                $data['source'],
                $data['description'] ?? ''
            );

            if (!$result['success']) {
                return [
                    'statusCode' => 400,
                    'success' => false,
                    'error' => $result['error']
                ];
            }

            return [
                'statusCode' => 200,
                'success' => true,
                'data' => [
                    'payment_id' => $result['payment_id'],
                    'message' => $result['message']
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->errorLogCaller($e->getMessage(), ['pid' => $pid, 'encounter' => $aid]);
            return [
                'statusCode' => 500,
                'success' => false,
                'error' => 'Internal server error'
            ];
        }
    }
} 