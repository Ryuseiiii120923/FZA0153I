<?php

namespace App\Models\HF;

use Illuminate\Database\Eloquent\Model;

class Rework extends Model
{
    
     protected $table = "hf_rework";
     public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';
    protected $keyType = 'string';
    protected $fillable = [
        'hf_id',
        'rework_type',
        'qty',
        'created_at',
        'updated_at'
    ];
}
