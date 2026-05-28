<?php

namespace App\Http\Controllers;


use App\Models\CheckPPF;
use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
use App\Models\HFRW\HFRWForms;
use App\Models\HFRW\HFRWDefect;
use App\Models\HFRW\HFRWRework;
use App\Models\LotNo;
use App\Models\MoldingDailyReport;
use App\Models\NCPHistory;
use App\Services\TotalofProcessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;


class PdfController extends Controller
{
    public function __construct(protected TotalofProcessService $totalofProcessService) {}

    public function getNQRCriteria(string $mixingNo = "", string $ppf = "")
    {
        preg_match('/^(\d{2})(\d)(\d{2})/', $mixingNo, $matches);

        $formattedDate = sprintf(
            '20%s-%02d-%02d',
            $matches[1],
            $matches[2],
            $matches[3]
        );

        $dateFromMix = Carbon::parse($formattedDate);

        $getMdNo = MoldingDailyReport::select('金型NO')
            ->where('流動NO', $ppf)
            ->first();

        $latestApproved = NCPHistory::Select('nqrCriteria')->where('mdNo', $getMdNo->金型NO)
            ->where('approvedDate', '<=', $dateFromMix)
            ->latest('approvedDate')
            ->first();

        return $latestApproved;
    }

    public function generate(string $ppf = "")
    {
        $hf = HF::select('hf_id', 'updated_by', 'GoodQty', 'total_inspect', 'created_at', 'TotalNg', 'remarks', 'Process', 'Operation')
            ->where('ppfno', $ppf)
            ->orderBy('created_at', 'asc')
            ->get();
        $hfrw = HFRWForms::select('hf_id', 'updated_by', 'GoodQty', 'total_inspect', 'created_at', 'TotalNg', 'Process', 'Operation')
            ->where('ppfno', $ppf)
            ->orderBy('created_at', 'asc')
            ->get();

        $rows = [];

        // ── Header columns: merge HF + HFRW defects so all unique large+small
        //    categories appear as columns regardless of which process they came from ──

        $hfDefectRows   = Defect::with('children')->where('ppfno', $ppf)->get();
        $hfrwDefectRows = HFRWDefect::with('children')->where('ppfno', $ppf)->get();

        $allDefectRows = $hfDefectRows->merge($hfrwDefectRows);

        $groupedDefects = $allDefectRows
            ->groupBy('defect')
            ->map(function ($items, $largeCategory) {
                $smallDefects = $items->flatMap(function ($defect) use ($largeCategory) {
                    return $defect->children->map(function ($child) use ($largeCategory) {
                        return [
                            'large_category' => $largeCategory,
                            'small_category' => $child->small_defect,
                        ];
                    });
                })->unique('small_category')->values();

                // If no small defects exist, insert a placeholder so the large
                // category still occupies exactly one column and the header/body
                // column counts stay in sync.
                if ($smallDefects->isEmpty()) {
                    return collect([[
                        'large_category' => $largeCategory,
                        'small_category' => null,
                    ]]);
                }

                return $smallDefects;
            });

        $hfReworkRows = Rework::where('ppfno', $ppf)->get();

        $groupedReworks = $hfReworkRows
            ->map(fn($r) => ['type' => $r->rework_type])
            ->unique('type')
            ->values();

        $totaldefects      = $groupedDefects->count();
        $totalReworks      = collect($groupedReworks)->count();
        $totalSmallDefects = $groupedDefects->flatten(1)->count();
        $totalLargeDefects = $groupedDefects->count();
        $now               = Carbon::now();
        $formatDate        = $now->format('M Y');
        $masterRecord      = CheckPPF::where('流動NO', $ppf)->first();
        $mixingLotNo       = LotNo::select('混練LOTNO')->where('流動NO', $ppf)->first();
        $partTypeCode      = substr($masterRecord->品番, 0, 2);
        $isSilicon         = ($partTypeCode == '91' || $partTypeCode == '98') ? true : false;
        $NQRCriteria       = $this->getNQRCriteria($mixingLotNo->混練LOTNO ?? '', $ppf);

        $record = [
            'month_year'         => $formatDate,
            'ppf_no'             => $masterRecord->流動NO,
            'part_number'        => $masterRecord->品番,
            'lot_no'             => $masterRecord->成形ﾛｯﾄ,
            'mixing_lot_no'      => $mixingLotNo->混練LOTNO ?? '',
            'inspection_group'   => 'A',
            'molding_die_number' => $masterRecord->金型NO,
            'machine_number'     => 'P-' . $masterRecord->PRESSNO,
            'checked_by'         => 'Supervisor',
            'is_silicon'         => $isSilicon,
            'no_vi_check'        => true,
            'vi_good'            => false,
            'vi_ng'              => false,
            'rework'             => false,
        ];

        // ── Merge HF and HFRW form records, then sort by created_at ascending ──
        $normalizeCreatedAt = fn($r, string $source) => (object) array_merge(
            $r->toArray(),
            [
                '_source'    => $source,
                'created_at' => Carbon::parse($r->created_at)->format('Y-m-d H:i:s'),
            ]
        );

        $allForms = collect()
            ->merge($hf->map(fn($r)   => $normalizeCreatedAt($r, 'VI')))
            ->merge($hfrw->map(fn($r) => $normalizeCreatedAt($r, 'HFRW')))
            ->sortBy('created_at')
            ->values();

        foreach ($allForms as $form) {
            $isHfrw = $form->_source === 'HFRW';

            // ── Resolve defects from the correct model ──────────────────
            $defectModel = $isHfrw ? HFRWDefect::class : Defect::class;

            $defects = $defectModel::with(['children' => function ($query) use ($form) {
                $query->where('hf_id', $form->hf_id)
                    ->where('updated_by', $form->updated_by);
            }])
                ->where('hf_id', $form->hf_id)
                ->where('updated_by', $form->updated_by)
                ->where('ppfno', $ppf)
                ->get()
                ->flatMap(function ($defect) use ($form) {
                    // Always emit a large-category entry so qty shows even with no children
                    $largeEntry = [[
                        'hf_id'          => $form->hf_id,
                        'updated_by'     => $form->updated_by,
                        'large_category' => $defect->defect,
                        'small_category' => null,
                        'small_qty'      => 0,
                        'large_qty'      => (int) $defect->qty,
                    ]];

                    $childEntries = $defect->children->map(function ($child) use ($defect, $form) {
                        return [
                            'hf_id'          => $form->hf_id,
                            'updated_by'     => $form->updated_by,
                            'large_category' => $defect->defect,
                            'small_category' => $child->small_defect,
                            'small_qty'      => (int) $child->qty,
                            'large_qty'      => (int) $defect->qty,
                        ];
                    })->toArray();

                    return array_merge($largeEntry, $childEntries);
                })
                ->values();

            // ── Reworks: only HF rows carry reworks ────────────────────
            $reworks = $isHfrw
                ? collect()
                : Rework::where('hf_id', $form->hf_id)
                ->where('updated_by', $form->updated_by)
                ->where('ppfno', $ppf)
                ->get()
                ->map(fn($rework) => [
                    'type' => $rework->rework_type,
                    'qty'  => (int) $rework->qty,
                ]);

            $date        = Carbon::parse($form->created_at);
            $denominator = $form->TotalNg + $form->GoodQty;
            $ngratio     = $denominator > 0
                ? number_format(($form->TotalNg / $denominator) * 100, 2)
                : '0.00';

            $rows[] = [
                'hf_id'               => $form->hf_id,
                'updated_by'          => $form->updated_by,
                'mm'                  => $date->format('m'),
                'dd'                  => $date->format('d'),
                'shift'               => '1',
                'total_quantity'      => $form->total_inspect,
                'process'             => $form->Process ?? '',
                'defects'             => $defects->toArray(),
                'reworks'             => $reworks->values()->toArray(),
                'total_good_qty'      => $form->GoodQty,
                'total_ng_qty'        => $form->TotalNg,
                'ng_percent'          => $ngratio,
                'nqr_criteria'        => isset($NQRCriteria->nqrCriteria)
                    ? number_format((float) $NQRCriteria->nqrCriteria, 2, '.', '')
                    : '',
                'nqr_judgement'       => $isHfrw ? '' : 'O',
                'handfinisher_no'     => $form->hf_id,
                'visual_inspector_no' => $form->updated_by,
                'remarks'             => $form->remarks ?? '',
                'operation'           => $form->Operation ?? ($isHfrw ? 'VI' : ''),
                'source'              => $form->_source,
            ];
        }

        // ---------------------------------------------------------------
        // TEST ONLY — remove this block when done testing
        // ---------------------------------------------------------------
        $rows[] = [
            'hf_id'               => 'TEST-VI-01',
            'updated_by'          => 'TestInspector',
            'mm'                  => now()->format('m'),
            'dd'                  => now()->format('d'),
            'shift'               => '1',
            'total_quantity'      => 50,
            'process'             => 'HF',
            'defects'             => [
                [
                    'hf_id'          => 'TEST-HF-01',
                    'updated_by'     => 'TestInspector',
                    'large_category' => $groupedDefects->keys()->first(),
                    'small_category' => null,
                    'small_qty'      => 0,
                    'large_qty'      => 3,
                ],
                [
                    'hf_id'          => 'TEST-HF-01',
                    'updated_by'     => 'TestInspector',
                    'large_category' => $groupedDefects->keys()->first(),
                    'small_category' => $groupedDefects->first()->first()['small_category'] ?? null,
                    'small_qty'      => 3,
                    'large_qty'      => 3,
                ],
            ],
            'reworks'             => $groupedReworks->isNotEmpty() ? [[
                'type' => $groupedReworks->first()['type'],
                'qty'  => 2,
            ]] : [],
            'total_good_qty'      => 47,
            'total_ng_qty'        => 3,
            'ng_percent'          => '6.00',
            'nqr_criteria'        => '',
            'nqr_judgement'       => '',
            'handfinisher_no'     => 'TEST-VI-01',
            'visual_inspector_no' => 'TestInspector',
            'remarks'             => 'TEST ROW — DELETE ME',
            'operation'           => 'HF',
            'source'              => 'VI',
        ];
        // ---------------------------------------------------------------
        // END TEST BLOCK
        // ---------------------------------------------------------------

        // ── Group rows by operation, then compute per-operation totals ──
        $rowsByOperation = collect($rows)->groupBy('operation');

        $totalsByOperation = $rowsByOperation->map(function ($opRows, $operation) use ($ppf) {
            $opArray  = $opRows->values()->toArray();
            $isViLike = $operation === 'VI'; // only VI rows feed the totals service

            return [
                'summary'       => $this->totalofProcessService->calculateTotalGoodNg(
                    $isViLike ? $opArray : [],
                    $isViLike ? [] : $opArray
                ),
                'large_defects' => $this->totalofProcessService->calculateTotalLargeDefects($opArray),
                'small_defects' => $this->totalofProcessService->calculateTotalSmallDefects($opArray),
                'reworks'       => $this->totalofProcessService->calculateTotalRework($opArray),
                'remarks'       => $this->totalofProcessService->fetchRemarks($ppf)->Details ?? '',
            ];
        });

        // ── Persist VI totals to DB (unchanged behaviour) ──
        $hfRows   = array_values(array_filter($rows, fn($r) => $r['operation'] === 'VI'));
        $this->totalofProcessService->AddToDb($ppf, $hfRows);

        $pdf = Pdf::loadView('pdf.general-process-record', [
            'groupedDefects'     => $groupedDefects,
            'groupedReworks'     => $groupedReworks,
            'rowsByOperation'    => $rowsByOperation,
            'totalsByOperation'  => $totalsByOperation,
            'record'             => $record,
            'totalDefects'       => $totaldefects,
            'totalLargeDefects'  => $totalLargeDefects,
            'totalSmallDefects'  => $totalSmallDefects,
            'totalReworks'       => $totalReworks,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('general-process-record.pdf');
    }
}