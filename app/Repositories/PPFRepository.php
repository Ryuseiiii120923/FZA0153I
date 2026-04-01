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

    public function FetchGoodQty($ppf){
        $sum1 = DB::table('hf_forms')
        ->where('ppfno', $ppf)
        ->sum('GoodQty');

        // $sum2 = DB::table('hf_forms')
        // ->where('ppfno', $ppf)
        // ->sum('GoodQty');

        return $sum1;
    }

    public function getPPF($ppf){
        return DB::table('Defect')->where('PPFNo', $ppf)->first();
    }

}
