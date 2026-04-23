<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class HfDashboardRepository
{
    public function fetchForRework()
    {
        return DB::table('hf_rework')
            ->select('PPFNo', DB::raw('SUM(qty) as total_rework'))
            ->where('FlgDone', 0)
            ->where('ProceedToRework', 1)
            ->groupBy('PPFNo')
            ->get();
    }

    public function fetchDoneRework()
    {
        return DB::table('hf_rework')
            ->select('PPFNo', DB::raw('SUM(qty) as total_rework'))
            ->where('FlgDone', 1)
            ->where('ProceedToRework', 1)
            ->groupBy('PPFNo')
            ->get();
    }

    public function updateflagdoneforDelete($ppf)
    {
        return DB::table('hf_rework')
            ->where('PPFNo', $ppf)
            ->update(['FlgDone' => 0]);
    }

    public function fetchDefectsByPPF($ppf)
    {
        return DB::table('dr_defect')
            ->select('defect', 'qty', 'hf_id')
            ->where('PPFNo', $ppf)
            ->get();
    }

    public function fetchSmallDefectsByPPF($ppf)
    {
        return DB::table('dr_small')
            ->select('large_defect', 'small_defect', 'qty')
            ->where('PPFNo', $ppf)
            ->get();
    }

    public function fetchReworksByPPF($ppf)
    {
        return DB::table('dr_rework')
            ->select('rework_type', 'qty', 'hf_id', 'ppfno', 'hfno', 'totalinsp')
            ->where('PPFNo', $ppf)
            ->get();
    }

    public function deleteDoneReworkByPPF($ppf)
    {
        $tables = ['dr_forms', 'dr_defect', 'dr_small'];

        DB::transaction(function () use ($tables, $ppf) {
            foreach ($tables as $table) {
                DB::table($table)
                    ->where('ppfno', $ppf)
                    ->delete();
            }
        });

         $tables = ['Inspector_Defect','Inspector_Small'];

        DB::transaction(function () use ($tables, $ppf) {
            foreach ($tables as $table) {
                DB::table($table)
                    ->where('PPFNo', $ppf)
                    ->where('EncodeProcess', 'reRework')
                    ->delete();
            }
        });
        return true;
    }
}
