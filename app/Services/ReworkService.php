<?php

namespace App\Services;

use App\Repositories\ReworkRepository;

class ReworkService
{
    public function __construct(
        protected ReworkRepository $reworkRepository,
    ) {
    }

   public function getReworks($ppf)
{
    $rows = $this->reworkRepository->fetchReworksGl($ppf);

    if ($rows->isEmpty()) {
        return [
            'reworks' => [],
            'total' => 0
        ];
    }

    $reworks = $rows->map(function ($item) {
        return [
            'operatorid'   => $item->inspectorId,
            'hfno'         => $item->hfno,
            'operatorname' => $item->inspName,
            'totalinsp'    => $item->total_inspect,
            'type'         => $item->rework,
            'quan'         => (int) $item->qty,
            'dateEncode'   => $item->created_at
        ];
    })->values();

    $total = $reworks->sum('quan');

    $payload = $reworks->map(function ($r) {
        return [
            'hfno'      => $r['hfno'],
            'type'      => $r['type'],
            'quan'      => $r['quan'],
            'totalinsp' => $r['totalinsp'],
            'action'    => 'initial'
        ];
    })->values();

    return [
        'reworks' => $reworks,
        'payload' => $payload,
        'total'   => $total
    ];
}

}