<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddDefect extends Model
{
    protected $table = "Defect";
   public $timestamps = false;
    protected $primaryKey = 'RecNo';
    protected $fillable = [
        'PPFNo',
        'PartNo',
        'Lotno',
        'MatNo',
        'MDNo',
        'PressNo',
        'Shift',
        'Operator',
        'Total',
        'Good',
        'Defect',
        'Quantity',
        'Details',
        'InspectionDate',
        'DateEncode',
        'AutoMachine',
        'InspNo1',
        'InspNo2',
        'InspNo3',
        'InspNo4',
        'InspNo5',
        'ExcessQty',
        'LackingQty',
        'ReworkQty',
        'SampleQty',
        'Encoder',
        
    ];

     protected $casts = [
        'PPFNo' => 'integer', 
    ];
 
}
