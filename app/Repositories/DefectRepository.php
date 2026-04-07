<?php

namespace App\Repositories;

use App\Models\AddDefect;
use Illuminate\Support\Facades\DB;

class DefectRepository
{
    public function fetchAddDefect($ppf)
    {
        return AddDefect::where('PPFNo', $ppf)->first();
    }

       public function getEncoders($ppf)
    {
        return DB::table('Inspector_Defect')
            ->where('PPFNo', $ppf)
            ->whereNotNull('InspectorID')
            ->distinct()
            ->pluck('InspectorID');
    }

    public function getDefectsPerEncoder($ppf, $encoder)
    {
        return DB::table('Inspector_Defect')
            ->select('Defect')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $encoder)
            ->distinct()
            ->pluck('Defect');
    }

    public function getTotalQty($ppf, $encoder, $defectName)
    {
        return DB::table('Inspector_Defect')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $encoder)
            ->where('Defect', $defectName)
            ->sum('Quantity');
    }

    public function getLatestDate($ppf, $encoder, $defectName)
    {
        return DB::table('Inspector_Defect')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $encoder)
            ->where('Defect', $defectName)
            ->max('DateEncode');
    }

    public function getOperatorName($encoder)
    {
        return DB::table('Inspector_Defect')
            ->where('InspectorID', $encoder)
            ->value('insp_name');
    }

    public function getSmallDefects($ppf, $encoder, $defectName,$encodeProcess)
    {
        return DB::table('Inspector_Small')
            ->selectRaw('SmallDefect, SUM(Qty) as total_qty')
            ->where('PPFNo', $ppf)
            ->where('InspectorID', $encoder)
            ->where('LargeDefect', $defectName)
            ->where('EncodeProcess', $encodeProcess) // ✅ NEW
            ->groupBy('SmallDefect')
            ->get();
    }

    public function getDefectsGrouped($ppf)
{
    return DB::table('Inspector_Defect')
        ->select(
            'InspectorID',
            'insp_name',
            'Defect',
            'EncodeProcess',
            DB::raw('SUM(Quantity) as total_qty'),
            DB::raw('MAX(DateEncode) as latest_date')
        )
        ->where('PPFNo', $ppf)
        ->whereNotNull('InspectorID')
        ->groupBy('InspectorID', 'insp_name', 'Defect', 'EncodeProcess')
        ->orderBy('InspectorID')
        ->get();
}
}