<?php

namespace App\Models\Operator;

use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Database\Eloquent\Model;

class HfForms extends Model
{
    protected $table = "hf_forms";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RecNo';
    protected $keyType = 'string';

     protected $fillable = [
        'updated_by'
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'updated_by', '作業員CD');
    }
}
