<?php

namespace App\Models\HF;

use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    protected $table = "hf_defect";
     public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';
    protected $keyType = 'string';
    protected $fillable = [
        'hf_id',
        'defect',
        'qty',
        'updated_by',
        'ppfno',
        'inspect_REC'
    ];

    public function children(){
        return $this->hasMany(SmallDefect::class, 'large_defect', 'defect');
    }
}
