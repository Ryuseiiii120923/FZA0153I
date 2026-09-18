<?php

namespace App\Models\GL;

use Illuminate\Database\Eloquent\Model;

class InspectionGroups extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'inspection_groups';
}
