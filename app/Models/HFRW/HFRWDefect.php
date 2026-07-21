<?php

namespace App\Models\HFRW;

use Illuminate\Database\Eloquent\Model;

class HFRWDefect extends Model
{
    protected $table = "dr_defect";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';
    protected $keyType = 'string';

     public function children(){
        return $this->hasMany(HFRWSmallDefect::class, 'large_defect', 'defect');
    }
}
