<?php

namespace App\Repositories\General;

use App\Models\Worker;
use App\Models\WorkerName;

class WorkerRepository{
    public function getWorkerName(string $workerId){
        return WorkerName::where('社員CD', $workerId)->first();
    }

    public function checkExistWorkerVI(string $workerId){
        return Worker::where('作業員CD', $workerId)
            ->where('区分', 1)
            ->first();
    }
}