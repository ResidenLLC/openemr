<?php

namespace OpenEMR\RestControllers;

use OpenEMR\Services\PaymentService;

class PaymentRestController
{
    public function postPayment($pid, $eid, $data)
    {
        // Basic validation
        if (!isset($data['amount'], $data['method'], $data['source'])) {
            return ["success" => false, "error" => "Missing required fields: amount, method, or source."];
        }
        $amount = (float)$data['amount'];
        $method = $data['method'];
        $source = $data['source'];
        $description = $data['description'] ?? '';

        $service = new PaymentService();
        $result = $service->recordPayment($pid, $eid, $amount, $method, $source, $description);
        if ($result && isset($result['payment_id'])) {
            return ["success" => true, "payment_id" => $result['payment_id']];
        } else {
            return ["success" => false, "error" => $result['error'] ?? 'Failed to record payment.'];
        }
    }
}
