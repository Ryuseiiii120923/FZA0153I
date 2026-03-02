<?php

namespace App\Models\Operator;

use Illuminate\Database\Eloquent\Model;

class PRInsp extends Model
{
    protected $table = "Inspector_PR";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'RecNo';
    protected $keyType = 'string';
    protected $fillable = [
        'InspectorID',
        'PPFNo',
        'total_inspect',
        'DateEncode',
        'Process'
    ];
}
