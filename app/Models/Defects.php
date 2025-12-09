<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Defects extends Model
{
    protected $table = "DefectMatrix2";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'LargeDefect';
    protected $keyType = 'string';
    protected $fillable = [
        'LargeDefect',
    ];

    public function children(){
        return $this->hasMany(SmallDef::class, 'LargeDefect','LargeDefect');
    }
}
