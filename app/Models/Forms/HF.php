<?php

namespace App\Models\Forms;

use App\Models\Worker;
use Illuminate\Database\Eloquent\Model;

class HF extends Model
{
    protected $connection = 'PRecord';
    protected $table = "vi_forms";

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'inspId', '作業員CD');
    }

    public function updatedByWorker()
    {
        return $this->belongsTo(Worker::class, 'updated_by', '作業員CD');
    }

    public function defects()
    {
        return $this->hasMany(Defect::class, 'updated_by', 'updated_by');
    }
}
