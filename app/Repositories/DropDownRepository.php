<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DropDownRepository{
    public function getGroupedDefects($ppf, $inspectorId, $inspectRec)
    {
        return DB::table('hf_defect')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->where('inspect_REC', $inspectRec)
            ->get();
    }
      public function getGroupedDefectsforFinishing($ppf, $inspectorId, $inspectRec)
    {
        return DB::table('dr_defect')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->where('inspect_REC', $inspectRec)
            ->get();
    }

    public function getByPpfAndInspector($ppf, $inspectorId)
    {
        return DB::table('hf_forms')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->get();
    }

     public function getReworks($ppf, $inspectorId, $inspectRec)
    {
        return DB::table('hf_rework')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->where('inspect_REC', $inspectRec)
            ->get();
    }

     public function getSmallDefects($ppf, $inspectorId, $inspectRec)
    {
        return DB::table('hf_small')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->where('inspect_REC', $inspectRec)
            ->whereNotNull('large_defect')
            ->get();
    }
     public function getSmallDefectsforFinishing($ppf, $inspectorId, $inspectRec)
    {
        return DB::table('dr_small')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->where('inspect_REC', $inspectRec)
            ->whereNotNull('large_defect')
            ->get();
    }
      public function getByPpfAndInspectorInFinishing($ppf, $inspectorId)
    {
        return DB::table('dr_forms')->where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->get();
    }

}