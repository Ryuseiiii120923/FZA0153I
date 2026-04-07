<?php 

namespace App\Services;

use App\Repositories\DoneReworkRepository;
use Illuminate\Support\Facades\DB;

class DoneReworkService
{
    protected $doneReworkRepo;

    public function __construct(DoneReworkRepository $doneReworkRepo)
    {
        $this->doneReworkRepo = $doneReworkRepo;
    }
     public function saveDoneRework($data)
    {
        return DB::transaction(function () use ($data) {
            $hfId = $data['hf_id'];
            $this->doneReworkRepo->saveMainForm($data);
            $this->doneReworkRepo->saveDefects($hfId, $data['defects'] ?? [], $data['ppfno'], $data['encoder']);
            $this->doneReworkRepo->saveReworks($hfId, $data['reworks'] ?? [], $data['ppfno'], $data['encoder']);
            $this->doneReworkRepo->saveSmallDefects($hfId, $data['smalldefects'] ?? [], $data['ppfno'], $data['encoder']);
        });
    }
}