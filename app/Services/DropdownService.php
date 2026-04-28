<?php

namespace App\Services;

use App\Models\Worker;
use App\Models\WorkerName;
use App\Repositories\DropDownRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DropdownService
{

    public function __construct(
        protected DropDownRepository $dropdownRepo,
    ) {}
    public function saveHF($formId, $forms, $hf_id, $total_inspect, $finishingProcedure = null)
    {
        $data = [
            'forms' => $forms,
        ];

        $validator = Validator::make(
            $data,
            [
                "forms.$formId.hf_id" => 'required',
                "forms.$formId.total_inspect" => 'required|numeric|min:1',
            ],
            [
                "forms.$formId.hf_id.required" => 'HF ID is required!',
                "forms.$formId.total_inspect.required" => 'Total Inspect is required!',
                "forms.$formId.total_inspect.numeric" => 'Total Inspect must be a number!',
                "forms.$formId.total_inspect.min" => 'Total Inspect must be at least 1!',
            ],
            [
                "forms.$formId.hf_id" => "HF ID",
                "forms.$formId.total_inspect" => "Total Inspect",
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Process data
        $forms[$formId]['hf_id'] = $forms[$formId]['hf_id'] ?? $hf_id;
        $forms[$formId]['total_inspect'] = $forms[$formId]['total_inspect'] ?? $total_inspect;

        if (!is_null($finishingProcedure)) {
            $forms[$formId]['finishingProcedure'] =
                $forms[$formId]['finishingProcedure'] ?? $finishingProcedure;
        }

        return $forms;
    }

    public function checkHf($formId, $forms)
    {
        if (!$formId || !isset($forms[$formId])) {
            return ['error' => null, 'forms' => $forms];
        }

        $currentHfId = $forms[$formId]['hf_id'] ?? null;
        $currentDate = now()->format('Y-m-d');

        if (empty($currentHfId)) {
            $forms[$formId]['hf_name'] = null;

            return [
                'error' => 'HF ID cannot be empty',
                'forms' => $forms
            ];
        }

        $searchValue = strlen($currentHfId) === 2
            ? ' ' . $currentHfId
            : $currentHfId;

        $hf = Worker::where('作業員CD', $searchValue)
            ->first();

        if (!$hf) {
            $forms[$formId]['hf_name'] = null;

            return [
                'error' => 'This Operator does not exist',
                'forms' => $forms
            ];
        }

        $name = WorkerName::where('社員CD', $hf->社員CD)->first();
        $forms[$formId]['hf_name'] = $name?->名前;

        // duplicate check
        // foreach ($forms as $id => $form) {
        //     if ($id === $formId) continue;

        //     $otherDate = isset($form['created_at'])
        //         ? Carbon::parse($form['created_at'])->format('Y-m-d')
        //         : null;

        //     if (
        //         isset($form['hf_id']) &&
        //         $form['hf_id'] === $currentHfId &&
        //         $currentDate === $otherDate
        //     ) {
        //         return [
        //             'error' => 'This Operator is already used in another form with the same date',
        //             'forms' => $forms
        //         ];
        //     }
        // }

        return [
            'error' => null,
            'forms' => $forms
        ];
    }

    public function syncFormData(array $forms, array $data, callable $syncCollection): array
    {
        $formId = $data['formId'] ?? null;
        if (!$formId || !isset($forms[$formId])) {
            return $forms;
        }

        $action = $data['action'] ?? 'add';

        $forms[$formId]['defects'] ??= [];
        $forms[$formId]['smallDefects'] ??= [];
        $forms[$formId]['rework'] ??= [];

        // DEFECTS
        $forms[$formId]['defects'] = $syncCollection(
            $forms[$formId]['defects'],
            $data['defects'] ?? [],
            $action,
            fn($d) =>
            !empty($d['type'])
                ? strtolower(trim($d['type'])) . '_' . strtolower(trim($d['category'] ?? 'large'))
                : null
        );

        // SMALL DEFECTS
        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {
            $forms[$formId]['smallDefects'][$large] = $syncCollection(
                $forms[$formId]['smallDefects'][$large] ?? [],
                $smalls,
                $action,
                fn($s) => !empty($s['type']) ? strtolower(trim($s['type'])) : null
            );
        }

        // REWORKS
        $forms[$formId]['rework'] = $syncCollection(
            $forms[$formId]['rework'] ?? [],
            $data['reworksData'] ?? [],
            $action,
            fn($r) =>
            !empty($r['type'])
                ? strtolower(trim($r['type'])) . '_' . (int)($r['hfno'] ?? 0)
                : null
        );

        return $forms;
    }

    public function receiveDropdownData($data, $dropdownForms = [])
    {
        foreach ($data as $formId => $formData) {

            if (!isset($dropdownForms[$formId])) {
                $dropdownForms[$formId] = [];
            }

            $dropdownForms[$formId]['defects'] = $formData['defects'] ?? [];
            $dropdownForms[$formId]['smallDefects'] = $formData['smallDefects'] ?? [];
            $dropdownForms[$formId]['rework'] = $formData['rework'] ?? [];

            foreach ($formData as $key => $value) {
                if (!in_array($key, ['defects', 'smallDefects', 'rework'])) {
                    $dropdownForms[$formId][$key] = $value;
                }
            }
        }

        return $dropdownForms;
    }

    public function calcGoodQty($formId, &$forms, $defectNg = [], $reworkNg = [])
    {
        if (!isset($forms[$formId])) {
            return null;
        }

        $form = $forms[$formId];

        $defectQty = isset($defectNg[$formId])
            ? $defectNg[$formId]
            : collect($form['defects'] ?? [])->sum('qty');

        $reworkQty = isset($reworkNg[$formId])
            ? $reworkNg[$formId]
            : collect($form['rework'] ?? [])->sum('quan');

        // ✅ FIXED calculation (keep this unless you REALLY want the bug)
        $totalNg = ($defectQty ?? 0) + ($reworkQty ?? 0);

        $forms[$formId]['GoodQty'] =
            ($form['total_inspect'] ?? 0) - $totalNg;

        $forms[$formId]['TotalNg'] = $totalNg;
        $forms[$formId]['TotalRework'] = $reworkQty;

        // ✅ EXACT same return format as your original
        return [
            $forms[$formId]['GoodQty'],
            $forms[$formId]['TotalNg']
        ];
    }

    public function editForms($ppf, $inspectorId)
    {
        $forms = [];
        $defectNg = [];
        $reworkNg = [];

        $hfRecords = $this->dropdownRepo->getByPpfAndInspector($ppf, $inspectorId);

        if ($hfRecords->isEmpty()) {
            return compact('forms', 'defectNg', 'reworkNg');
        }

        foreach ($hfRecords as $h) {

            // DEFECTS
            $defectsRaw = $this->dropdownRepo->getGroupedDefects($ppf, $inspectorId, $h->inspect_REC);

            $operatorDefects = $defectsRaw
                ->groupBy(fn($d) => strtolower(trim($d->defect)))
                ->map(function ($group) {
                    $first = $group->first();
                    return [
                        'id' => $first->RECNO,
                        'type' => $first->defect,
                        'qty' => $group->sum(fn($d) => $d->qty ?? 1),
                    ];
                })
                ->values()
                ->toArray();

            $defectNg[$h->hf_id] = collect($operatorDefects)->sum('qty');

            // REWORK
            $reworksRaw = $this->dropdownRepo->getReworks($ppf, $inspectorId, $h->inspect_REC);

            $operatorRework = $reworksRaw->map(fn($r) => [
                'id' => $r->RECNO,
                'hfno' => $r->hfno,
                'totalinsp' => $r->totalinsp ?? 0,
                'type' => $r->rework_type,
                'quan' => $r->qty ?? 1,
            ])->toArray();

            $reworkNg[$h->hf_id] = collect($operatorRework)->sum('quan');

            // SMALL DEFECTS
            $smallDefectsRaw = $this->dropdownRepo->getSmallDefects($ppf, $inspectorId, $h->inspect_REC);

            $operatorSmallDefects = $smallDefectsRaw
                ->groupBy('large_defect')
                ->mapWithKeys(function ($group, $largeDefect) {
                    return [
                        $largeDefect => collect($group)->map(fn($s) => [
                            'id' => $s->RECNO,
                            'type' => $s->small_defect,
                            'qty' => $s->qty ?? 0,
                        ])->toArray()
                    ];
                })
                ->toArray();

            $uniqueId = uniqid();
            $selectedLarge = array_key_first($operatorSmallDefects);

            $forms[$uniqueId] = [
                'id' => $h->RECNO,
                'inspect_REC' => $h->inspect_REC,
                'hf_id' => $h->hf_id,
                'ppfno' => $h->ppfno,
                'total_inspect' => $h->total_inspect,
                'finishingProcedure' => $h->finishingProcedure ?? '',
                'open' => true,
                'defects' => $operatorDefects,
                'created_at' => $h->created_at ?? null,
                'updated_date' => $h->updated_date ?? null,
                'smallDefects' => $operatorSmallDefects,
                'selectedLargeDefect' => $selectedLarge,
                'isRework' => (bool) $h->IsDoneRework,
                'ForRework' => (bool) $h->ForRework,
                'rework' => $operatorRework,
                'formId' => $h->formId ?? null,
            ];
        }
        return compact('forms', 'defectNg', 'reworkNg');
    }

    public function editFormsforFinishing($ppf, $inspectorId)
    {
        $forms = [];
        $defectNg = [];
        $reworkNg = [];

        $hfRecords = $this->dropdownRepo->getByPpfAndInspectorInFinishing($ppf, $inspectorId);

        if ($hfRecords->isEmpty()) {
            return compact('forms', 'defectNg', 'reworkNg');
        }

        foreach ($hfRecords as $h) {

            // DEFECTS
            $defectsRaw = $this->dropdownRepo->getGroupedDefectsforFinishing($ppf, $inspectorId, $h->inspect_REC);

            $operatorDefects = $defectsRaw
                ->groupBy(fn($d) => strtolower(trim($d->defect)))
                ->map(function ($group) {
                    $first = $group->first();
                    return [
                        'id' => $first->RECNO,
                        'type' => $first->defect,
                        'qty' => $group->sum(fn($d) => $d->qty ?? 1),
                    ];
                })
                ->values()
                ->toArray();

            $defectNg[$h->hf_id] = collect($operatorDefects)->sum('qty');

            // REWORK
            // SMALL DEFECTS
            $smallDefectsRaw = $this->dropdownRepo->getSmallDefectsforFinishing($ppf, $inspectorId, $h->inspect_REC);

            $operatorSmallDefects = $smallDefectsRaw
                ->groupBy('large_defect')
                ->mapWithKeys(function ($group, $largeDefect) {
                    return [
                        $largeDefect => collect($group)->map(fn($s) => [
                            'id' => $s->RECNO,
                            'type' => $s->small_defect,
                            'qty' => $s->qty ?? 0,
                        ])->toArray()
                    ];
                })
                ->toArray();

            $uniqueId = uniqid();
            $selectedLarge = array_key_first($operatorSmallDefects);

            $forms[$uniqueId] = [
                'id' => $h->RECNO,
                'inspect_REC' => $h->inspect_REC,
                'hf_id' => $h->hf_id,
                'ppfno' => $h->ppfno,
                'total_inspect' => $h->total_inspect,
                'open' => true,
                'defects' => $operatorDefects,
                'created_at' => $h->created_at ?? null,
                'updated_date' => $h->updated_date ?? null,
                'smallDefects' => $operatorSmallDefects,
                'selectedLargeDefect' => $selectedLarge,
            ];
        }

        return compact('forms', 'defectNg', 'reworkNg');
    }
}
