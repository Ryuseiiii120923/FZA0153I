<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;

class SmallDefect extends Model
{
    protected $connection = 'PRecord';
     protected $table = "vi_small";
     public $timestamps = false;
}
