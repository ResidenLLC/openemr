<?php

namespace OpenEMR\Modules\PatientSync;

class AppointmentCategoryRestController
{
    public function getCategories()
    {
        $results = sqlStatement("SELECT pc_catid, pc_catname, pc_duration, pc_catdesc, pc_catcolor FROM openemr_postcalendar_categories where pc_cattype = 0 ORDER BY pc_seq");
        $categories = [];
        while ($row = sqlFetchArray($results)) {
            $categories[] = [
                'pc_catid' => $row['pc_catid'],
                'pc_catname' => $row['pc_catname'],
                'pc_catdesc' => $row['pc_catdesc'],
                'pc_duration' => $row['pc_duration'] / 60,
                'pc_catcolor' => $row['pc_catcolor'] ?? '#FFFFFF'
            ];
        }
        return $categories;
    }
}
