<?php

namespace App\Models\DoneRework;

use App\Models\Worker;
use Illuminate\Database\Eloquent\Model;

class Forms extends Model
{
    protected $table = "dr_forms";
     public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';

      public function worker()
    {
        return $this->belongsTo(Worker::class, 'hf_id', '作業員CD');
    }

     public function updatedByWorker()
    {
        return $this->belongsTo(Worker::class, 'updated_by', '作業員CD');
    }

}
