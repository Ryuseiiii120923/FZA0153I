<?php

namespace App\Repositories\Operator;

use App\Models\Forms\Rework;
use App\Models\Forms\Defect;
use App\Models\Forms\HF;
use App\Models\Forms\SmallDefect;
use App\Models\Worker;
use App\Models\WorkerName;

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

    public function findWorkerByCode(string $code)
{
    return Worker::where('作業員CD', $code)->first();
}

public function findWorkerName(string $employeeCode)
{
    return WorkerName::where('社員CD', $employeeCode)->first();
}


}