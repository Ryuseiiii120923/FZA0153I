<?php

namespace App\Models\TotalForPR;

use Illuminate\Database\Eloquent\Model;

class TotalOfProcessLargeDefect extends Model
{
    protected $table = 'total_of_process_large_defects';

    protected $fillable = [
        'ppfno',
        'defect',
        'total_qty',
    ];
}