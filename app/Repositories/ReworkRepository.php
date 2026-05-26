<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReworkRepository
{
    public function fetchForRework($ppf)
    {
        return DB::table('hf_rework as r')
            ->select('r.PPFNo', DB::raw('SUM(r.qty) as total_rework'))
            ->where('r.FlgDone', 0)
            ->where('r.ProceedToRework', 0)
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
        return DB::table('hf_rework')
            ->select('PPFNo', DB::raw('SUM(qty) as total_rework'))
            ->where('FlgDone', 0)
            ->where('ProceedToRework', 1)
            ->groupBy('PPFNo')
            ->get();
    }

    public function ProceedRework($ppf)
    {
        $lastReworkNo = DB::table('hf_rework')
            ->where('ppfno', $ppf)
            ->max('ReworkNo');

        $newReworkNo = ($lastReworkNo ?? 0) + 1;

        DB::table('hf_rework')
            ->where('PPFNo', $ppf)
            ->where('ReworkNo', NULL)
            ->update(['ProceedToRework' => 1, 'ReworkNo' => $newReworkNo]);
    }

    public function fetchReworksGl($ppf)
    {
        return DB::table('Inspector_Rework')
            ->where('PPFNo', $ppf)
            ->get();
    }
}
