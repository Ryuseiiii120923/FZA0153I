<?php

namespace App\Services;

use App\Repositories\DefectRepository;
use App\Repositories\DoneReworkRepository;
use App\Repositories\PPFRepository;
use App\Repositories\ReworkRepository;

class ForReworkService
{
     protected $ppfRepo, $doneReworkRepo, $defectRepo, $reworkRepo;
     public function __construct(PPFRepository $ppfRepo, DoneReworkRepository $doneReworkRepo, DefectRepository $defectRepo, ReworkRepository $reworkRepo)
    {
        $this->ppfRepo = $ppfRepo;
        $this->defectRepo = $defectRepo;
        $this->doneReworkRepo = $doneReworkRepo;
        $this->reworkRepo = $reworkRepo;
    }

     public function FetchForRework($ppf)
    {
        return $this->reworkRepo->fetchForRework($ppf);
    }

     public function FetchForAllRework()
    {
        return $this->reworkRepo->fetchForAllRework();
    }

    public function fetchGoodQty($ppf)
    {
        return $this->ppfRepo->FetchGoodQty($ppf);
    }

    public function fetchIfFlgDone($ppf)
    {
        return $this->doneReworkRepo->fetchFlag($ppf);
    }

    public function ProceedRework($ppf) {
        $this->reworkRepo->ProceedRework($ppf);
    }

}