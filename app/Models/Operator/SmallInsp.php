<?php

namespace App\Models\Operator;

use Illuminate\Database\Eloquent\Model;

class SmallInsp extends Model
{
     protected $table = "Inspector_Small";
    public $incrementing = false;
    public $timestamps = false;
 protected $fillable = [
        'PPFNo',
        'LargeDefect',
        'SmallDefect',
        'Qty',
        'InspectorID'
    ];
}
