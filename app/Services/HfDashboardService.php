<?php

namespace App\Services;

use App\Repositories\HfDashboardRepository;
use Illuminate\Support\Facades\DB;

class HfDashboardService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new HfDashboardRepository();
    }


    public function fetchHfReworkData()
    {
        $reworks = DB::table('hf_rework')
            ->select(
                'PPFNo',
                'ReworkNo',
                'FlgDone',
                DB::raw('SUM(qty) as total_rework')
            )
            ->where('ProceedToRework', 1)
            ->groupBy('PPFNo', 'ReworkNo', 'FlgDone')
            ->orderBy('PPFNo')
            ->orderBy('ReworkNo')
            ->get();

        $data = [];

        foreach ($reworks as $item) {
            $data[] = [
                'ppfno' => $item->PPFNo,
                'rework_no' => $item->ReworkNo,
                'total_rework' => (int) $item->total_rework,
                'status' => (int) $item->FlgDone === 1
                    ? 'Confirmed'
                    : 'Not Confirmed'
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

    public function deleteDoneRework($ppf,$reworkNo)
    {
        $result = $this->repository->deleteDoneReworkByPPF($ppf,$reworkNo);
    
        if ($result) {
 
            $this->repository->updateflagdoneforDelete($ppf,$reworkNo);
            return true;
        } else {
            throw new \Exception("Failed to delete done rework for PPF: " . $ppf);
        }
    }
}
