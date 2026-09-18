<?php

namespace App\Repositories\General;

use App\Models\AddDefect;
use App\Models\MoldingResult;
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

    public function fetchMainTable(int $ppf)
    {
       return AddDefect::where('PPFNo', $ppf)->first();
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
}
