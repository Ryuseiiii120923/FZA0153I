<?php

namespace App\Models\DoneRework;

use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Database\Eloquent\Model;

class Forms extends Model
{
    protected $connection = 'PRecord';
    protected $table = "hfrw_forms";

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'hfid', '作業員CD');
    }

    public function updatedByWorker()
    {
        return $this->belongsTo(Worker::class, 'updated_by', '作業員CD');
    }

    public function updatedByEncoder()
    {
        return $this->belongsTo(WorkerName::class, 'updated_by', '社員CD');
    }
}
