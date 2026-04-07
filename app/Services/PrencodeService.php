<?php

namespace App\Services;

use App\Repositories\PrencodeRepository;

class PrencodeService
{
    protected $prencodeRepo;
    public function __construct(PrencodeRepository $prencodeRepo)
    {
        $this->prencodeRepo = $prencodeRepo;
    }

    public function calculateGoodQty($totalInspect, $defects, $reworks)
    {
        $defectQty = collect($defects)->sum('qty');
        $reworkQty = collect($reworks)->sum('quan');

        $totalNg = $defectQty + $reworkQty;

        return $totalInspect - $totalNg;
    }


    public function handleReworks(array $currentReworks, array $reworksData): array
    {
        $type = $reworksData['newtype'] ?? $reworksData['type'] ?? null;
        if (!$type) {
            return [
                'reworks' => $currentReworks,
                'total' => collect($currentReworks)->sum('quan')
            ];
        }

        $normalized = [
            'hfno'      => $reworksData['newhfno'] ?? $reworksData['hfno'] ?? '',
            'type'      => strtoupper(trim($type)),
            'quan'      => (int) ($reworksData['newquan'] ?? $reworksData['quan'] ?? 0),
            'totalinsp' => (int) ($reworksData['totalinsp'] ?? 0),
        ];

        $collection = collect($currentReworks);

        $collection = $collection->reject(
            fn($r) =>
            $r['hfno'] === $normalized['hfno'] &&
                $r['type'] === $normalized['type']
        );

        if (($reworksData['action'] ?? '') !== 'delete') {
            $collection->push($normalized);
        }

        $collection = $collection->values();

        return [
            'reworks' => $collection->toArray(),
            'total'   => $collection->sum('quan')
        ];
    }

    public function handleDefect($currentDefects, $payload)
    {
        if (!$payload) {
            return $currentDefects;
        }

        $defectData = $payload['defectData'] ?? $payload;

        $newDefect = trim($defectData['newDefect'] ?? '');
        $newQuan   = (float)($defectData['newQuan'] ?? 0);
        $action    = $defectData['action'] ?? 'add';

        if (!$newDefect) {
            return $currentDefects;
        }

        // 🔵 Normalize existing defects
        $normalized = [];

        foreach ($currentDefects as $def) {
            $type = $def['type'] ?? $def['newDefect'] ?? '';
            $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? 0);

            if ($type === '') continue;

            $key = strtolower($type);

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
            } else {
                $normalized[$key] = [
                    'type' => $type,
                    'qty'  => (int) $qty
                ];
            }
        }

        $key = strtolower($newDefect);
        if ($action === 'delete') {
            unset($normalized[$key]);
            return array_values($normalized);
        }
        if ($action === 'update') {
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] = $newQuan;
            }
        } else {
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $newQuan;
            } else {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }

        return array_values($normalized);
    }

    public function LoadDefectsPrencode($ppf, $inspectorID)
    {
        $fetchdefect = $this->prencodeRepo->fetchDefects($ppf, $inspectorID);

        if ($fetchdefect->isEmpty()) {
            return [
                'defects' => [],
                'smallDefects' => [],
                'lastdef' => null,
                'lastqty' => null
            ];
        }


        $defects = $fetchdefect->map(function ($item) {
            return [
                'type' => $item->Defect,
                'qty'  => (int) $item->Quantity
            ];
        })->filter(fn($d) => $d['qty'] > 0)
            ->values()
            ->toArray();

        $last = end($defects);
        $lastdef = $last['type'] ?? null;
        $lastqty = $last['qty'] ?? null;
        $smalldefects = [];
        $allSmall = $this->prencodeRepo->fetchSmallDefects($ppf, $inspectorID);


        $smalldefects = [];

        foreach ($allSmall as $s) {
            $smalldefects[$s->LargeDefect][] = [
                'SelectedLargeDefect' => $s->LargeDefect,
                'type' => $s->SmallDefect,
                'qty'  => $s->Qty
            ];
        }

        return [
            'defects' => $defects,
            'smallDefects' => $smalldefects ?? [],
            'lastdef' => $lastdef,
            'lastqty' => $lastqty
        ];
    }

    public function LoadReworksPrencode($ppf, $inspectorID)
    {
        $fetchrework = $this->prencodeRepo->fetchReworks($ppf, $inspectorID);
        if ($fetchrework->isEmpty()) {
            return [
                'reworks' => [],
                'totalNgRework' => 0
            ];
        }

        $reworks = $fetchrework->map(function ($item) {
            return [
                'hfno'      => $item->HFNo,
                'totalinsp' => $item->TotalInsp,
                'type'      => strtoupper(trim($item->Type)),
                'quan'      => (int) $item->Quantity
            ];
        })->values()->toArray();

        $totalNgRework = collect($reworks)->sum('quan');

        return [
            'reworks' => $reworks,
            'totalNgRework' => $totalNgRework
        ];
    }
}
