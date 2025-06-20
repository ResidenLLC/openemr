<?php

namespace OpenEMR\Services;

class PaymentService
{
    public function recordPayment($pid, $eid, $amount, $method, $source, $description = '')
    {
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
        $row = sqlQuery("SELECT code_type, code, modifier FROM billing WHERE pid=? AND encounter=? AND activity=1 LIMIT 1", [$pid, $eid]);
        $code_type = $row['code_type'] ?? '';
        $code = $row['code'] ?? '';
        $modifier = $row['modifier'] ?? '';

        // Get next sequence_no
        $seq = sqlQuery("SELECT IFNULL(MAX(sequence_no),0) + 1 AS increment FROM ar_activity WHERE pid = ? AND encounter = ?", [$pid, $eid]);
        $sequence_no = $seq['increment'] ?? 1;

        // Insert into ar_activity
        $activity_id = sqlInsert(
            "INSERT INTO ar_activity (pid, encounter, sequence_no, code_type, code, modifier, payer_type, post_time, post_user, session_id, pay_amount, adj_amount, account_code) VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), ?, ?, ?, 0, 'PP')",
            [$pid, $eid, $sequence_no, $code_type, $code, $modifier, $userId, $session_id, $amount]
        );
        if (!$activity_id) {
            return ['error' => 'Failed to insert ar_activity'];
        }

        // Insert into payments
        $payment_id = sqlInsert(
            "INSERT INTO payments (pid, encounter, dtime, user, method, source, amount1, amount2) VALUES (?, ?, ?, ?, ?, ?, ?, 0)",
            [$pid, $eid, $timestamp, $user, $method, $source, $amount]
        );
        if (!$payment_id) {
            return ['error' => 'Failed to insert payments'];
        }

        return ['payment_id' => $payment_id];
    }
} 