<?php

namespace App\Models\HF;

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
        'updated_at'
    ];
}
