<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    protected $connection = 'PRecord';
    protected $table = "vi_defect";
    public $timestamps = false;
    public function children(){
        return $this->hasMany(SmallDefect::class, 'large_defect', 'defect');
    }
}
