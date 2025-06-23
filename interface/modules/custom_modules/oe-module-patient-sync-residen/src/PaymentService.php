<?php

namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Services\BaseService;

class PaymentService extends BaseService
{
    const TABLE_NAME = 'payments';
    private $logger;

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME);
        $this->logger = new SystemLogger();
    }

    public function recordPayment($pid, $eid, $amount, $method, $source, $description = '')
    {
        try {
            // Validate inputs
            if (!is_numeric($pid) || !is_numeric($eid) || !is_numeric($amount)) {
                return ['error' => 'Invalid input parameters'];
            }

            global $authUser, $authUserID;
            $user = $authUser ?? ($_SESSION['authUser'] ?? 'api');
            $userId = $authUserID ?? ($_SESSION['authUserID'] ?? 0);
            $timestamp = date('Y-m-d H:i:s');

            // Insert into ar_session
            $session_id = sqlInsert(
                "INSERT INTO ar_session (payer_id, patient_id, user_id, closed, reference, check_date, deposit_date, pay_total, payment_type, description, adjustment_code, post_to_date, payment_method) VALUES (?, ?, ?, 0, ?, NOW(), NOW(), ?, 'patient', ?, 'patient_payment', NOW(), ?)",
                [0, $pid, $userId, $source, $amount, $description, $method]
            );

            if (!$session_id) {
                return ['error' => 'Failed to insert ar_session'];
            }

            // Get code_type, code, modifier for this encounter (optional, fallback to empty)
            $row = sqlQuery(
                "SELECT code_type, code, modifier FROM billing WHERE pid=? AND encounter=? AND activity=1 LIMIT 1",
                [$pid, $eid]
            );

            // Get next sequence_no
            $seq = sqlQuery(
                "SELECT IFNULL(MAX(sequence_no),0) + 1 AS increment FROM ar_activity WHERE pid = ? AND encounter = ?",
                [$pid, $eid]
            );
            $sequence_no = $seq['increment'] ?? 1;

            // Insert into ar_activity
            $activity_id = sqlInsert(
                "INSERT INTO ar_activity (pid, encounter, sequence_no, payer_type, post_time, post_user, session_id, pay_amount, adj_amount, account_code) VALUES (?, ?,  ?, 0, NOW(), ?, ?, ?, 0, 'PP')",
                [$pid, $eid, $sequence_no, $userId, $session_id, $amount]
            );
            if (!$activity_id) {
                return ['error' => 'Failed to insert ar_activity'];
            }

            $this->logger->debug("ar_activity insert values", [
                'pid' => $pid,
                'eid' => $eid,
                'sequence_no' => $sequence_no,
                'code_type' => $row['code_type'] ?? 'COPAY',
                'code' => $row['code'] ?? 'COPAY',
                'modifier' => $row['modifier'] ?? '',
                'userId' => $userId,
                'session_id' => $session_id,
                'amount' => $amount
            ]);

            // Insert into payments
            $payment_id = sqlInsert(
                "INSERT INTO payments (pid, encounter, dtime, user, method, source, amount1, amount2) VALUES (?, ?, ?, ?, ?, ?, ?, 0)",
                [$pid, $eid, $timestamp, $user, $method, $source, $amount]
            );
            if (!$payment_id) {
                return ['error' => 'Failed to insert payments'];
            }


            if ($payment_id) {
                return ['payment_id' => $payment_id];
            } else {
                throw new \Exception("Failed to insert payment record");
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Error recording payment",
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return ['error' => 'Failed to record payment: ' . $e->getMessage()];
        }
    }
}
