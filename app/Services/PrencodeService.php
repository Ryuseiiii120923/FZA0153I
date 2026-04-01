<?php

namespace App\Services;

class PrencodeService
{
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
}
