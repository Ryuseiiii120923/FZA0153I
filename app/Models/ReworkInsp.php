<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReworkInsp extends Model
{
     protected $table = "Inspector_Rework";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RecNo';
    protected $keyType = 'string';
    protected $fillable = [
        'HFNo',
        'PPFNo',
        'Defect',
        'Quantity',
        'TotalInspQty',
        'DateEncode',
        'InspectorID',
        'insp_name',
        'Process'
    ];

}
