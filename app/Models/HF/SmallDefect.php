<?php

namespace App\Models\HF;

use Illuminate\Database\Eloquent\Model;

class SmallDefect extends Model
{
     protected $table = "hf_small";
     public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';
    protected $keyType = 'string';
    protected $fillable = [
        'hf_id',
        'large_defect',
        'small_defect',
        'qty',
        'created_at',
        'updated_at'
    ];
}
