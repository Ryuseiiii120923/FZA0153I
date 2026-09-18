<?php

namespace App\Models\TotalForPR;

use Illuminate\Database\Eloquent\Model;

class TotalOfProcessRework extends Model
{
    protected $table = 'total_of_process_reworks';

    protected $fillable = [
        'ppfno',
        'rework_type',
        'total_qty',
    ];
}