<?php

namespace App\Repositories;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\CheckPPF as ModelsCheckPPF;
use App\Models\Operator\DefectInsp;
use App\Models\Operator\PRInsp;
use App\Models\Operator\ReworkInsp;
use Illuminate\Support\Facades\DB;

class PPFRepository
{
    public function getCheckPPF($ppf)
    {
        return ModelsCheckPPF::where('流動NO', $ppf)->first();
    }

    public function getHF($ppf)
    {
        return CheckHF::where('流動NO', $ppf)->first();
    }

    public function getAddDefect($ppf)
    {
        return AddDefect::where('PPFNo', $ppf)->first();
    }

    public function getTotalInspectionPerInspector($ppf, $inspectorID)
    {
        return PRInsp::where('PPFNo', $ppf)->where('InspectorID', $inspectorID)->value('total_inspect') ?? 0;
    }
    public function getTotalInspected($ppf)
    {
        return PRInsp::where('PPFNo', $ppf)->sum('total_inspect');
    }

    public function checkPPFExistForInspector($ppf, $inspectorID)
    {
        return DefectInsp::where('InspectorID', $inspectorID)->where('PPFNo', $ppf)->exists() ||
            ReworkInsp::where('InspectorId', $inspectorID)->where('PPFNo', $ppf)->exists() ||
            PRInsp::where('InspectorId', $inspectorID)->where('PPFNo', $ppf)->exists();
    }

    public function getPosture($ppf)
    {
        return DB::table('Postcure')->where('PPFNo', $ppf)->first();
    }

    public function getPCValue($partNo)
    {
        return DB::table('Seihin')
            ->where('品番', $partNo)
            ->value('PC') ?? 0;
    }

    public function getReinspect($ppf)
    {
        return DB::table('FinalInspection')
            ->where('PPFNo', $ppf)
            ->orderBy('ReInspect')
            ->get();
    }

    public function getTotalReworkPending(){
        return DB::table('Inspector_Rework')
        ->select('PPFNo',DB::raw('SUM(Quantity) as total_rework'), 'DateEncode')
        ->where('FlgDone', 0)
        ->groupBy('PPFNo', 'DateEncode')
        ->get();
    }

}
