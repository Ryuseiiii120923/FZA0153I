<?php

namespace App\Services\Rework;

use App\Repositories\Rework\ReworkRepository;
use Illuminate\Support\Collection;

class ReworkService
{

    public function __construct(
        protected ReworkRepository $reworkRepository,
    ) {}

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
    public function existsInMasterList(Collection $master, string $value): bool
    {
        return $master->pluck('DefectType')
            ->map(fn($d) => strtolower(trim($d)))
            ->contains(strtolower(trim($value)));
    }

    public function isDuplicate(array $committed, array $staged, string $hfno, string $type): bool
    {
        $normalized = strtolower(trim($type));

        $inCommitted = collect($committed)->contains(
            fn($r) => ($r['hfno'] ?? '') === $hfno && strtolower(trim($r['type'] ?? '')) === $normalized
        );

        $inStaged = collect($staged)->contains(
            fn($r) => ($r['hfno'] ?? '') === $hfno && strtolower(trim($r['type'] ?? '')) === $normalized
        );

        return $inCommitted || $inStaged;
    }

    public function hfLimitReached(array $committed, array $staged, string $hfno, int $limit = 5): bool
    {
        $allHfs = collect($committed)->pluck('hfno')
            ->merge(collect($staged)->pluck('hfno'))
            ->unique();

        return !$allHfs->contains($hfno) && $allHfs->count() >= $limit;
    }

    public function buildStagedEntry(string $hfno, $totalInsp, string $type, $quan): array
    {
        return [
            'hfno'      => $hfno,
            'totalinsp' => $totalInsp,
            'type'      => trim($type),
            'quan'      => (int) $quan,
        ];
    }

    public function buildStagedListFromCommitted(array $reworkss): array
    {
        return collect($reworkss)->map(fn($r) => [
            'hfno'      => $r['hfno'] ?? '',
            'totalinsp' => $r['totalinsp'] ?? '',
            'type'      => $r['type'] ?? '',
            'quan'      => (int) ($r['quan'] ?? 0),
        ])->values()->toArray();
    }

    /**
     * Insert-or-update each staged entry into the committed list, matched
     * by hfno + type.
     */
    public function mergeStagedIntoCommitted(array $staged, array $committed): array
    {
        foreach ($staged as $entry) {
            $entryHfno = $entry['hfno'] ?? '';
            $entryType = strtolower(trim($entry['type'] ?? ''));

            $existingIndex = null;
            foreach ($committed as $i => $r) {
                if (
                    trim($r['hfno'] ?? '') === $entryHfno &&
                    strtolower(trim($r['type'] ?? '')) === $entryType
                ) {
                    $existingIndex = $i;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $committed[$existingIndex] = $entry;
            } else {
                $committed[] = $entry;
            }
        }

        return $committed;
    }

    public function removeByHfnoAndType(array $reworkss, string $hfno, string $type): array
    {
        $hfno = trim($hfno);
        $type = trim(strtoupper($type));

        return collect($reworkss)
            ->reject(
                fn($rework) =>
                trim($rework['hfno'] ?? $rework['newhfno'] ?? '') === $hfno &&
                    trim(strtoupper($rework['type'] ?? $rework['newtype'] ?? '')) === $type
            )
            ->values()
            ->toArray();
    }

    public function totalForForm(array $reworkss, $hfno = null): int
    {
        return collect($reworkss)
            ->when($hfno !== null, fn($c) => $c->filter(fn($r) => ($r['hfno'] ?? null) == $hfno))
            ->sum(fn($x) => (int) ($x['quan'] ?? 0));
    }

    public function updateEntry(array $reworkss, ?string $editingType, ?string $editingHfno, $newQuan, $newHfno, $newTotalInsp): array
    {
        foreach ($reworkss as &$rework) {
            if ($rework['type'] === $editingType && $rework['hfno'] === $editingHfno) {
                $rework['quan']      = $newQuan;
                $rework['hfno']      = $newHfno;
                $rework['totalinsp'] = $newTotalInsp;
                break;
            }
        }
        unset($rework);

        return $reworkss;
    }
}
