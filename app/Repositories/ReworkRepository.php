<?php

namespace App\Repositories;

use App\Models\Forms\Rework;
use App\Models\Inspector\ReworkInsp;
use Illuminate\Support\Facades\DB;

class ReworkRepository
{
    public function fetchForRework($ppf)
    {
        return DB::connection('PRecord')->table('vi_rework as r')
            ->select('r.PPFNo', DB::raw('SUM(r.qty) as total_rework'))
            ->where('r.FlgDone', 0)
            ->where('r.proceedToWork', 0)
            ->where('r.ppfno', $ppf)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('hf_forms as f')
                    ->whereColumn('f.ppfno', 'r.ppfno')
                    ->where('f.finishingProcedure', 'Hand Finishing');
            })
            ->groupBy('r.PPFNo')
            ->get();
    }

    public function fetchForAllRework()
    {
        return Rework::select('PPFNo', DB::raw('SUM(qty) as total_rework'))
            ->where('FlgDone', 0)
            ->where('proceedToWork', 1)
            ->groupBy('PPFNo')
            ->get();
    }

    public function ProceedRework($ppf)
    {
        $lastReworkNo = Rework::where('ppfno', $ppf)
            ->max('ReworkNo');

        $newReworkNo = ($lastReworkNo ?? 0) + 1;

        return Rework::where('PPFNo', $ppf)
            ->where('ReworkNo', NULL)
            ->update(['proceedToWork' => 1, 'ReworkNo' => $newReworkNo]);
    }

    public function fetchReworksGl($ppf)
    {
        return ReworkInsp::where('PPFNo', $ppf)->where('operation','VI')
            ->get();
    }
}
