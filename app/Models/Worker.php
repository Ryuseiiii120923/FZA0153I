<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Worker extends Authenticatable
{
    protected $table = "作業員";
    protected $primaryKey = '社員CD';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        '社員CD'
    ];

    public function getAuthIdentifier()
    {
        return (int) $this->getKey();
    }

    public function employeeName()
    {
        return $this->hasOne(WorkerName::class, '社員CD', '社員CD');
    }
}
