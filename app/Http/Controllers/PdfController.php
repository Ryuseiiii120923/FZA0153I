<?php

namespace App\Http\Controllers;


use App\Models\CheckPPF;
use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
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
        $hf = HF::select('hf_id', 'updated_by', 'GoodQty', 'total_inspect', 'created_at', 'TotalNg', 'remarks','Process')
            ->where('ppfno', $ppf)
            ->orderBy('created_at', 'asc')
            ->get();

        $rows = [];
        // Header columns — scoped to this ppfno, unique large+small combinations
        $groupedDefects = Defect::with('children')
            ->where('ppfno', $ppf)
            ->get()
            ->groupBy('defect')
            ->map(function ($items, $largeCategory) {
                return $items->flatMap(function ($defect) use ($largeCategory) {
                    return $defect->children->map(function ($child) use ($largeCategory) {
                        return [
                            'large_category' => $largeCategory,
                            'small_category' => $child->small_defect,
                        ];
                    });
                })->unique('small_category')->values();
            });

        $groupedReworks = Rework::where('ppfno', $ppf)->get()
            ->map(function ($groupedRework) {
                return [
                    'type' => $groupedRework->rework_type
                ];
            })->values();

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

        foreach ($hf as $perHf) {
            $defects = Defect::with(['children' => function ($query) use ($perHf) {
                $query->where('hf_id', $perHf->hf_id)
                      ->where('updated_by', $perHf->updated_by);
            }])
                ->where('hf_id', $perHf->hf_id)
                ->where('updated_by', $perHf->updated_by)
                ->where('ppfno', $ppf)
                ->get()
                ->flatMap(function ($defect) use ($perHf) {
                    // Always emit a large-category entry so qty shows even with no children
                    $largeEntry = [[
                        'hf_id'          => $perHf->hf_id,
                        'updated_by'     => $perHf->updated_by,
                        'large_category' => $defect->defect,
                        'small_category' => null,
                        'small_qty'      => 0,
                        'large_qty'      => (int) $defect->qty,
                    ]];

                    $childEntries = $defect->children->map(function ($child) use ($defect, $perHf) {
                        return [
                            'hf_id'          => $perHf->hf_id,
                            'updated_by'     => $perHf->updated_by,
                            'large_category' => $defect->defect,
                            'small_category' => $child->small_defect,
                            'small_qty'      => (int) $child->qty,
                            'large_qty'      => (int) $defect->qty,
                        ];
                    })->toArray();

                    return array_merge($largeEntry, $childEntries);
                })
                ->values();

            $reworks = Rework::where('hf_id', $perHf->hf_id)
                ->where('updated_by', $perHf->updated_by)
                ->where('ppfno', $ppf)
                ->get()
                ->map(function ($rework) {
                    return [
                        'type' => $rework->rework_type,
                        'qty'  => (int) $rework->qty,
                    ];
                })
                ->values();

            $date        = Carbon::parse($perHf->created_at);
            $denominator = $perHf->TotalNg + $perHf->GoodQty;
            $ngratio     = $denominator > 0
                ? number_format(($perHf->TotalNg / $denominator) * 100, 2)
                : '0.00';

            $rows[] = [
                'hf_id'               => $perHf->hf_id,
                'updated_by'          => $perHf->updated_by,
                'mm'                  => $date->format('m'),
                'dd'                  => $date->format('d'),
                'shift'               => '1',
                'total_quantity'      => $perHf->total_inspect,
                'process'             => $perHf->Process ?? '',
                'defects'             => $defects->toArray(),
                'reworks'             => $reworks->toArray(),
                'total_good_qty'      => $perHf->GoodQty,
                'total_ng_qty'        => $perHf->TotalNg,
                'ng_percent'          => $ngratio,
                'nqr_criteria'        => isset($NQRCriteria->nqrCriteria)
                    ? number_format((float) $NQRCriteria->nqrCriteria, 2, '.', '')
                    : '',
                'nqr_judgement'       => 'O',
                'handfinisher_no'     => $perHf->hf_id,
                'visual_inspector_no' => $perHf->updated_by,
                'remarks'             => $perHf->remarks,
            ];
        }

        // ---------------------------------------------------------------
        // TEST ONLY — remove this block when done testing
        // Simulates a "VI" process row with its own defects and reworks
        // to verify the totals row and PDF columns work for multiple processes
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
                // Sentinel large-category entry (small_category = null)
                [
                    'hf_id'          => 'TEST-HF-01',
                    'updated_by'     => 'TestInspector',
                    'large_category' => $groupedDefects->keys()->first(), // use first existing large category
                    'small_category' => null,
                    'small_qty'      => 0,
                    'large_qty'      => 3,
                ],
                // Small-category entry under the same large category
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
        ];
        // ---------------------------------------------------------------
        // END TEST BLOCK
        // ---------------------------------------------------------------

        // Compute totals from HF rows only then persist to DB
        $hfRows = array_values(array_filter($rows, fn($r) => $r['process'] === 'VI'));

        $totals = [
            'summary'       => $this->totalofProcessService->calculateTotalGoodNg($hfRows),
            'large_defects' => $this->totalofProcessService->calculateTotalLargeDefects($hfRows),
            'small_defects' => $this->totalofProcessService->calculateTotalSmallDefects($hfRows),
            'reworks'       => $this->totalofProcessService->calculateTotalRework($hfRows),
            'remarks'       => $this->totalofProcessService->fetchRemarks($ppf)->Details ?? '',

        ];

        $this->totalofProcessService->AddToDb($ppf, $hfRows);

        $pdf = Pdf::loadView('pdf.general-process-record', [
            'groupedDefects'    => $groupedDefects,
            'groupedReworks'    => $groupedReworks,
            'rows'              => $rows,
            'record'            => $record,
            'totalDefects'      => $totaldefects,
            'totalLargeDefects' => $totalLargeDefects,
            'totalSmallDefects' => $totalSmallDefects,
            'totalReworks'      => $totalReworks,
            'totals'            => $totals,
            'TotalRemarks'       => $totals['remarks'] ?? '',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('general-process-record.pdf');
    }

   
}