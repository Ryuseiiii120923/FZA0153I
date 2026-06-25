<?php

namespace App\Models\Operator;

use Illuminate\Database\Eloquent\Model;

class DefectInsp extends Model
{
    protected $table = "Inspector_Defect";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RecNo';
    protected $keyType = 'string';
    protected $fillable = [
        'InspectorID',
        'PPFNo',
        'Defect',
        'Quantity',
        'DateEncode',
        'insp_name',
        'Process'
    ];
}

