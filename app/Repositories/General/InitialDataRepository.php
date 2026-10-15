<?php

namespace App\Repositories\General;

use App\Models\AddDefect;
use App\Models\GL\InspectionGroups;
use App\Models\MoldingResult;
use App\Models\ViCheck;
use Illuminate\Support\Facades\DB;

class InitialDataRepository
{
    public function getPosture(int $ppf)
    {
        return DB::table('Postcure')->where('PPFNo', $ppf)->first();
    }

    public function getPCValue(string $partNo)
    {
        return DB::table('Seihin')
            ->where('品番', $partNo)
            ->value('PC') ?? 0;
    }

    public function getReinspect(int $ppf)
    {
        return DB::table('FinalInspection')
            ->where('PPFNo', $ppf)
            ->orderBy('ReInspect')
            ->get();
    }

    public function isPPFExist(int $ppf)
    {
        return DB::table('Defect')->where('PPFNo', $ppf)->exists();
    }

    public function fetchComputedFieldQty(int $ppf)
    {
       return AddDefect::select('ExcessQty as excssQty', 'LackingQty as lackQty', 'ReworkQty as reworkQty', 'SampleQty as sampleQty','Good as goodQty')->where('PPFNo', $ppf)->first();
    }

    public function getPpfDetails(int $ppf)
    {
        return MoldingResult::where('流動NO', $ppf)->first();
    }

      public function isLockedByFinalInspection(int $ppf): bool
    {
        return DB::table('FinalInspection')
            ->where('PPFNO', $ppf)
            ->where(function ($q) {
                $q->whereNull('ReInspect')
                    ->orWhere('ReInspect', '0')
                    ->orWhere('ReInspect', '');
            })
            ->exists();
    }

    public function loadPlant(int $ppf){
        return ViCheck::where('PPFNo', $ppf)->value('Plant');
    }

    public function fetchGroup(int $ppf){
        return InspectionGroups::where('ppfno',$ppf)->where('operation','VI')->value('group_name');
    }

    public function fetchFooterDetails(int $ppf){
        return AddDefect::select('InspectionDate','Encoder','DateEncode','AutoMachine','Details')->where('PPFNo',$ppf)->first();
    }
}
