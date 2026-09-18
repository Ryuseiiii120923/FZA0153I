<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rework extends Model
{
    protected $connection = 'PRecord';
     protected $table = "vi_rework";

     public function hfForm(): BelongsTo
{
    return $this->belongsTo(HF::class, 'ppfno', 'ppfno');
}
}
