<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ReworkRepository
{
    public function fetchForRework($ppf){
        return DB::table('hf_rework as r')
        ->join('hf_forms as f', 'r.ppfno', '=', 'f.ppfno')
        ->select('r.PPFNo', DB::raw('SUM(r.qty) as total_rework'))
        ->where('r.FlgDone', 0)
        ->where('r.ProceedToRework', 0)
        ->where('f.finishingProcedure', 'Hand Finishing')
        ->groupBy('r.PPFNo')
        ->get();
    }

    public function fetchForAllRework(){
        return DB::table('hf_rework')
        ->select('PPFNo', DB::raw('SUM(qty) as total_rework'))
        ->where('FlgDone', 0)
        ->where('ProceedToRework', 1)
        ->groupBy('PPFNo')
        ->get();
    }

    public function ProceedRework($ppf) {
        DB::table('hf_rework')
        ->where('PPFNo', $ppf)
        ->update(['ProceedToRework' => 1]);
    }

    public function fetchReworksGl($ppf){
        return DB::table('Inspector_Rework')
        ->where('PPFNo', $ppf)
        ->get();
    }
}