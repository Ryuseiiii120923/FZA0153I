<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddRwk extends Model
{
    protected $table = 'DefectRWK';

    protected $fillable = [
        'PPFNo',
        'Defect',
        'Quantity',
        'HFNo',
        'TotalInspQty'
    ];
    public $timestamps = false;
}
