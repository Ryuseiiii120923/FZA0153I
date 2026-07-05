<?php

namespace App\Repositories;

use App\Models\CheckHF;
use App\Models\CheckPPF as ModelsCheckPPF;
use App\Models\Forms\HF;
use App\Models\Inspector\DefectInsp;
use App\Models\Inspector\PRInsp;
use App\Models\Inspector\ReworkInsp;
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
        return PRInsp::where('ppfno', $ppf)->where('InspectorID', $inspectorID)->value('total_inspect') ?? 0;
    }
    public function getTotalInspected($ppf)
    {
        return PRInsp::where('ppfno', $ppf)->sum('total_inspect');
    }

    public function checkPPFExistForInspector($ppf, $inspectorID)
    {
        return DefectInsp::where('InspectorID', $inspectorID)->where('operation','VI')->where('ppfno', $ppf)->exists() ||
            ReworkInsp::where('InspectorId', $inspectorID)->where('operation','VI')->where('ppfno', $ppf)->exists() ||
            PRInsp::where('InspectorId', $inspectorID)->where('operation','VI')->where('ppfno', $ppf)->exists();
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
        $sum1 = HF::where('ppfno', $ppf)
            ->sum('goodQty');


        return $sum1;
    }

    public function getPPF($ppf){
        return DB::table('Defect')->where('PPFNo', $ppf)->exists();
    }

}
