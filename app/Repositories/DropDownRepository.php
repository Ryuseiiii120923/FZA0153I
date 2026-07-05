<?php

namespace App\Repositories;

use App\Models\Forms\Rework;
use App\Models\Forms\Defect;
use App\Models\Forms\HF;
use App\Models\Forms\SmallDefect;
use Illuminate\Support\Facades\DB;

class DropDownRepository{
    public function getGroupedDefects($ppf, $inspectorId, $inspectRec)
    {
        return Defect::where('ppfno', $ppf)
            ->where('inspId', $inspectorId)
            ->where('formId', $inspectRec)
            ->get();
    }

    public function getByPpfAndInspector($ppf, $inspectorId)
    {
        return HF::where('ppfno', $ppf)
            ->where('inspId', $inspectorId)
            ->get();
    }

     public function getReworks($ppf, $inspectorId, $inspectRec)
    {
        return Rework::where('ppfno', $ppf)
            ->where('inspId', $inspectorId)
            ->where('formId', $inspectRec)
            ->get();
    }

     public function getSmallDefects($ppf, $inspectorId, $inspectRec)
    {
        return SmallDefect::where('ppfno', $ppf)
            ->where('inspId', $inspectorId)
            ->where('formId', $inspectRec)
            ->whereNotNull('largeDefect')
            ->get();
    }


}