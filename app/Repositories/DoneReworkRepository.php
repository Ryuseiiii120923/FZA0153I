<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DoneReworkRepository
{
    protected $workerRepo;
    public function __construct(WorkerRepository $workerRepo)
    {
        $this->workerRepo = $workerRepo;
    }
    public function saveMainForm(array $data)
    {
        try {
            $forms = [];
            $forms[] = [
                'hf_id' => $data['hf_id'],
                'total_inspect' => $data['total_inspect'],
                'created_at' => now(),
                'updated_by' => $data['encoder'],
                'ppfno' => $data['ppfno'],
                'GoodQty' => $data['goodQty'],
                'inspect_REC' => $data['inspect_REC']
            ];


            DB::table('dr_forms')->upsert(
                $forms,
                ['inspect_REC', 'ppfno'],
                ['GoodQty', 'updated_by', 'total_inspect', 'hf_id']
            );
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save DR form: " . $e->getMessage());
        }
    }

    public function saveDefects(int $hfId, array $defects, int $ppfno, int $encoder, string $inspectRec)
    {
        try {

            $drRows = [];
            $inspectorRows = [];

            foreach ($defects as $defect) {

                if (empty($defect['type'])) {
                    throw new \Exception("Defect type cannot be empty.");
                }

                $drRows[] = [
                    'hf_id' => $hfId,
                    'defect' => $defect['type'],
                    'qty' => $defect['qty'],
                    'updated_by' => $encoder,
                    'ppfno' => $ppfno,
                    'inspect_REC' => $inspectRec
                ];
            }

            $groupedDefects = collect($defects)
                ->groupBy('type')
                ->map(function ($items, $type) {
                    return [
                        'type' => $type,
                        'qty' => collect($items)->sum('qty')
                    ];
                })
                ->values();

            foreach ($groupedDefects as $defect) {

                $inspectorRows[] = [
                    'PPFNo' => $ppfno,
                    'InspectorID' => $encoder,
                    'insp_name' => $this->workerRepo->getWorkerName($encoder)->名前 ?? 'Unknown',
                    'Defect' => $defect['type'],
                    'Quantity' => $defect['qty'],
                    'DateEncode' => now(),
                    'Process' => 'HF',
                    'EncodeProcess' => 'reRework',
                ];
            }
            DB::table('dr_defect')->upsert(
                $drRows,
                ['hf_id', 'ppfno', 'defect', 'inspect_REC'],
                ['qty', 'updated_by', 'hf_id', 'ppfno']
            );

            DB::table('Inspector_Defect')->upsert(
                $inspectorRows,
                ['PPFNo',  'Defect', 'EncodeProcess', 'InspectorID'], // unique keys
                ['Quantity', 'insp_name', 'InspectorID', 'DateEncode']              // columns to update
            );
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save defects: " . $e->getMessage());
        }
    }

    public function saveSmallDefects(int $hfId, array $smalldefects, string $ppfno, string $encoder, string $inspectRec)
    {
        try {
            $drsmallrows = [];
            $inspectorSmallRows = [];
            foreach ($smalldefects as $large => $smalls) {
                foreach ($smalls as $small) {

                    if (empty($small['type'])) {
                        throw new \Exception("Small defect type for {$large} cannot be empty.");
                    }

                    $drsmallrows[] = [
                        'hf_id' => $hfId,
                        'large_defect' => $large,
                        'small_defect' => $small['type'],
                        'qty' => $small['qty'],
                        'updated_by' => $encoder,
                        'ppfno' => $ppfno,
                        'inspect_REC' => $inspectRec
                    ];
                }
            }

            $groupedSmall = collect($smalldefects)
                ->flatMap(function ($smalls, $large) {
                    return collect($smalls)->map(function ($small) use ($large) {
                        return [
                            'large' => $large,
                            'type' => $small['type'],
                            'qty' => $small['qty']
                        ];
                    });
                })
                ->groupBy(fn($item) => $item['large'] . '|' . $item['type'])
                ->map(function ($items) {
                    $first = $items->first();
                    return [
                        'large' => $first['large'],
                        'type' => $first['type'],
                        'qty' => $items->sum('qty')
                    ];
                })
                ->values();

            foreach ($groupedSmall as $small) {

                $inspectorSmallRows[] = [
                    'PPFNo' => $ppfno,
                    'InspectorID' => $encoder,
                    'LargeDefect' => $small['large'],
                    'SmallDefect' => $small['type'],
                    'Qty' => $small['qty'],
                    'Process' => 'HF',
                    'EncodeProcess' => 'reRework',
                ];
            }

            // ✅ Run UPSERT once
            if (!empty($drsmallrows)) {
                DB::table('dr_small')->upsert(
                    $drsmallrows,
                    ['hf_id', 'ppfno', 'large_defect', 'small_defect', 'inspect_REC'], // better unique key
                    ['qty', 'hf_id',]
                );
            }

            if (!empty($inspectorSmallRows)) {
                DB::table('Inspector_Small')->upsert(
                    $inspectorSmallRows,
                    ['LargeDefect', 'SmallDefect', 'EncodeProcess', 'InspectorID'],
                    ['Qty', 'InspectorID']
                );
            }
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save small defects: " . $e->getMessage());
        }
    }

    public function updateFlag($ppf)
    {
        try {
            Db::table('hf_rework')
                ->where('ppfno', $ppf)
                ->update([
                    'FlgDone' => 1
                ]);
        } catch (\Throwable $e) {
            throw new \Exception("Update to update Flag: " . $e->getMessage());
        }
    }

    public function fetchFlag($ppf)
    {
        try {
            $flgDone =  DB::table('hf_rework')
                ->where('PPFNo', $ppf)
                ->value('FlgDone');

            $proceedToRework = DB::table('hf_rework')
                ->where('PPFNo', $ppf)
                ->value('ProceedToRework');

            return [
                'FlgDone' => (bool) $flgDone,
                'ProceedToRework' => (bool) $proceedToRework
            ];
        } catch (\Throwable $e) {
            throw new \Exception("Failed to Fetch: " . $e->getMessage());
        }
    }

    public function deleteLargeDefect($ppf,$type,$formId){
        DB::table('dr_defect')->where('ppfno', $ppf)->where('defect', $type)->where('inspect_REC', $formId)->delete();
        DB::table('dr_small')->where('ppfno', $ppf)->where('large_defect', $type)->where('inspect_REC', $formId)->delete();
    }
     public function deleteSmallDefect($ppf,$large,$type,$formId){
        DB::table('dr_small')->where('ppfno',(int)$ppf)->where('small_defect', $type)->where('large_defect', $large)->where('inspect_REC', $formId)->delete();
    }
     public function deleteForm($formId, $ppfno){
        DB::table('dr_forms')->where('inspect_REC', $formId)->where('ppfno', $ppfno)->delete();
        DB::table('dr_defect')->where('inspect_REC', $formId)->where('ppfno', $ppfno)->delete();
        DB::table('dr_small')->where('inspect_REC', $formId)->where('ppfno', $ppfno)->delete();
    }
}
