<?php
namespace OpenEMR\Modules\PatientSync;

use Exception;
use OpenEMR\RestControllers\RestControllerHelper;

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
            'pc_aid' => $data['pc_aid']
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
}
