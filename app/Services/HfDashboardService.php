<?php

namespace App\Services;

use App\Repositories\HfDashboardRepository;

class HfDashboardService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new HfDashboardRepository();
    }

    public function fetchHfReworkData()
    {
        //check if there are any pending rework items
        //check if there are any done rework items
        //return the data to the component
        //set the status Not confirmed for pending rework and confirmed for done rework

        $pendingRework = $this->repository->fetchForRework();
        $doneRework = $this->repository->fetchDoneRework();
        $data = [];
        foreach ($pendingRework as $item) {
            $data[] = [
                'ppfno' => $item->PPFNo,
                'total_rework' => (int) $item->total_rework,
                'status' => 'Not Confirmed'
            ];
        }
        foreach ($doneRework as $item) {
            $data[] = [
                'ppfno' => $item->PPFNo,
                'total_rework' => (int) $item->total_rework,
                'status' => 'Confirmed'
            ];
        }
        return $data;
    }

    public function fetchDefectsByPPF($ppf)
    {
        $defect = $this->repository->fetchDefectsByPPF($ppf);
        if ($defect) {
            // Main defect list
            $defectsData = $defect->map(function ($item) {
                return [
                    'type' => $item->defect,
                    'qty'  => (int) $item->qty
                ];
            })->filter(fn($d) => $d['qty'] > 0)
                ->values()
                ->toArray();

            $last = end(array: $defectsData);
            $lastdef = $last['type'] ?? null;
            $lastqty = $last['qty'] ?? null;

            // Group small defects by large defect
            foreach ($defect as $item) {
                $large = $item->defect;

                $smallDef = $this->repository->fetchSmallDefectsByPPF($ppf);

                foreach ($smallDef as $s) {
                    $large = $s->large_defect;
                }

                $smalldefectsData[$large] = $smallDef->map(function ($s) {
                    return [
                        'SelectedLargeDefect' => $s->large_defect,
                        'type' => $s->small_defect,
                        'qty'  => $s->qty
                    ];
                })->toArray();
            }
        }

        return [
            'defect' => $defectsData ?? [],
            'smallDefects' => $smalldefectsData ?? [],
            'lastDefect' => $lastdef,
            'lastQty' => $lastqty,
            'hf_id' => $defect->first()->hf_id ?? null
        ];
    }

    public function fetchReworksByPPF($ppf)
    {
        $rework = $this->repository->fetchReworksByPPF($ppf);
        $hf_id = $rework->first()->hf_id ?? null;
        if ($rework) {
            $reworksData = $rework->map(function ($item) {
                return [
                    'type' => $item->rework_type,
                    'hfno' => $item->hfno,
                    'totalinsp' => $item->totalinsp,
                    'quan'  => (int) $item->qty
                ];
            })->filter(fn($d) => $d['quan'] > 0)
                ->values()
                ->toArray();

            return [
                'reworks' => $reworksData ?? [],
                'hf_id' => $hf_id
            ];
        }
        return [
            'reworks' => [],
            'hf_id' => $hf_id ?? null
        ];
    }

    public function deleteDoneRework($ppf){
        $result = $this->repository->deleteDoneReworkByPPF($ppf);
        if($result){
            $this->repository->updateflagdoneforDelete($ppf);
            return true;
        } else {
            throw new \Exception("Failed to delete done rework for PPF: " . $ppf);
        }
    }
}
