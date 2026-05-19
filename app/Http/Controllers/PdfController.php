<?php

namespace App\Http\Controllers;

use App\Models\CheckPPF;
use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\LotNo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PdfController extends Controller
{

    public function generate( string $ppf = "")
    {
        $hf = HF::select('hf_id', 'updated_by', 'GoodQty', 'total_inspect', 'updated_date')->where('ppfno', $ppf)->get();
        $rows = [];
        $reworks = [
            ['type' => 'Excessive Flash'],
            ['type' => 'FM White'],
            ['type' => 'Flash Adhesion'],
            ['type' => 'Chipping'],
        ];

        // Global header columns — load all unique large+small combinations across all HF
        $groupedDefects = Defect::with('children')
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
                })->unique('small_category')->values(); // deduplicate header columns only
            });

        $totaldefects      = $groupedDefects->count();
        $totalReworks      = collect($reworks)->count();
        $totalSmallDefects = $groupedDefects->flatten(1)->count();
        $totalLargeDefects = $groupedDefects->count();
        $now = Carbon::now();
        $formatDate = $now->format('M Y');
        $masterRecord = CheckPPF::where('流動NO', $ppf)->first();
        $mixingLotNo = LotNo::select('混練LOTNO')->where('流動NO', $ppf)->first();
        $partTypeCode = substr($masterRecord->品番,0,2);
        $isSilicon = ($partTypeCode == '91' || $partTypeCode == '98')? true : false;

        $record = [
            'month_year'         => $formatDate,
            'ppf_no'             => $masterRecord->流動NO,
            'part_number'        => $masterRecord->品番,
            'lot_no'             => $masterRecord->成形ﾛｯﾄ,
            'mixing_lot_no'      => $mixingLotNo ->混練LOTNO ?? '',
            'inspection_group'   => 'A',
            'molding_die_number' => $masterRecord->金型NO,
            'machine_number'     => 'P-'. $masterRecord->PRESSNO,
            'checked_by'         => 'Supervisor',
            'is_silicon'         => $isSilicon,
            'no_vi_check'        => true,
            'vi_good'            => false,
            'vi_ng'              => false,
            'rework'             => false,
        ]; 

        foreach ($hf as $perHf) {
            $defects = Defect::with(['children' => function ($query) use ($perHf) {
                    $query->where('hf_id', $perHf->hf_id); 
                }])
                ->where('hf_id', $perHf->hf_id)
                ->where('ppfno', $ppf)
                ->get()
                ->flatMap(function ($defect) use ($perHf) {
                    return $defect->children->map(function ($child) use ($defect, $perHf) {
                        return [
                            'hf_id'          => $perHf->hf_id,
                            'large_category' => $defect->defect,
                            'small_category' => $child->small_defect,
                            'small_qty'      => (int) $child->qty,
                            'large_qty'      => (int) $defect->qty,
                        ];
                    });
                })
                ->values();

            $date = Carbon::parse($perHf->updated_date);

            $rows[] = [
                'hf_id'               => $perHf->hf_id,
                'mm'                  => $date->format('m'),
                'dd'                  => $date->format('d'),
                'shift'               => '1',
                'total_quantity'      => $perHf->total_inspect,
                'process'             => 'HF',
                'defects'             => $defects->toArray(),
                'reworks'             => [
                    ['type' => 'Excessive Flash', 'qty' => 12],
                    ['type' => 'FM White',        'qty' => 13],
                    ['type' => 'Flash Adhesion',  'qty' => 14],
                    ['type' => 'Chipping',        'qty' => 14],
                ],
                'total_good_qty'      => $perHf->GoodQty,
                'total_ng_qty'        => 4,
                'ng_percent'          => 0.4,
                'nqr_judgement'       => 'O',
                'handfinisher_no'     => $perHf->hf_id,
                'visual_inspector_no' => $perHf->updated_by,
                'remarks'             => 'OK',
            ];
        }

        $pdf = Pdf::loadView('pdf.general-process-record', [
            'groupedDefects'    => $groupedDefects,
            'rows'              => $rows,
            'reworks'           => $reworks,
            'record'            => $record,
            'totalDefects'      => $totaldefects,
            'totalLargeDefects' => $totalLargeDefects,
            'totalSmallDefects' => $totalSmallDefects,
            'totalReworks'      => $totalReworks,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('general-process-record.pdf');
    }
}