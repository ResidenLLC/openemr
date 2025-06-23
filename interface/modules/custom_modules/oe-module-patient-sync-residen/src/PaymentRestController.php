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
                    "success" => false,
                    "error" => "Missing required fields: amount, method, or source."
                ];
            }

            $amount = (float)$data['amount'];
            $method = $data['method'];
            $source = $data['source'];
            $description = $data['description'] ?? '';

            $result = $this->paymentService->recordPayment($pid, $aid, $amount, $method, $source, $description);

            if ($result && isset($result['payment_id'])) {
                return [
                    "success" => true,
                    "payment_id" => $result['payment_id']
                ];
            } else {
                return [
                    "success" => false,
                    "error" => $result['error'] ?? 'Failed to record payment.'
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error("Payment processing error", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [
                "success" => false,
                "error" => "Internal server error processing payment."
            ];
        }
    }
} 