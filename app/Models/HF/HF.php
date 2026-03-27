<?php

namespace App\Models\HF;

use App\Models\Worker;
use Illuminate\Database\Eloquent\Model;

class HF extends Model
{
    protected $table = "hf_forms";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';
    protected $keyType = 'string';
    protected $fillable = [
        'hf_id',
        'total_inspect',
        'created_at',
        'updated_by',
        'ppfno',
        'updated_date',
        'inspect_REC'
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'hf_id', '作業員CD');
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
