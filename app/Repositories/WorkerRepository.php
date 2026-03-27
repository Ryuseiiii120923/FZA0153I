<?php

namespace App\Repositories;

use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Support\Facades\Auth;

class WorkerRepository{
    public function getWorkerName($worker){
        return WorkerName::where('社員CD', $worker)->first();
    }

    public function checkExistWorkerVI($worker){
        return Worker::where('作業員CD', $worker)
            ->where('区分', 1)
            ->first();
    }
}