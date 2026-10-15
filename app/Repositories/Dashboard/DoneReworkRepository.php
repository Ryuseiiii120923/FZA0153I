<?php

namespace App\Repositories\Dashboard;

use App\Models\DoneRework\Forms;

class DoneReworkRepository
{
    public function fetchDoneRework(int $ppf)
    {
        return Forms::with(['worker.employeeName', 'updatedByWorker', 'updatedByEncoder'])
            ->select('hfid', 'total_inspect', 'updated_by', 'ppfno', 'GoodQty', 'created_at')
            ->where('ppfno', $ppf)
            ->get();
    }
}
