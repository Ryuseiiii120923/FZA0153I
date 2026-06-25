<?php

namespace App\Repositories;

use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Support\Facades\Auth;

class WorkerRepository{
    public function getWorkerName($workerId){
        return WorkerName::where('社員CD', $workerId)->first();
    }

    public function checkExistWorkerVI($workerId){
        return Worker::where('作業員CD', $workerId)
            ->where('区分', 1)
            ->first();
    }
}