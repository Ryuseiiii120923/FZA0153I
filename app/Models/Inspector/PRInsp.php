<?php

namespace App\Models\Inspector;

use App\Models\GL\EnrollOperator;
use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Database\Eloquent\Model;

class PRInsp extends Model
{
    protected $connection = 'PRecord';
    protected $table = "inspector_pr";


    public function worker()
    {
        return $this->belongsTo(Worker::class, 'InspectorID', '作業員CD');
    }

    public function operatorEnroll()
{
    return $this->belongsTo(EnrollOperator::class, 'updated_by', 'GLID');
}
}
