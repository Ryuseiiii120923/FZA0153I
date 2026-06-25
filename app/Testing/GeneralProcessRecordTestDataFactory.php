<?php

namespace App\Testing;

use Illuminate\Support\Collection;

/**
 * Provides hardcoded test rows for the General Process Record PDF.
 *
 * Usage in BuildGeneralProcessRecordAction::execute():
 *
 *   $rows = array_merge($rows, TestDataFactory::rows($groupedDefects, $groupedReworks));
 *
 * Remove the merge call (or the entire class) when no longer needed.
 */
class GeneralProcessRecordTestDataFactory
{
    /**
     * Returns an array of fake rows in the same shape as ProcessRowBuilder::build().
     *
     * @param  Collection $groupedDefects  From DefectColumnService::buildGroupedDefects()
     * @param  Collection $groupedReworks  From DefectColumnService::buildGroupedReworks()
     * @return array<int, array>
     */
    public static function rows(Collection $groupedDefects, Collection $groupedReworks): array
    {
        $firstLargeCategory = $groupedDefects->keys()->first();
        $firstSmallCategory = $groupedDefects->first()?->first()['small_category'] ?? null;
        $firstReworkType    = $groupedReworks->first()['type'] ?? null;

        return [
        

            // ── Fake HF row #1 ────────────────────────────────────────
            self::makeRow(
                hfId:         'TEST-HF-01',
                updatedBy:    'TestHF',
                operation:    'HF',
                process:      'HF',
                totalInspect: 50,
                goodQty:      47,
                totalNg:      3,
                defects:      [
                    self::largeDefect('TEST-HF-01', 'TestHF', $firstLargeCategory, qty: 3),
                    self::smallDefect('TEST-HF-01', 'TestHF', $firstLargeCategory, $firstSmallCategory, qty: 3),
                ],
                reworks:      [],
                remarks:      'TEST HF ROW 1 — delete me',
            ),

            // ── Fake HF row #2 (different inspector / quantities) ─────
            self::makeRow(
                hfId:         'TEST-HF-02',
                updatedBy:    'TestHF2',
                operation:    'HF',
                process:      'HF',
                totalInspect: 80,
                goodQty:      74,
                totalNg:      6,
                defects:      [
                    self::largeDefect('TEST-HF-02', 'TestHF2', $firstLargeCategory, qty: 6),
                    self::smallDefect('TEST-HF-02', 'TestHF2', $firstLargeCategory, $firstSmallCategory, qty: 6),
                ],
                reworks:      [],
                remarks:      'TEST HF ROW 2 — delete me',
            ),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────

    private static function makeRow(
        string $hfId,
        string $updatedBy,
        string $operation,
        string $process,
        int    $totalInspect,
        int    $goodQty,
        int    $totalNg,
        array  $defects,
        array  $reworks,
        string $remarks = '',
    ): array {
        $denominator = $goodQty + $totalNg;

        return [
            'hf_id'               => $hfId,
            'updated_by'          => $updatedBy,
            'mm'                  => now()->format('m'),
            'dd'                  => now()->format('d'),
            'shift'               => '1',
            'total_quantity'      => $totalInspect,
            'process'             => $process,
            'defects'             => $defects,
            'reworks'             => $reworks,
            'total_good_qty'      => $goodQty,
            'total_ng_qty'        => $totalNg,
            'ng_percent'          => $denominator > 0
                ? number_format(($totalNg / $denominator) * 100, 2)
                : '0.00',
            'nqr_criteria'        => '',
            'nqr_judgement'       => $operation === 'VI' ? 'O' : '',
            'handfinisher_no'     => $hfId,
            'visual_inspector_no' => $updatedBy,
            'feedback_receipt'    => '',
            'ng_parts_status'     => '',
            'gl_confirmation'     => '',
            'remarks'             => $remarks,
            'operation'           => $operation,
            'source'              => $operation === 'VI' ? 'VI' : 'HFRW',
        ];
    }

    private static function largeDefect(
        string  $hfId,
        string  $updatedBy,
        ?string $largeCategory,
        int     $qty,
    ): array {
        return [
            'hf_id'          => $hfId,
            'updated_by'     => $updatedBy,
            'large_category' => $largeCategory ?? 'Unknown',
            'small_category' => null,
            'small_qty'      => 0,
            'large_qty'      => $qty,
        ];
    }

    private static function smallDefect(
        string  $hfId,
        string  $updatedBy,
        ?string $largeCategory,
        ?string $smallCategory,
        int     $qty,
    ): array {
        return [
            'hf_id'          => $hfId,
            'updated_by'     => $updatedBy,
            'large_category' => $largeCategory ?? 'Unknown',
            'small_category' => $smallCategory,
            'small_qty'      => $qty,
            'large_qty'      => $qty,
        ];
    }
}
