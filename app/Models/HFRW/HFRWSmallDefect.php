<?php

namespace App\Models\HFRW;

use Illuminate\Database\Eloquent\Model;

class HFRWSmallDefect extends Model
{
     protected $table = "dr_small";
     public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RECNO';
    protected $keyType = 'string';

}
