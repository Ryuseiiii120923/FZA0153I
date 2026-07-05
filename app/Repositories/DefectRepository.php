<?php

namespace App\Repositories;

use App\Models\AddDefect;
use App\Models\Inspector\DefectInsp;
use App\Models\Inspector\SmallInsp;
use Illuminate\Support\Facades\DB;

class DefectRepository
{
    public function fetchAddDefect($ppf)
    {
        return Db::table('Defect')
            ->where('ppfno', $ppf)
            ->first();
    }

    public function getEncoders($ppf)
    {
        return DefectInsp::where('ppfno', $ppf)
            ->whereNotNull('inspectorId')
            ->distinct()
            ->pluck('inspectorId');
    }

    public function getDefectsPerEncoder($ppf, $encoder)
    {
        return DefectInsp::select('defect')
            ->where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->distinct()
            ->pluck('defect');
    }

    public function getTotalQty($ppf, $encoder, $defectName)
    {
        return DefectInsp::where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->where('defect', $defectName)
            ->sum('qty');
    }

    public function getLatestDate($ppf, $encoder, $defectName)
    {
        return DefectInsp::where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->where('defect', $defectName)
            ->max('created_at');
    }

    public function getInspectorName($encoder)
    {
        return DefectInsp::where('inspectorId', $encoder)
            ->value('inspName');
    }

    public function getSmallDefects($ppf, $encoder, $defectName, $Process)
    {
        return SmallInsp::selectRaw('small_defect, SUM(qty) as total_qty')
            ->where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->where('large_defect', $defectName)
            ->where('process', $Process) // ✅ NEW
            ->groupBy('small_defect')
            ->get();
    }

    public function getSmallDefectsForPpf($ppf)
{
    return SmallInsp::selectRaw('large_defect, process, inspectorId, small_defect, SUM(qty) as total_qty')
        ->where('ppfno', $ppf)
        ->groupBy('large_defect', 'process', 'inspectorId', 'small_defect')
        ->get()
        ->groupBy(fn($r) => $r->LargeDefect . '||' . $r->Process . '||' . $r->inspectorId);
}

    public function getDefectsGrouped($ppf)
    {
        return DefectInsp::select(
                'inspectorId',
                'inspName',
                'defect',
                'process', // ✅ NEW
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('MAX(created_at) as latest_date')
            )
            ->where('ppfno', $ppf)
            ->where('process','!=','HF')
            ->whereNotNull('inspectorId')
            ->groupBy('inspectorId', 'inspName', 'defect', 'process') // ✅ NEW
            ->orderBy('inspectorId')
            ->get();
    }

    public function getExpected($ppf)
    {
        return Db::table('計量１')->where('流動NO', $ppf)->first();
    }

    public function getDefectforMain()
    {
        return DB::table('DefectMatrix2')
            ->select('LargeDefect')
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();
    }
}
