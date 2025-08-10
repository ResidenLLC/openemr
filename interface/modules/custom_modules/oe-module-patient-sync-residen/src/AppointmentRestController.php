<?php
namespace OpenEMR\Modules\PatientSync;

use Exception;
use OpenEMR\RestControllers\RestControllerHelper;
use OpenEMR\Services\PatientTrackerService;
use OpenEMR\Services\AppointmentService;

/**
 * @OA\Put(
 *      path="/api/patient/{pid}/appointment/{eid}",
 *      description="Updates an existing appointment",
 *      tags={"standard"},
 *      @OA\Parameter(
 *          name="pid",
 *          in="path",
 *          description="The id for the patient.",
 *          required=true,
 *          @OA\Schema(type="string")
 *      ),
 *      @OA\Parameter(
 *          name="eid",
 *          in="path",
 *          description="The eid for the appointment.",
 *          required=true,
 *          @OA\Schema(type="string")
 *      ),
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\MediaType(
 *              mediaType="application/json",
 *              @OA\Schema(
 *                  @OA\Property(property="pc_catid", type="string", description="The category of the appointment."),
 *                  @OA\Property(property="pc_title", type="string", description="The title of the appointment."),
 *                  @OA\Property(property="pc_duration", type="string", description="The duration of the appointment."),
 *                  @OA\Property(property="pc_hometext", type="string", description="Comments for the appointment."),
 *                  @OA\Property(property="pc_apptstatus", type="string", description="use an option from resource=/api/list/apptstat"),
 *                  @OA\Property(property="pc_eventDate", type="string", description="The date of the appointment."),
 *                  @OA\Property(property="pc_startTime", type="string", description="The time of the appointment."),
 *                  @OA\Property(property="pc_facility", type="string", description="The facility id of the appointment."),
 *                  @OA\Property(property="pc_billing_location", type="string", description="The billing location id of the appointment."),
 *                  @OA\Property(property="pc_aid", type="string", description="The provider id for the appointment.")
 *              )
 *          )
 *      ),
 *      @OA\Response(response="200", ref="#/components/responses/standard"),
 *      @OA\Response(response="400", ref="#/components/responses/badrequest"),
 *      @OA\Response(response="401", ref="#/components/responses/unauthorized"),
 *      security={{"openemr_auth":{}}}
 * )
 */
class AppointmentRestController
{
    public function put($pid, $eid, $data)
    {
        $required = ['pc_catid', 'pc_title', 'pc_duration', 'pc_eventDate', 'pc_startTime', 'pc_aid'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                return [
                    'error' => "Missing required field: $field"
                ];
            }
        }
        // Build update array
        $update = [
            'pc_catid' => $data['pc_catid'],
            'pc_title' => $data['pc_title'],
            'pc_duration' => $data['pc_duration'],
            'pc_hometext' => $data['pc_hometext'] ?? '',
            'pc_apptstatus' => $data['pc_apptstatus'] ?? '',
            'pc_eventDate' => $data['pc_eventDate'],
            'pc_startTime' => $data['pc_startTime'],
            'pc_facility' => $data['pc_facility'] ?? '',
            'pc_billing_location' => $data['pc_billing_location'] ?? '',
            'pc_aid' => $data['pc_aid'],
            'pc_room' => $data['pc_room'] ?? ''
        ];
        $set = [];
        $params = [];
        foreach ($update as $col => $val) {
            $set[] = "$col = ?";
            $params[] = $val;
        }
        $params[] = $eid;
        $params[] = $pid;
        $sql = "UPDATE openemr_postcalendar_events SET ".implode(", ", $set)." WHERE pc_eid = ? AND pc_pid = ?";
        try {
            $result = sqlStatement($sql, $params);
            
            // Check if we need to create encounter linkage for check-in status on today's date
            if ($data['pc_eventDate'] == date('Y-m-d') && AppointmentService::isCheckInStatus($data['pc_apptstatus'])) {
                $this->createEncounterLinkage($pid, $eid, $data);
            }
            
        } catch (\Exception $e) {
            http_response_code(400);
            return [
                'error' => 'Failed to update appointment',
                'exception' => $e->getMessage()
            ];
        }
        return [
            'success' => true,
            'eid' => $eid,
            'pid' => $pid
        ];
    }

    /**
     * Create linkage between appointment and today's encounter when status is a check-in status
     */
    private function createEncounterLinkage($pid, $eid, $data)
    {
        try {
            // Find existing encounter for today
            $encounter = $this->findTodaysEncounter($pid, $data['pc_eventDate']);
            
            if ($encounter) {
                // Use PatientTrackerService to create the linkage
                $trackerService = new PatientTrackerService();
                $trackerService->manage_tracker_status(
                    $data['pc_eventDate'], 
                    $data['pc_startTime'], 
                    $eid, 
                    $pid, 
                    $_SESSION["authUser"] ?? 'api_user', 
                    $data['pc_apptstatus'], 
                    $data['pc_room'] ?? '', 
                    $encounter
                );
            }
        } catch (\Exception $e) {
            // Log error but don't fail the appointment update
            error_log("Failed to create encounter linkage: " . $e->getMessage());
        }
    }

    /**
     * Find existing encounter for the patient on the given date
     */
    private function findTodaysEncounter($pid, $date)
    {
        $sql = "SELECT encounter FROM form_encounter 
                WHERE pid = ? AND date = ? 
                ORDER BY encounter DESC LIMIT 1";
        
        $result = sqlQuery($sql, array($pid, $date));
        
        return $result['encounter'] ?? null;
    }
}
