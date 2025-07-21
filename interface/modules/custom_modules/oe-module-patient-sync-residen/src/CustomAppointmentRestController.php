<?php
namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Uuid\UuidRegistry;

class CustomAppointmentRestController
{
    public function getAllFiltered($facility, $provider, $start_date, $end_date)
    {
        $sql = "SELECT * FROM openemr_postcalendar_events WHERE 1=1";
        $params = [];
        if ($facility) {
            $sql .= " AND pc_facility = ?";
            $params[] = $facility;
        }
        if ($provider) {
            $sql .= " AND pc_aid = ?";
            $params[] = $provider;
        }
        if ($start_date) {
            $sql .= " AND pc_eventDate >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $sql .= " AND pc_eventDate <= ?";
            $params[] = $end_date;
        }
        $result = sqlStatement($sql, $params);
        $appointments = [];
        while ($row = sqlFetchArray($result)) {
            // Convert all string values in $row to UTF-8
            array_walk_recursive($row, function (&$value) {
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
            });
            // Convert binary uuid to string if present
            if (isset($row['uuid'])) {
                $row['uuid'] = UuidRegistry::uuidToString($row['uuid']);
            }
            $appointments[] = $row;
        }
        return $appointments;
    }
} 