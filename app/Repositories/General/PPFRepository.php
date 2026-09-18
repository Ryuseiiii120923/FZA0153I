<?php

namespace App\Repositories\General;

use App\Models\CheckHF;
use App\Models\Forms\HF;
use App\Models\Inspector\DefectInsp;
use App\Models\Inspector\PRInsp;
use App\Models\Inspector\ReworkInsp;

class PPFRepository
{

    public function getHF(int $ppf)
    {
        return CheckHF::where('流動NO', $ppf)->first();
    }

    public function getTotalInspectionPerInspector(int $ppf, string $inspectorID)
    {
        return PRInsp::where('ppfno', $ppf)->where('InspectorID', $inspectorID)->value('total_inspect') ?? 0;
    }
    public function getTotalInspected(int $ppf)
    {
        return PRInsp::where('ppfno', $ppf)->sum('total_inspect');
    }

    public function checkPPFExistForInspector(int $ppf, string $inspectorID)
    {
        return DefectInsp::where('inspectorId', $inspectorID)->where('operation','VI')->where('ppfno', $ppf)->exists() ||
            ReworkInsp::where('inspectorId', $inspectorID)->where('operation','VI')->where('ppfno', $ppf)->exists() ||
            PRInsp::where('inspectorId', $inspectorID)->where('operation','VI')->where('ppfno', $ppf)->exists();
    }

    public function FetchGoodQty(int $ppf){
        $sum1 = HF::where('ppfno', $ppf)
            ->sum('goodQty');
        return $sum1;
    }


}
