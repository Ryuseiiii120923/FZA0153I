<?php

namespace App\Models\Inspector;

use Illuminate\Database\Eloquent\Model;

class DefectInsp extends Model
{
    protected $connection = 'PRecord';
    protected $table = "inspector_defect";
}

