<?php

namespace App\Models\TotalForPR;

use Illuminate\Database\Eloquent\Model;

class TotalOfProcessSmallDefect extends Model
{
    protected $table = 'total_of_process_small_defects';

    protected $fillable = [
        'ppfno',
        'large_defect',
        'small_defect',
        'total_qty',
    ];
}