<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rework extends Model
{
    protected $table="RWKDefectType";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'DefectType';
    protected $keyType = 'string';
    protected $fillable = [
        'DefectType',
    ];
}
