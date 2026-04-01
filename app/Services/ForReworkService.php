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

     public function calculateGoodQtyForm(array $form): array
    {
        $defectQty = collect($form['defects'] ?? [])->sum('qty');
        $reworkQty = collect($form['rework'] ?? [])->sum('quan');

        $totalNg = $defectQty + $reworkQty;

        $goodQty = ($form['total_inspect'] ?? 0) - $totalNg;

        return [
            'goodQty' => $goodQty,
            'defectNg' => $defectQty,
            'reworkNg' => $reworkQty,
        ];
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