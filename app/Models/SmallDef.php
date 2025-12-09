<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmallDef extends Model
{
    protected $table = "DefectSMALL";
    public $incrementing = false;
    public $timestamps = false;
 protected $fillable = [
        'PPFNo',
        'LargeDefect',
        'SmallDefect',
        'Qty'
    ];
}
