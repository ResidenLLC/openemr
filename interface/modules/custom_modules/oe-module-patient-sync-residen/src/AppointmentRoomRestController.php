<?php
namespace OpenEMR\Modules\PatientSync;

/**
 * @OA\Get(
 *     path="/api/appointments_room",
 *     summary="Get all appointment rooms",
 *     tags={"Appointments"},
 *     security={{"OAuth2": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of appointment rooms",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="option_id", type="string"),
 *                 @OA\Property(property="title", type="string"),
 *                 @OA\Property(property="seq", type="integer"),
 *                 @OA\Property(property="notes", type="string"),
 *                 @OA\Property(property="activity", type="integer")
 *             )
 *         )
 *     )
 * )
 */
class AppointmentRoomRestController
{
    public function getRooms()
    {
        $sql = "SELECT option_id, title, seq, notes, activity FROM list_options WHERE list_id = ? ORDER BY seq, title";
        $params = ['patient_flow_board_rooms'];
        $result = sqlStatement($sql, $params);
        $rooms = [];
        while ($row = sqlFetchArray($result)) {
            $rooms[] = [
                'option_id' => $row['option_id'],
                'title' => $row['title'],
                'seq' => (int)$row['seq'],
                'notes' => $row['notes'],
                'activity' => (int)$row['activity'],
            ];
        }
        return $rooms;
    }
} 