<?php

namespace App\Models\Inspector;

use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Database\Eloquent\Model;

class HfForms extends Model
{
    protected $table = "hf_forms";
    protected $keyType = 'string';

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'updated_by', '作業員CD');
    }
}
