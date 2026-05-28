<?php

namespace App\Actions;

use App\DTOs\GeneralProcessRecordData;
use App\Models\CheckPPF;
use App\Models\HF\HF;
use App\Models\HFRW\HFRWForms;
use App\Models\LotNo;
use App\Models\MoldingDailyReport;
use App\Models\NCPHistory;
use App\Services\PDF\DefectColumnService;
use App\Services\PDF\ProcessRowBuilder;
use App\Services\TotalofProcessService;
use Carbon\Carbon;

/**
 * Orchestrates fetching, transforming, and aggregating all data
 * required for the General Process Record PDF.
 *
 * Keeping this logic out of the controller means it can be called
 * from console commands, queued jobs, or tests without HTTP overhead.
 */
class BuildGeneralProcessRecordAction
{
    public function __construct(
        private readonly DefectColumnService  $defectColumnService,
        private readonly ProcessRowBuilder    $rowBuilder,
        private readonly TotalofProcessService $totalofProcessService,
    ) {}

    public function execute(string $ppf): GeneralProcessRecordData
    {
        // ── 1. Column definitions ─────────────────────────────────────
        $groupedDefects = $this->defectColumnService->buildGroupedDefects($ppf);
        $groupedReworks = $this->defectColumnService->buildGroupedReworks($ppf);

        // ── 2. Master record & NQR criteria ──────────────────────────
        $masterRecord  = CheckPPF::where('流動NO', $ppf)->firstOrFail();
        $mixingLotNo   = LotNo::select('混練LOTNO')->where('流動NO', $ppf)->first();
        $nqrCriteria   = $this->resolveNqrCriteria($mixingLotNo->混練LOTNO ?? '', $ppf);

        // ── 3. Form records ───────────────────────────────────────────
        $hfForms = HF::select(
                'hf_id', 'updated_by', 'GoodQty', 'total_inspect',
                'created_at', 'TotalNg', 'remarks', 'Process', 'Operation',
            )
            ->where('ppfno', $ppf)
            ->orderBy('created_at')
            ->get();

        $hfrwForms = HFRWForms::select(
                'hf_id', 'updated_by', 'GoodQty', 'total_inspect',
                'created_at', 'TotalNg', 'Process', 'Operation',
            )
            ->where('ppfno', $ppf)
            ->orderBy('created_at')
            ->get();

        // ── 4. Build rows ─────────────────────────────────────────────
        $rows = $this->rowBuilder->build($hfForms, $hfrwForms, $ppf, $nqrCriteria);
          // ── [TEST] Append hardcoded rows — remove this block when done ─
        $rows = array_merge($rows, \App\Testing\GeneralProcessRecordTestDataFactory::rows(
            $groupedDefects,
            $groupedReworks,
        ));
        // ── [END TEST] ────────────────────────────────────────────────

        // ── 5. Totals (VI rows only) & persist ────────────────────────
        $viRows = array_values(array_filter($rows, fn($r) => $r['operation'] === 'VI'));
        $totals = $this->buildTotals($viRows, $ppf);
        $this->totalofProcessService->AddToDb($ppf, $viRows);

        // ── 6. Header record ──────────────────────────────────────────
        $partTypeCode = substr($masterRecord->品番, 0, 2);

        return new GeneralProcessRecordData(
            ppfNo:           $masterRecord->流動NO,
            partNumber:      $masterRecord->品番,
            lotNo:           $masterRecord->成形ﾛｯﾄ,
            mixingLotNo:     $mixingLotNo->混練LOTNO ?? '',
            monthYear:       Carbon::now()->format('M Y'),
            inspectionGroup: 'A',
            moldingDieNumber: $masterRecord->金型NO,
            machineNumber:   'P-' . $masterRecord->PRESSNO,
            checkedBy:       'Supervisor',
            isSilicon:       in_array($partTypeCode, ['91', '98'], strict: true),
            noViCheck:       true,
            viGood:          false,
            viNg:            false,
            rework:          false,
            groupedDefects:  $groupedDefects,
            groupedReworks:  $groupedReworks,
            rows:            $rows,
            totals:          $totals,
            totalRemarks:    $totals['remarks'] ?? '',
        );
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function resolveNqrCriteria(string $mixingNo, string $ppf): string
    {
        if (blank($mixingNo) || !preg_match('/^(\d{2})(\d)(\d{2})/', $mixingNo, $m)) {
            return '';
        }

        $date = Carbon::parse(sprintf('20%s-%02d-%02d', $m[1], $m[2], $m[3]));

        $mdNo = MoldingDailyReport::select('金型NO')
            ->where('流動NO', $ppf)
            ->value('金型NO');

        if (!$mdNo) {
            return '';
        }

        $criteria = NCPHistory::select('nqrCriteria')
            ->where('mdNo', $mdNo)
            ->where('approvedDate', '<=', $date)
            ->latest('approvedDate')
            ->value('nqrCriteria');

        return $criteria !== null
            ? number_format((float) $criteria, 2, '.', '')
            : '';
    }

    private function buildTotals(array $viRows, string $ppf): array
    {
        return [
            'summary'       => $this->totalofProcessService->calculateTotalGoodNg($viRows),
            'large_defects' => $this->totalofProcessService->calculateTotalLargeDefects($viRows),
            'small_defects' => $this->totalofProcessService->calculateTotalSmallDefects($viRows),
            'reworks'       => $this->totalofProcessService->calculateTotalRework($viRows),
            'remarks'       => $this->totalofProcessService->fetchRemarks($ppf)->Details ?? '',
            'total_qty'     => $this->totalofProcessService->calculateTotalQty($viRows),
        ];
    }
}
