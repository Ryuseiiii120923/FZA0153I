<?php

namespace App\Services;

use App\Repositories\DefectRepository;

class DefectService
{
    protected $repo;

    public function __construct(DefectRepository $repo)
    {
        $this->repo = $repo;
    }

    public function loadDefectsGL($ppf)
    {
        $defects = [];
        $smallDefects = [];

        $rows = $this->repo->getDefectsGrouped($ppf);

        // ✅ ONE query replaces N getSmallDefects() calls
        $allSmalls = $this->repo->getSmallDefectsForPpf($ppf);

        foreach ($rows as $row) {
            if ((int)$row->total_qty <= 0) continue;

            $defects[] = [
                'operatorid'   => $row->InspectorID,
                'operatorname' => $row->insp_name,
                'type'         => $row->Defect,
                'qty'          => (int)$row->total_qty,
                'dateEncode'   => $row->latest_date,
                'Process'      => $row->Process,
            ];

            // ✅ Lookup from in-memory map — no query fired
            $key = $row->Defect . '||' . $row->Process . '||' . $row->InspectorID;

            foreach ($allSmalls->get($key, []) as $s) {
                $smallDefects[$row->Defect][$row->Process][$row->InspectorID][] = [
                    'type' => $s->SmallDefect,
                    'qty'  => (int)$s->total_qty,
                ];
            }
        }

        // ✅ Normalize (OPTIONAL — now includes Process)
        $normalized = [];

        foreach ($defects as $d) {
            $key = strtolower(trim($d['type'])) . '_' . $d['Process'];

            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'type' => $d['type'],
                    'qty'  => 0,
                    'Process' => $d['Process']
                ];
            }

            $normalized[$key]['qty'] += $d['qty'];
        }

        $defectPayload = array_map(fn($d) => [
            'newDefect' => $d['type'],
            'newQuan'   => $d['qty'],
            'Process' => $d['Process'], // ✅ NEW
            'action'    => '',
        ], array_values($normalized));

        return [
            'defects'       => $defects,
            'smallDefects'  => $smallDefects,
            'payload'       => $defectPayload,
            'totalQty'      => collect($defects)->sum('qty'),
            'inspectors'    => collect($defects)->pluck('operatorid')->unique()->values(),
            'last'          => end($defects)
        ];
    }
}
