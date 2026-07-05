<?php

namespace App\Services\PR;

use App\Models\Forms\Defect;
use App\Models\Forms\HF;
use App\Models\Forms\Rework;
use App\Models\Forms\SmallDefect;
use App\Models\Inspector\DefectInsp;
use App\Models\Inspector\PRInsp;
use App\Models\Inspector\ReworkInsp;
use App\Models\Inspector\SmallInsp;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitPrencodeService
{
    public array $hfRows = [], $defectRows = [], $smallRows = [], $reworkRows = [];
    public array $mergedDefects = [], $mergedSmallDefects = [], $mergedReworks = [];

    public string $operation = '';
    public int $totalGoodQty = 0, $totalNg = 0, $totalRework = 0;
    public string $ppfno = '', $updatedBy = '', $username = '';
    public string $inspName = '';
    public string $inspectorID = '';
    public int $updated_by = 0;


    public function submit(array $data): void
    {
        $this->prepareDataForSubmission($data);
        $this->saveHF();
        $this->saveInspectorRecord();
    }


    public function saveHF(): void
    {
        HF::upsert(
            $this->hfRows,
            ['hfid', 'ppfno', 'updated_by', 'inspId', 'formId'],
            ['total_inspect', 'goodQty', 'totalNg', 'remarks', 'finishingProcedure', 'ForRework', 'updated_at']
        );

        Defect::upsert(
            $this->defectRows,
            ['hfid', 'defect', 'ppfno', 'updated_by', 'inspId', 'formId'],
            ['qty']
        );

        SmallDefect::upsert(
            $this->smallRows,
            ['hfid', 'largeDefect', 'smallDefect', 'ppfno', 'updated_by', 'inspId', 'formId'],
            ['qty']
        );

        Rework::upsert(
            $this->reworkRows,
            ['hfid', 'ppfno', 'updated_by', 'inspId', 'rework_type', 'formId'],
            ['qty', 'total_inspect', 'updated_at']
        );
    }


    public function saveInspectorRecord(): void
    {
        $worker = Worker::with('employeeName')
            ->where('作業員CD', $this->inspectorID)
            ->first();
        $totalInspect = array_sum(
            array_column(
                array_filter($this->hfRows, function ($row) {
                    return ($row['forRework'] ?? 1) == 0
                        && (($row['methodProcess'] ?? $row['method'] ?? '') != 'PL')
                        && (($row['method'] ?? $row['methodProcess'] ?? '') != 'SF');
                }),
                'total_inspect'
            )
        );

        $inspRow  = [
            'inspectorId'   => $this->inspectorID,
            'ppfno'         => $this->ppfno,
            'total_inspect' => $totalInspect,
            'operation'     => 'VI',
            'totalNg'       => $this->totalNg,
            'totalRework'   => $this->totalRework,
            'totalGood'     => $this->totalGoodQty,
            'updated_by'     => $this->updatedBy,
            'updated_at'      => now(),
            'created_at'      => now(),
        ];

        PRInsp::upsert(
            $inspRow,
            ['inspectorId', 'ppfno','operation'],
            ['total_inspect', 'totalNg', 'totalRework', 'totalGood', 'updated_at']
        );
        // 🔵 MERGED DEFECTS
        $defectInspRows = [];
        foreach ($this->mergedDefects as $def) {

            $defectInspRows[] = [
                'inspName'     => $worker ? $worker->employeeName->名前 : '',
                'ppfno'         => $this->ppfno,
                'defect'        => $def['type'],
                'qty'      => $def['qty'],
                'inspectorId'   => $this->inspectorID,
                'process'       => $def['process'],
                'operation'     => 'VI',
                'updated_by' => $this->updatedBy

            ];
        }
        DefectInsp::upsert($defectInspRows, ['ppfno', 'defect', 'inspectorId', 'process', 'operation'], ['qty']);

        // 🔵 MERGED SMALL DEFECTS
        $smallInspRows = [];
        foreach ($this->mergedSmallDefects as $s) {

            $smallInspRows[] = [
                'inspectorId'   => $this->inspectorID,
                'ppfno'         => $this->ppfno,
                'large_defect'   => $s['large'],
                'small_defect'   => $s['type'],
                'qty'           => $s['qty'],
                'process'       => $s['process'],
                'operation'     => $this->operation,
                'updated_by' => $this->updatedBy
            ];
        }
        SmallInsp::upsert($smallInspRows, ['ppfno', 'large_defect', 'small_defect', 'inspectorId', 'process', 'operation'], ['qty']);

        // 🔵 MERGED REWORKS
        $reworkInspRows = [];
        foreach ($this->mergedReworks as $r) {
            $reworkInspRows[] = [
                'hfno' => $r['hfno'],
                'inspectorId'   => $this->inspectorID,
                'inspName'     => $worker ? $worker->employeeName->名前 : '',
                'ppfno'         => $this->ppfno,
                'rework'        => $r['type'],
                'qty'      => $r['qty'],
                'total_inspect'  => $r['totalinsp'],
                'process'       => $r['process'],
                'operation'     => $this->operation,
                'updated_by' => $this->updatedBy
            ];
        }
        ReworkInsp::upsert($reworkInspRows, ['ppfno', 'rework', 'inspectorId', 'process', 'operation'], ['qty', 'total_inspect']);
    }

    public function prepareDataForSubmission(array $data): void
    {
        $now = now();
        $this->updatedBy = $data['updated_by'] ?? "";
        $this->inspectorID = $data['inspectorId'] ?? "";
        $this->ppfno     = $data['ppfno'] ?? null;
        $this->inspName  = $data['username'] ?? '';
        foreach ($data['form'] as $formId => $formData) {
            $hf_id    = $formData['hf_id'] ?? null;
            $goodQty  = $goodQty = (int) ($formData['GoodQty'] ?? 0);
            $operation = $formData['Operation'] ?? null;
            $process   = $formData['Process'] ?? null;
            $totalng = 0;
            foreach ($formData['defects'] as $defects) {
                $totalng += $defects['qty'];
            }
            $this->totalGoodQty += $goodQty;
            $this->operation = $operation;
            $this->hfRows[] = [
                'hfid'              => $hf_id,
                'inspId'           => $this->inspectorID,
                'total_inspect'      => $formData['total_inspect'] ?? null,
                'updated_by'         => $this->updatedBy,
                'ppfno'              => $this->ppfno,
                'formId'             => $formData['formId'] ?? null, // ← from formData, like original
                'finishingProcedure' => $formData['finishingProcedure'] ?? null,
                'forRework'          => array_key_exists('ForRework', $formData)
                    ? ($formData['ForRework'] ? 1 : 0)
                    : null,
                'goodQty'            => $goodQty,
                'totalNg'            => $totalng,
                'process' => $process ?? null,
                'operation' => $operation ?? null,
                'remarks' => $formData['Remarks'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            // Inject process per-form (falls back to top-level $process if not set on formData)
            $formData['process'] = $formData['process'] ?? $process;
            $this->prepareDefect($formData, $hf_id);
            $this->prepareSmallDefect($formData, $formId, $hf_id, $data['needToDeleteDefect'] ?? [], $data['needToDeleteDefectSmall'] ?? []);
            $this->prepareRework($formData, $hf_id);
        }
    }


    public function prepareDefect(array $formData, ?string $hf_id): void
    {
        $process = $formData['process'] ?? null;

        foreach ($formData['defects'] ?? [] as $defect) {
            $type = $defect['type'] ?? null;
            $qty  = (float) ($defect['qty'] ?? 0);

            $this->totalNg += $qty;

            if (!$type || $qty <= 0) continue;

            $forRework = is_null($formData['ForRework'] ?? null)
                ? null
                : ($formData['ForRework'] ? 1 : 0);

            $this->defectRows[] = [
                'hfid'       => $hf_id,
                'inspId'     => $this->inspectorID,
                'defect'      => $type,
                'qty'         => $qty,
                'updated_by'  => $this->updatedBy,
                'ppfno'       => $this->ppfno,
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
                'process'   => $process,
            ];

            $this->mergedDefects[$key]['qty'] += $qty;
        }
    }


    public function prepareSmallDefect(
        array $formData,
        string $formId,
        ?string $hf_id,
        array $needDeleteDefect = [],
        array $needDeleteDefectSmall = []
    ): void {
        $process = $formData['process'] ?? null;

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
                    'hfid'        => $hf_id,
                    'largeDefect' => $large,
                    'smallDefect' => $type,
                    'qty'          => $qty,
                    'updated_by'   => $this->updatedBy,
                    'ppfno'        => $this->ppfno,
                    'inspId' => $this->inspectorID,
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
                    'process'   => $process,
                ];

                $this->mergedSmallDefects[$key]['qty'] += $qty;
            }
        }
    }


    public function prepareRework(array $formData, ?string $hf_id): void
    {
        $process = $formData['process'] ?? null;

        foreach ($formData['rework'] ?? [] as $rework) {
            $type     = $rework['type'] ?? null;
            $qty      = (float) ($rework['quan'] ?? 0);
            $hfno     = $rework['hfno'] ?? '';
            $totalInsp = (string) ($rework['totalinsp'] ?? 0);

            $this->totalRework += $qty;
            $this->totalNg     += $qty;

            if (!$type || $qty <= 0) continue;

            $this->reworkRows[] = [
                'hfid'       => $hf_id,
                'rework_type' => $type,
                'qty'         => $qty,
                'total_inspect'   => $totalInsp,
                'updated_by'  => $this->updatedBy,
                'ppfno'       => $this->ppfno,
                'formId'      => $formData['formId'] ?? null,
                'inspId'      => $this->inspectorID,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $key = $hfno . '_' . $type;

            $this->mergedReworks[$key] ??= [
                'hfno'      => $hfno,
                'type'      => $type,
                'totalinsp' => $rework['totalinsp'] ?? null,
                'qty'       => 0,
                'process'   => $process,
                'ForRework' => is_null($formData['ForRework'] ?? null)
                    ? null
                    : ($formData['ForRework'] ? 1 : 0),
            ];

            $this->mergedReworks[$key]['qty'] += $qty;
        }
    }
}
