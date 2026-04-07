<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class PrencodeRepository
{
    public function fetchDefects($ppf, $inspectorID)
    {
        return DB::table('Inspector_Defect')
            ->select('Defect', 'Quantity')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $inspectorID)
            ->get();
    }

    public function fetchSmallDefects($ppf, $inspectorID)
    {
        return DB::table('Inspector_SmallDefect')
            ->select('LargeDefect', 'SmallDefect', 'Quantity')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $inspectorID)
            ->get();
    }

    public function fetchReworks($ppf, $inspectorID)
    {
        return DB::table('Inspector_Rework')
            ->select('Rework', 'Quantity')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $inspectorID)
            ->get();
    }
}
