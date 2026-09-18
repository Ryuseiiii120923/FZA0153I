<?php

namespace App\Services\Defect;

use App\Repositories\Defect\DefectRepository;
use Illuminate\Support\Collection;

class DefectService
{
     public function __construct(private DefectRepository $repo) {}
    // -------------------------------------------------------------------------
    // Totals — was recomputed inline with slightly different formulas in
    // fetchDefect(), setDefects(), setSmallDefects(), addDefectFromModal(),
    // and deleteDefectArray(). Unified here on the (int)(?? 0) version.
    // -------------------------------------------------------------------------

    public function calculateTotalNg(array $defects): float
    {
        return collect($defects)->sum('qty');
    }

    public function calculateTotalSmallQuan(array $smallDefects): float
    {
        return collect($smallDefects)->flatten(1)->sum(fn($d) => (int) ($d['qty'] ?? 0));
    }

    /**
     * Case/whitespace-insensitive "does this value exist in this master
     * list column" check. Was duplicated in addDefect() (LargeDefect) and
     * addSmallDefect() (SmallDefect).
     */
    public function existsInMasterList(Collection $master, string $column, string $value): bool
    {
        return $master->pluck($column)
            ->map(fn($d) => strtolower(trim($d)))
            ->contains(strtolower(trim($value)));
    }

    // -------------------------------------------------------------------------
    // Modal pre-fill — was inline in selectLargeDefectInModal()
    // -------------------------------------------------------------------------

    /**
     * Fills in modalLargeQty / modalSmallDefects when a large defect is
     * selected in the modal: prefers a staged entry, falls back to an
     * already-saved defect.
     */
    public function prefillModalSmallDefects(
        array $modalSmallDefects,
        array $stagedDefects,
        string $largeDefect,
        array $defects,
        array $smallDefects
    ): array {
        $stagedIdx = collect($stagedDefects)->search(fn($e) => $e['type'] === $largeDefect);

        if ($stagedIdx !== false) {
            $staged = $stagedDefects[$stagedIdx];
            foreach ($modalSmallDefects as &$ms) {
                $saved = collect($staged['smallDefects'])->firstWhere('type', $ms['type']);
                if ($saved) {
                    $ms['qty'] = $saved['qty'];
                }
            }
            unset($ms);

            return [
                'modalLargeQty'     => $staged['qty'],
                'modalSmallDefects' => $modalSmallDefects,
            ];
        }

        $modalLargeQty = '';
        $existingLarge = collect($defects)->firstWhere('type', $largeDefect);
        if ($existingLarge) {
            $modalLargeQty = $existingLarge['qty'];
        }

        if (isset($smallDefects[$largeDefect])) {
            foreach ($modalSmallDefects as &$ms) {
                $saved = collect($smallDefects[$largeDefect])->firstWhere('type', $ms['type']);
                if ($saved) {
                    $ms['qty'] = $saved['qty'];
                }
            }
            unset($ms);
        }

        return [
            'modalLargeQty'     => $modalLargeQty,
            'modalSmallDefects' => $modalSmallDefects,
        ];
    }

    // -------------------------------------------------------------------------
    // Staging workflow — was inline in loadExistingDefectsToStage(),
    // stageCurrentEntry(), stageDefect(), removeStagedDefect()
    // -------------------------------------------------------------------------

    public function buildStagedListFromDefects(array $defects, array $smallDefects): array
    {
        $staged = [];

        foreach ($defects as $defect) {
            $largeType = $defect['type'] ?? null;

            if (!$largeType) {
                continue;
            }

            $staged[] = [
                'type' => $largeType,
                'qty'  => (int) ($defect['qty'] ?? 0),
                'smallDefects' => collect($smallDefects[$largeType] ?? [])
                    ->map(fn($small) => [
                        'type' => $small['type'],
                        'qty'  => (int) ($small['qty'] ?? 0),
                    ])
                    ->values()
                    ->toArray(),
            ];
        }

        return $staged;
    }

    /**
     * Filters modal small-defect rows down to the ones with a qty > 0.
     * Was duplicated in stageCurrentEntry() and stageDefect().
     */
    public function computeSmallsWithQty(array $modalSmallDefects): array
    {
        return collect($modalSmallDefects)
            ->filter(fn($s) => isset($s['qty']) && (int) $s['qty'] > 0)
            ->map(fn($s) => ['type' => $s['type'], 'qty' => (int) $s['qty']])
            ->values()
            ->toArray();
    }

    /**
     * Insert-or-update a staged entry by large defect type.
     */
    public function upsertStagedEntry(array $stagedDefects, string $type, int $qty, array $smallsWithQty): array
    {
        $idx = collect($stagedDefects)->search(fn($e) => $e['type'] === $type);

        $entry = [
            'type'         => $type,
            'qty'          => $qty,
            'smallDefects' => $smallsWithQty,
        ];

        if ($idx !== false) {
            $stagedDefects[$idx] = $entry;
        } else {
            $stagedDefects[] = $entry;
        }

        return $stagedDefects;
    }

    public function removeStagedEntry(array $stagedDefects, string $type): array
    {
        return collect($stagedDefects)
            ->reject(fn($e) => $e['type'] === $type)
            ->values()
            ->toArray();
    }

    /**
     * Commits every staged entry into the real defects/smallDefects arrays.
     * Was the foreach loop inline in addDefectFromModal().
     */
    public function mergeStagedIntoDefects(
        array $stagedDefects,
        array $defects,
        array $smallDefects,
        Collection $largeDefectsMaster
    ): array {
        foreach ($stagedDefects as $entry) {
            $largeType = $entry['type'];
            $largeQty  = (int) $entry['qty'];

            if (!$this->existsInMasterList($largeDefectsMaster, 'LargeDefect', $largeType)) {
                continue;
            }

            $existingIndex = collect($defects)
                ->search(fn($d) => strtolower(trim($d['type'])) === strtolower(trim($largeType)));

            if ($existingIndex !== false) {
                $defects[$existingIndex]['qty'] = $largeQty;
            } else {
                $defects[] = [
                    'type'     => trim($largeType),
                    'category' => 'large',
                    'qty'      => $largeQty,
                ];
            }

            if (!isset($smallDefects[$largeType])) {
                $smallDefects[$largeType] = [];
            }

            foreach ($entry['smallDefects'] as $small) {
                $smallType = trim($small['type']);
                $smallQty  = (int) $small['qty'];
                $idx = collect($smallDefects[$largeType])
                    ->search(fn($s) => strtolower(trim($s['type'])) === strtolower($smallType));
                if ($idx !== false) {
                    $smallDefects[$largeType][$idx]['qty'] = $smallQty;
                } else {
                    $smallDefects[$largeType][] = [
                        'type' => $smallType,
                        'qty'  => $smallQty,
                    ];
                }
            }
        }

        return ['defects' => $defects, 'smallDefects' => $smallDefects];
    }

    // -------------------------------------------------------------------------
    // Legacy single-add flows — was inline in addDefect() / addSmallDefect()
    // -------------------------------------------------------------------------

    public function appendLargeDefect(
        array $defects,
        Collection $largeDefectsMaster,
        string $newDefect,
        ?string $category,
        $qty
    ): array {
        $normalized = strtolower(trim($newDefect));
        $category   = $category ?? 'large';

        if (!$this->existsInMasterList($largeDefectsMaster, 'LargeDefect', $newDefect)) {
            return [
                'success' => false,
                'error'   => 'This defect type does not exist in the master list',
                'defects' => $defects,
            ];
        }

        $existing = collect($defects)->contains(function ($defect) use ($normalized, $category) {
            return strtolower(trim($defect['type'])) === $normalized
                && strtolower(trim($defect['category'] ?? 'large')) === strtolower($category);
        });

        if ($existing) {
            return [
                'success' => false,
                'error'   => 'This defect type in this category already exists',
                'defects' => $defects,
            ];
        }

        $defects[] = [
            'type'     => trim($newDefect),
            'category' => $category,
            'qty'      => $qty,
        ];

        return ['success' => true, 'error' => null, 'defects' => $defects];
    }

    public function appendSmallDefect(
        array $smallDefects,
        Collection $smallDefectsMaster,
        array $defects,
        string $largeDefect,
        string $newSmallDefect,
        $newSmallQuan
    ): array {
        $normalized = strtolower(trim($newSmallDefect));

        if (!$this->existsInMasterList($smallDefectsMaster, 'SmallDefect', $newSmallDefect)) {
            return [
                'success'      => false,
                'field'        => 'newSmallDefect',
                'error'        => 'This small defect does not exist in the master list',
                'smallDefects' => $smallDefects,
            ];
        }

        $existing = collect($smallDefects[$largeDefect] ?? [])->contains(function ($defect) use ($normalized) {
            return strtolower(trim($defect['type'])) === $normalized;
        });

        if ($existing) {
            return [
                'success'      => false,
                'field'        => 'newSmallDefect',
                'error'        => 'This small defect type already exists',
                'smallDefects' => $smallDefects,
            ];
        }

        $currentSmallTotal = collect($smallDefects[$largeDefect] ?? [])->sum('qty');
        $largeDefectQty    = collect($defects)->firstWhere('type', $largeDefect)['qty'] ?? '';

        if (((float) $currentSmallTotal + (float) $newSmallQuan) > (float) ($largeDefectQty ?: 0)) {
            return [
                'success'      => false,
                'field'        => 'newSmallQuan',
                'error'        => 'Total small defect quantity cannot exceed large defect quantity.',
                'smallDefects' => $smallDefects,
            ];
        }

        $smallDefects[$largeDefect][] = [
            'type' => trim($newSmallDefect),
            'qty'  => (int) $newSmallQuan,
        ];

        return ['success' => true, 'field' => null, 'error' => null, 'smallDefects' => $smallDefects];
    }

    // -------------------------------------------------------------------------
    // Quantity capping — was inline in updateDefectArray() / updateDefectSmallArray()
    // -------------------------------------------------------------------------

    /**
     * When a large defect's qty is edited down, proportionally caps its
     * small-defect quantities so their total never exceeds the new large qty.
     */
    public function updateLargeDefectQty(array $defects, array $smallDefects, string $editingType, $newQty): array
    {
        foreach ($defects as &$defect) {
            if ($defect['type'] === $editingType) {
                $defect['qty'] = (float) $newQty;
                break;
            }
        }
        unset($defect);

        if (isset($smallDefects[$editingType])) {
            $smallDefects[$editingType] = $this->capSmallQuantitiesToLarge($smallDefects[$editingType], (float) $newQty);
        }

        return ['defects' => $defects, 'smallDefects' => $smallDefects];
    }

    public function capSmallQuantitiesToLarge(array $smalls, float $largeQty): array
    {
        $smallTotal = collect($smalls)->sum(fn($s) => (float) $s['qty']);

        if ($smallTotal <= $largeQty) {
            return $smalls;
        }

        $remaining = $largeQty;
        foreach ($smalls as &$small) {
            if ($remaining <= 0) {
                $small['qty'] = 0;
                continue;
            }
            if ($small['qty'] > $remaining) {
                $small['qty'] = $remaining;
            }
            $remaining -= $small['qty'];
        }
        unset($small);

        return $smalls;
    }

    /**
     * When a single small defect's qty is edited, caps it to whatever
     * headroom remains under the large defect's qty. Returns null when
     * there's nothing staged for this large defect (caller should no-op).
     */
    public function capEditedSmallQuantity(array $smallDefects, array $defects, string $large, string $editingTypeSmall, $newSmallQuan): ?array
    {
        if (!isset($smallDefects[$large])) {
            return null;
        }

        $largeQty = collect($defects)->firstWhere('type', $large)['qty'] ?? 0;

        $otherTotal = collect($smallDefects[$large])
            ->reject(fn($d) => $d['type'] === $editingTypeSmall)
            ->sum(fn($d) => (float) $d['qty']);

        $remaining = $largeQty - $otherTotal;

        foreach ($smallDefects[$large] as &$defect) {
            if ($defect['type'] === $editingTypeSmall) {
                $defect['qty'] = min((float) $newSmallQuan, $remaining);
                break;
            }
        }
        unset($defect);

        return $smallDefects;
    }

  

public function loadDefectsGL($ppf)
{
    $defects = [];
    $rawSmallDefects = [];

    $rows = $this->repo->getDefectsGrouped($ppf);

    // ✅ ONE query replaces N getSmallDefects() calls
    $allSmalls = $this->repo->getSmallDefectsForPpf($ppf);

    foreach ($rows as $row) {
        if ((int) $row->total_qty <= 0) {
            continue;
        }

        $defects[] = [
            'operatorid'   => $row->inspectorId,
            'operatorname' => $row->inspName,
            'type'         => $row->defect,
            'qty'          => (int) $row->total_qty,
            'dateEncode'   => $row->latest_date,
            'Process'      => $row->process,
        ];

        $key = $row->defect . '||' . $row->process . '||' . $row->inspectorId;

        foreach ($allSmalls->get($key, []) as $s) {
            $rawSmallDefects[$row->defect][] = [
                'type' => $s->small_defect,
                'qty'  => (int) $s->total_qty,
            ];
        }
    }

    // Collapse to one row per (large defect, small defect type), summing qty
    // across whichever inspectors/processes reported it.
    $smallDefects = [];
    foreach ($rawSmallDefects as $largeType => $smalls) {
        $smallDefects[$largeType] = collect($smalls)
            ->groupBy('type')
            ->map(fn($group, $type) => [
                'type' => $type,
                'qty'  => $group->sum('qty'),
            ])
            ->values()
            ->toArray();
    }

    // Normalize for the dropdown payload (merge same type+process across inspectors)
    $normalized = [];
    foreach ($defects as $d) {
        $key = strtolower(trim($d['type'])) . '_' . $d['Process'];
        $normalized[$key] ??= [
            'type'    => $d['type'],
            'qty'     => 0,
            'Process' => $d['Process'],
        ];
        $normalized[$key]['qty'] += $d['qty'];
    }

    $defectPayload = array_map(fn($d) => [
        'newDefect' => $d['type'],
        'newQuan'   => $d['qty'],
        'Process'   => $d['Process'],
        'action'    => '',
    ], array_values($normalized));

    return [
        'defects'      => $defects,
        'smallDefects' => $smallDefects,
        'payload'      => $defectPayload,
        'totalQty'     => collect($defects)->sum('qty'),
        'inspectors'   => collect($defects)->pluck('operatorid')->unique()->values(),
        'last'         => end($defects) ?: null,
    ];
}
}