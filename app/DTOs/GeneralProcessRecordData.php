<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * Immutable data transfer object for the General Process Record PDF.
 *
 * Centralises every value the Blade view needs so the controller and
 * view are decoupled from raw array shapes.
 */
final class GeneralProcessRecordData
{
    public function __construct(
        // ── Header / meta ────────────────────────────────────────────
        public readonly string     $ppfNo,
        public readonly string     $partNumber,
        public readonly string     $lotNo,
        public readonly string     $mixingLotNo,
        public readonly string     $monthYear,
        public readonly string     $inspectionGroup,
        public readonly string     $moldingDieNumber,
        public readonly string     $machineNumber,
        public readonly string     $checkedBy,
        public readonly bool       $isSilicon,

        // ── Checkbox flags ───────────────────────────────────────────
        public readonly bool       $viGood,
        public readonly bool       $viNg,
        public readonly bool       $rework,

        // ── Column definitions ───────────────────────────────────────
        /** @var Collection<string, Collection<array{large_category:string, small_category:string|null}>> */
        public readonly Collection $groupedDefects,

        /** @var Collection<array{type:string}> */
        public readonly Collection $groupedReworks,

        // ── Row data ─────────────────────────────────────────────────
        /** @var array<int, array> */
        public readonly array      $rows,

        // ── Totals ───────────────────────────────────────────────────
        public readonly array      $totals,
        public readonly string     $totalRemarks,
    ) {}

    // ── Derived column counts (used in Blade colspan attributes) ─────

    public function totalLargeDefects(): int
    {
        return $this->groupedDefects->count();
    }

    public function totalSmallDefects(): int
    {
        return $this->groupedDefects->flatten(1)->count();
    }

    public function totalReworks(): int
    {
        return $this->groupedReworks->count();
    }
}
