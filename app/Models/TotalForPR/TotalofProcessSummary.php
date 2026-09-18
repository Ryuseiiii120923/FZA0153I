<?php

namespace App\Models\TotalForPR;

use Illuminate\Database\Eloquent\Model;

class TotalOfProcessSummary extends Model
{
    protected $table = 'total_of_process_summary';

    protected $fillable = [
        'ppfno',
        'total_good',
        'total_ng',
        'ng_percent',
        'process'
    ];
}