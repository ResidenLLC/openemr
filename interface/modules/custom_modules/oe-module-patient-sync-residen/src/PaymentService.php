<?php

namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Services\BaseService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\EncounterService;

// Ensure the frontPayment function is available
require_once $GLOBALS['fileroot'] . '/library/payment.inc.php';

class PaymentService extends BaseService
{
    const TABLE_NAME = 'payments';
    private $logger;
    private $patientService;
    private $encounterService;

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME);
        $this->logger = new SystemLogger();
        $this->patientService = new PatientService();
        $this->encounterService = new EncounterService();
    }

    public function recordPayment($pid, $encounterId, $amount, $method, $source, $description = '')
    {
        try {
            // Validate patient exists
            $patient = $this->patientService->findByPid($pid);
            if (empty($patient)) {
                return ['success' => false, 'error' => "Patient with ID $pid not found"];
            }

            // Validate encounter exists and belongs to patient (strict check by pid + eid)
            $encounter = $this->encounterService->getOneByPidEid($pid, $encounterId);
            if (empty($encounter)) {
                return ['success' => false, 'error' => "Encounter $encounterId does not belong to patient $pid"];
            }

            $currentDateTime = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');

            // First create ar_session record
            $sessionId = sqlInsert(
                "INSERT INTO ar_session SET
                payer_id = 0,
                patient_id = ?,
                user_id = ?,
                closed = 0,
                reference = ?,
                check_date = ?,
                deposit_date = ?,
                pay_total = ?,
                payment_type = 'patient',
                description = ?,
                adjustment_code = 'patient_payment',
                post_to_date = ?,
                payment_method = ?",
                array(
                    $pid,
                    $_SESSION['authUserID'] ?? 0,
                    $source,
                    $currentDate,
                    $currentDate,
                    $amount,
                    $description,
                    $currentDate,
                    $method
                )
            );

            if (!$sessionId) {
                return ['success' => false, 'error' => 'Failed to create payment session'];
            }

            // Use the global frontPayment function to insert into the payments table
            $paymentId = frontPayment(
                $pid,
                $encounterId,
                $method,
                $source,
                $amount,
                0.00, // amount2
                $currentDateTime
            );

            if (!$paymentId) {
                return ['success' => false, 'error' => 'Failed to insert payment record using frontPayment'];
            }

            // Get next sequence_no for ar_activity
            $seq = sqlQuery(
                "SELECT IFNULL(MAX(sequence_no),0) + 1 AS next_seq FROM ar_activity WHERE pid = ? AND encounter = ?",
                array($pid, $encounterId)
            );
            $sequence_no = $seq['next_seq'] ?? 1;

            // Insert into ar_activity using the session_id
            $arActivity = sqlInsert(
                "INSERT INTO ar_activity SET
                pid = ?,
                encounter = ?,
                sequence_no = ?,
                code_type = '',
                code = '',
                modifier = '',
                payer_type = ?,
                post_time = ?,
                post_user = ?,
                session_id = ?,
                pay_amount = ?,
                adj_amount = ?,
                memo = '',
                account_code = 'PP'",
                array(
                    $pid,
                    $encounterId,
                    $sequence_no,
                    0,
                    $currentDateTime,
                    $_SESSION['authUserID'] ?? 0,
                    $sessionId,
                    $amount,
                    '0.00'
                )
            );


            return [
                'success' => true,
                'payment_id' => $paymentId,
                'session_id' => $sessionId,
                'message' => "Payment recorded successfully"
            ];

        } catch (\Exception $e) {
            $this->logger->errorLogCaller($e->getMessage(), ['pid' => $pid, 'encounter' => $encounterId]);
            return ['success' => false, 'error' => 'Internal server error'];
        }
    }
}
