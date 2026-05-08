<?php

namespace App\Services\PR;

use Illuminate\Support\Facades\DB;

class SubmitPrencodeService
{
    public array $hfRows = [], $defectRows = [], $smallRows = [], $reworkRows = [];
    public array $mergedDefects = [], $mergedSmallDefects = [], $mergedReworks = [];

    public string $methodProcess = '';
    public int $totalGoodQty = 0, $totalNg = 0, $totalRework = 0;
    public string $ppfno = '', $updatedBy = '', $username = '';


    public function submit(array $data): void
    {
        $this->prepareDataForSubmission($data);
        $this->saveHF();
        $this->saveInspectorRecord();
    }


    public function saveHF(): void
    {
        DB::table('hf_forms')->upsert(
            $this->hfRows,
            ['hf_id', 'ppfno', 'updated_by', 'formId'],
            ['total_inspect', 'GoodQty']
        );

        DB::table('hf_defect')->upsert(
            $this->defectRows,
            ['hf_id', 'defect', 'ppfno', 'updated_by', 'formId'],
            ['qty']
        );

        DB::table('hf_small')->upsert(
            $this->smallRows,
            ['hf_id', 'large_defect', 'small_defect', 'ppfno', 'updated_by', 'formId'],
            ['qty']
        );

        DB::table('hf_rework')->upsert(
            $this->reworkRows,
            ['hfno', 'ppfno', 'updated_by', 'inspect_REC', 'rework_type', 'formId'],
            ['qty', 'totalinsp']
        );
    }


    public function saveInspectorRecord(): void
    {
        $totalInspect = array_sum(
            array_column(
                array_filter($this->hfRows, function ($row) {
                    return ($row['ForRework'] ?? 1) == 0
                        && (($row['methodProcess'] ?? $row['method'] ?? '') != 'PL')
                        && (($row['method'] ?? $row['methodProcess'] ?? '') != 'SF');
                }),
                'total_inspect'
            )
        );

        DB::table('Inspector_PR')->insert([
            'InspectorID'   => $this->updatedBy,
            'PPFNo'         => $this->ppfno,
            'total_inspect' => $totalInspect,
            'DateEncode'    => now(),
            'Process'       => $this->methodProcess,
            'TotalNg'       => $this->totalNg,
            'TotalRework'   => $this->totalRework,
            'TotalGood'     => $this->totalGoodQty,
        ]);

        // 🔵 MERGED DEFECTS
        $defectInspRows = [];
        foreach ($this->mergedDefects as $def) {
            $methodSave = ($def['ForRework'] == 0) ? 'iniInspect' : 'ReInspect';

            $defectInspRows[] = [
                'insp_name'     => $this->username,
                'PPFNo'         => $this->ppfno,
                'Defect'        => $def['type'],
                'Quantity'      => $def['qty'],
                'DateEncode'    => now(),
                'InspectorID'   => $this->updatedBy,
                'Process'       => $this->methodProcess,
                'EncodeProcess' => $methodSave ?? $this->methodProcess,
            ];
        }
        DB::table('Inspector_Defect')->insert($defectInspRows);

        // 🔵 MERGED SMALL DEFECTS
        $smallInspRows = [];
        foreach ($this->mergedSmallDefects as $s) {
            $encodeProcess = ($s['ForRework'] == 1) ? 'ReRework' : 'iniInspect';

            $smallInspRows[] = [
                'InspectorID'   => $this->updatedBy,
                'PPFNo'         => $this->ppfno,
                'LargeDefect'   => $s['large'],
                'SmallDefect'   => $s['type'],
                'Qty'           => $s['qty'],
                'Process'       => $this->methodProcess,
                'EncodeProcess' => $encodeProcess ?? $this->methodProcess,
            ];
        }
        DB::table('Inspector_Small')->insert($smallInspRows);

        // 🔵 MERGED REWORKS
        $reworkInspRows = [];
        foreach ($this->mergedReworks as $r) {
            $reworkInspRows[] = [
                'HFNo'          => $r['hfno'],
                'InspectorID'   => $this->updatedBy,
                'insp_name'     => $this->username,
                'PPFNo'         => $this->ppfno,
                'Defect'        => $r['type'],
                'Quantity'      => $r['qty'],
                'DateEncode'    => now(),
                'TotalInspQty'  => $r['totalinsp'],
                'Process'       => $this->methodProcess,
                'EncodeProcess' => $this->methodProcess ?? null,
            ];
        }
        DB::table('Inspector_Rework')->insert($reworkInspRows);
    }


    public function prepareDataForSubmission(array $data): void
    {
        dd($data['form']);
        $now = now();
        $this->updatedBy = $data['updated_by'] ?? null;
        $this->ppfno     = $data['ppfno'] ?? null;
        $this->username  = $data['username'] ?? '';

        foreach ($data['form'] as $formId => $formData) {
            $hf_id    = $formData['hf_id'] ?? null;
            $goodQty  = $formData['GoodQty'] ?? 0;
            $method   = $formData['method'] ?? null;
            $inspectREC = $formData['inspect_REC'] ?? null;

            $this->totalGoodQty += $goodQty;
            $this->methodProcess = $method ?? $this->methodProcess;

            $this->hfRows[] = [
                'hf_id'              => $hf_id,
                'total_inspect'      => $formData['total_inspect'] ?? null,
                'updated_by'         => $this->updatedBy,
                'ppfno'              => $this->ppfno,
                'inspect_REC'        => $inspectREC,
                'formId'             => $formData['formId'] ?? null, // ← from formData, like original
                'created_at'         => $now,
                'updated_date'       => $now,
                'IsDoneRework'       => 0,
                'finishingProcedure' => $formData['finishingProcedure'] ?? null,
                'ForRework'          => array_key_exists('ForRework', $formData)
                    ? ($formData['ForRework'] ? 1 : 0)
                    : null,
                'GoodQty'            => $goodQty,
                'methodProcess'      => $method ?? null,
            ];

            $this->prepareDefect($formData, $hf_id, $inspectREC);
            $this->prepareSmallDefect($formData, $formId, $hf_id, $inspectREC, $data['needToDeleteDefect'] ?? [], $data['needToDeleteDefectSmall'] ?? []);
            $this->prepareRework($formData, $formId, $hf_id, $inspectREC);
        }
    }


    public function prepareDefect(array $formData, ?string $hf_id, ?string $inspectREC): void
    {
        foreach ($formData['defects'] ?? [] as $defect) {
            $type = $defect['type'] ?? null;
            $qty  = (float) ($defect['qty'] ?? 0);

            $this->totalNg += $qty;

            if (!$type || $qty <= 0) continue;

            $forRework = is_null($formData['ForRework'] ?? null)
                ? null
                : ($formData['ForRework'] ? 1 : 0);

            $this->defectRows[] = [
                'hf_id'       => $hf_id,
                'defect'      => $type,
                'qty'         => $qty,
                'updated_by'  => $this->updatedBy,
                'ppfno'       => $this->ppfno,
                'inspect_REC' => $inspectREC,
                'formId'      => $formData['formId'] ?? null,
            ];

            $key = $type . '_' . (
                is_null($forRework)
                    ? ($formData['method'] ?? '')
                    : $forRework
            );

            $this->mergedDefects[$key] ??= [
                'type'      => $type,
                'qty'       => 0,
                'ForRework' => $forRework,
            ];

            $this->mergedDefects[$key]['qty'] += $qty;
        }
    }


    public function prepareSmallDefect(
        array $formData,
        string $formId,
        ?string $hf_id,
        ?string $inspectREC,
        array $needDeleteDefect = [],
        array $needDeleteDefectSmall = []
    ): void {
        foreach ($formData['smallDefects'] ?? [] as $large => $smalls) {
            if (isset($needDeleteDefect[$formId]) && in_array($large, $needDeleteDefect[$formId])) {
                continue;
            }

            foreach ($smalls as $small) {
                $type = $small['type'] ?? null;
                $qty  = (float) ($small['qty'] ?? 0);

                if (!$type || $qty <= 0) continue;

                if (isset($needDeleteDefectSmall[$formId])) {
                    foreach ($needDeleteDefectSmall[$formId] as $deletedSmall) {
                        if ($deletedSmall['largeDefect'] === $large && $deletedSmall['type'] === $type) {
                            continue 2;
                        }
                    }
                }

                $forRework = is_null($formData['ForRework'] ?? null)
                    ? null
                    : ($formData['ForRework'] ? 1 : 0);

                $this->smallRows[] = [
                    'hf_id'        => $hf_id,
                    'large_defect' => $large,
                    'small_defect' => $type,
                    'qty'          => $qty,
                    'updated_by'   => $this->updatedBy,
                    'ppfno'        => $this->ppfno,
                    'inspect_REC'  => $inspectREC,
                    'formId'       => $formData['formId'] ?? null,
                ];

                $key = $large . '_' . $type . '_' . (
                    is_null($forRework)
                        ? ($formData['method'] ?? '')
                        : $forRework
                );

                $this->mergedSmallDefects[$key] ??= [
                    'large'     => $large,
                    'type'      => $type,
                    'qty'       => 0,
                    'ForRework' => $forRework,
                ];

                $this->mergedSmallDefects[$key]['qty'] += $qty;
            }
        }
    }


    public function prepareRework(array $formData, string $formId, ?string $hf_id, ?string $inspectREC): void
    {
        foreach ($formData['rework'] ?? [] as $rework) {
            $type     = $rework['type'] ?? null;
            $qty      = (float) ($rework['quan'] ?? 0);   // ← 'quan' like original
            $hfno     = $rework['hfno'] ?? '';
            $totalInsp = (string) ($rework['totalinsp'] ?? 0);

            $this->totalRework += $qty;
            $this->totalNg     += $qty;

            if (!$type || $qty <= 0) continue;

            $this->reworkRows[] = [
                'hf_id'       => $hf_id,
                'hfno'        => $hfno,
                'rework_type' => $type,               // ← 'rework_type' like original
                'qty'         => $qty,
                'totalinsp'   => $totalInsp,
                'updated_by'  => $this->updatedBy,
                'ppfno'       => $this->ppfno,
                'inspect_REC' => $inspectREC,
                'formId'      => $formData['formId'] ?? null,
            ];

            $key = $hfno . '_' . $type;

            $this->mergedReworks[$key] ??= [
                'hfno'      => $hfno,
                'type'      => $type,
                'totalinsp' => $rework['totalinsp'] ?? null,
                'qty'       => 0,
                'ForRework' => is_null($formData['ForRework'] ?? null)
                    ? null
                    : ($formData['ForRework'] ? 1 : 0),
            ];

            $this->mergedReworks[$key]['qty'] += $qty;
        }
    }
}