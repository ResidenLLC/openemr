<?php

namespace OpenEMR\Modules\PatientSync;

class AppointmentCategoryRestController
{
    public function getCategories()
    {
        $results = sqlStatement("SELECT pc_catid, pc_catname, pc_duration FROM openemr_postcalendar_categories");
        $categories = [];
        while ($row = sqlFetchArray($results)) {
            $categories[] = [
                'pc_catid' => $row['pc_catid'],
                'pc_catname' => $row['pc_catname'],
                'pc_duration' => $row['pc_duration'] / 60 // Convert from seconds to minutes,
            ];
        }
        return $categories;
    }
}
