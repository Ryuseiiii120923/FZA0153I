<?php

namespace App\Models\GL;

use App\Models\Worker;
use Illuminate\Database\Eloquent\Model;

class EnrollOperator extends Model
{
    protected $connection = 'PRecord';
    protected $table = "operator_enroll";
    protected $primaryKey = 'OperatorID';

    protected $fillable = [
        'OperatorID',
        'OperatorName',
        'GLID',
        'ProgramID',
    ];

    public function workerMaster()
    {
        return $this->belongsTo(Worker::class, 'OperatorID', '作業員CD');
    }
}
