<?php

namespace App\Services;

use App\Models\AddDefect;
use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
use App\Models\HF\SmallDefect;
use App\Models\TotalForPR\TotalOfProcessLargeDefect;
use App\Models\TotalForPR\TotalOfProcessRework;
use App\Models\TotalForPR\TotalOfProcessSmallDefect;
use App\Models\TotalForPR\TotalOfProcessSummary;

class TotalofProcessService
{
    /**
     * Calculate total large defect quantities from $rows (any process type).
     * Returns: [['defect' => string, 'total_qty' => int], ...]
     */
    public function calculateTotalLargeDefects(array $rows): array
    {
        return collect($rows)
            ->flatMap(fn($row) => collect($row['defects'])
                ->filter(fn($d) => $d['small_category'] === null) // sentinel entries only
                ->map(fn($d) => [
                    'defect' => $d['large_category'],
                    'qty'    => $d['large_qty'],
                ])
            )
            ->groupBy('defect')
            ->map(fn($group, $defect) => [
                'defect'    => $defect,
                'total_qty' => $group->sum('qty'),
            ])
            ->values()
            ->toArray();
    }
 
    /**
     * Calculate total small defect quantities from $rows (any process type).
     * Returns: [['large_defect' => string, 'small_defect' => string, 'total_qty' => int], ...]
     */
    public function calculateTotalSmallDefects(array $rows): array
    {
        return collect($rows)
            ->flatMap(fn($row) => collect($row['defects'])
                ->filter(fn($d) => $d['small_category'] !== null)
                ->map(fn($d) => [
                    'large_defect' => $d['large_category'],
                    'small_defect' => $d['small_category'],
                    'qty'          => $d['small_qty'],
                ])
            )
            ->groupBy(fn($d) => $d['large_defect'] . '||' . $d['small_defect'])
            ->map(fn($group) => [
                'large_defect' => $group->first()['large_defect'],
                'small_defect' => $group->first()['small_defect'],
                'total_qty'    => $group->sum('qty'),
            ])
            ->values()
            ->toArray();
    }
 
    /**
     * Calculate total rework quantities from $rows (any process type).
     * Returns: [['rework_type' => string, 'total_qty' => int], ...]
     */
    public function calculateTotalRework(array $rows): array
    {
        return collect($rows)
            ->flatMap(fn($row) => collect($row['reworks'])
                ->map(fn($r) => [
                    'rework_type' => $r['type'],
                    'qty'         => $r['qty'],
                ])
            )
            ->groupBy('rework_type')
            ->map(fn($group, $type) => [
                'rework_type' => $type,
                'total_qty'   => $group->sum('qty'),
            ])
            ->values()
            ->toArray();
    }
 
    /**
     * Calculate total good/ng from $rows (any process type).
     * Returns: ['total_good' => int, 'total_ng' => int]
     */
    public function calculateTotalGoodNg(array $rows): array
    {
        $rows = collect($rows);
 
        return [
            'total_good' => $rows->sum('total_good_qty'),
            'total_ng'   => $rows->sum('total_ng_qty'),
        ];
    }
 
    /**
     * Persist all totals to DB, computed from $rows.
     */
    public function AddToDb(string $ppf, array $rows): void
    {
        $goodNg      = $this->calculateTotalGoodNg($rows);
        $denominator = $goodNg['total_good'] + $goodNg['total_ng'];
        $ngPercent   = $denominator > 0
            ? round(($goodNg['total_ng'] / $denominator) * 100, 2)
            : 0.00;
 
        TotalOfProcessSummary::updateOrCreate(
            ['ppfno' => $ppf],
            [
                'total_good' => $goodNg['total_good'],
                'total_ng'   => $goodNg['total_ng'],
                'ng_percent' => $ngPercent,
            ]
        );
 
        foreach ($this->calculateTotalLargeDefects($rows) as $item) {
            TotalOfProcessLargeDefect::updateOrCreate(
                ['ppfno' => $ppf, 'defect' => $item['defect']],
                ['total_qty' => $item['total_qty']]
            );
        }
 
        foreach ($this->calculateTotalSmallDefects($rows) as $item) {
            TotalOfProcessSmallDefect::updateOrCreate(
                [
                    'ppfno'        => $ppf,
                    'large_defect' => $item['large_defect'],
                    'small_defect' => $item['small_defect'],
                ],
                ['total_qty' => $item['total_qty']]
            );
        }
 
        foreach ($this->calculateTotalRework($rows) as $item) {
            TotalOfProcessRework::updateOrCreate(
                ['ppfno' => $ppf, 'rework_type' => $item['rework_type']],
                ['total_qty' => $item['total_qty']]
            );
        }
    }

     public function fetchRemarks($ppf){
        return AddDefect::select('Details')
            ->where('PPFNo', $ppf)
            ->whereNotNull('remarks')
            ->first();
    }
}