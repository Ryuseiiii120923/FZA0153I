<?php

namespace App\Repositories\Defect;

use App\Models\Defects;
use App\Models\Inspector\DefectInsp;
use App\Models\Inspector\SmallInsp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DefectRepository{
    public function getEncoders(int $ppf)
    {
        return DefectInsp::where('ppfno', $ppf)
            ->whereNotNull('inspectorId')
            ->distinct()
            ->pluck('inspectorId');
    }

    public function getDefectsPerEncoder(int $ppf, string $encoder)
    {
        return DefectInsp::select('defect')
            ->where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->distinct()
            ->pluck('defect');
    }

    public function getTotalQty(int $ppf, string $encoder, string $defectName)
    {
        return DefectInsp::where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->where('defect', $defectName)
            ->sum('qty');
    }

    public function getLatestDate(int $ppf, string $encoder, string $defectName)
    {
        return DefectInsp::where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->where('defect', $defectName)
            ->max('created_at');
    }

    public function getInspectorName(string $encoder)
    {
        return DefectInsp::where('inspectorId', $encoder)
            ->value('inspName');
    }

    public function getSmallDefects(int $ppf, string $encoder, string $defectName, string $Process)
    {
        return SmallInsp::selectRaw('small_defect, SUM(qty) as total_qty')
            ->where('ppfno', $ppf)
            ->where('inspectorId', $encoder)
            ->where('large_defect', $defectName)
            ->where('process', $Process) // ✅ NEW
            ->groupBy('small_defect')
            ->get();
    }

    public function getSmallDefectsForPpf(int $ppf)
{
    return SmallInsp::selectRaw('large_defect, process, inspectorId, small_defect, SUM(qty) as total_qty')
        ->where('ppfno', $ppf)
        ->groupBy('large_defect', 'process', 'inspectorId', 'small_defect')
        ->get()
        ->groupBy(fn($r) => $r->large_defect . '||' . $r->process . '||' . $r->inspectorId);
}

    public function getDefectsGrouped(int $ppf)
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

    public function getExpected(int $ppf)
    {
        return DB::table('計量１')->where('流動NO', $ppf)->first();
    }

    public function getDefectforMain()
    {
        return DB::table('DefectMatrix2')
            ->select('LargeDefect')
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();
    }
    public function getLargeDefectTypes(): Collection
    {
        return Defects::select('LargeDefect')
            ->distinct()
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();
    }
}