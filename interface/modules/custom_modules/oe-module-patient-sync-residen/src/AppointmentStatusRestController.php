<?php

namespace OpenEMR\Modules\PatientSync;

class AppointmentStatusRestController
{
    public function getStatuses()
    {
        $results = sqlStatement('SELECT option_id, title FROM list_options WHERE list_id = "apptstat" AND activity = 1 ORDER BY seq ASC');
        $statuses = [];
        while ($row = sqlFetchArray($results)) {
            $statuses[] = [
                'option_id' => $row['option_id'],
                'title' => $row['title'],
            ];
        }
        return $statuses;
    }
} 