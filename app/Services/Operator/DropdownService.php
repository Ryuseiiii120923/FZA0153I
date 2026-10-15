<?php

namespace App\Services\Operator;

use App\Repositories\Operator\DropDownRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DropdownService
{
    public function __construct(
        protected DropDownRepository $dropdownRepo,
    ) {}

    public function saveHF($formId, $forms, $hf_id, $total_inspect, $finishingProcedure = null)
    {
        $data = ['forms' => $forms];

        $validator = Validator::make(
            $data,
            [
                "forms.$formId.hf_id"          => 'required',
                "forms.$formId.total_inspect"  => 'required|numeric|min:1',
            ],
            [
                "forms.$formId.hf_id.required"         => 'HF ID is required!',
                "forms.$formId.total_inspect.required" => 'Total Inspect is required!',
                "forms.$formId.total_inspect.numeric"  => 'Total Inspect must be a number!',
                "forms.$formId.total_inspect.min"      => 'Total Inspect must be at least 1!',
            ],
            [
                "forms.$formId.hf_id"         => "HF ID",
                "forms.$formId.total_inspect" => "Total Inspect",
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $forms[$formId]['hf_id'] = $forms[$formId]['hf_id'] ?? $hf_id;
        $forms[$formId]['total_inspect'] = $forms[$formId]['total_inspect'] ?? $total_inspect;

        if (!is_null($finishingProcedure)) {
            $forms[$formId]['finishingProcedure'] = $forms[$formId]['finishingProcedure'] ?? $finishingProcedure;
        }

        return $forms;
    }

    public function checkHf($formId, $forms)
    {
        if (!$formId || !isset($forms[$formId])) {
            return ['error' => null, 'forms' => $forms];
        }

        $currentHfId = $forms[$formId]['hf_id'] ?? null;

        if (empty($currentHfId)) {
            $forms[$formId]['hf_name'] = null;
            return ['error' => 'HF ID cannot be empty', 'forms' => $forms];
        }

        $searchValue = strlen($currentHfId) === 2 ? ' ' . $currentHfId : $currentHfId;

        $hf = $this->dropdownRepo->findWorkerByCode($searchValue);

        if (!$hf) {
            $forms[$formId]['hf_name'] = null;
            return ['error' => 'This Operator does not exist', 'forms' => $forms];
        }

        $name = $this->dropdownRepo->findWorkerName($hf->社員CD);
        $forms[$formId]['hf_name'] = $name?->名前;

        return ['error' => null, 'forms' => $forms];
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

        $forms[$formId]['defects'] = $syncCollection(
            $forms[$formId]['defects'],
            $data['defects'] ?? [],
            $action,
            fn($d) => !empty($d['type'])
                ? strtolower(trim($d['type'])) . '_' . strtolower(trim($d['category'] ?? 'large'))
                : null
        );

        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {
            $forms[$formId]['smallDefects'][$large] = $syncCollection(
                $forms[$formId]['smallDefects'][$large] ?? [],
                $smalls,
                $action,
                fn($s) => !empty($s['type']) ? strtolower(trim($s['type'])) : null
            );
        }

        $forms[$formId]['rework'] = $syncCollection(
            $forms[$formId]['rework'] ?? [],
            $data['reworksData'] ?? [],
            $action,
            fn($r) => !empty($r['type'])
                ? strtolower(trim($r['type'])) . '_' . (int) ($r['hfno'] ?? 0)
                : null
        );

        return $forms;
    }

    /**
     * Pure array reconciliation — moved here from a private method on the
     * DropDown Livewire component so it can be reused as the callable
     * syncFormData() expects, without a Livewire dependency.
     */
    public function syncCollection(array $existing, array $incoming, string $action, callable $keyBuilder): array
    {
        $map = collect($existing)->keyBy($keyBuilder)->toArray();

        foreach ($incoming as $item) {
            $key = $keyBuilder($item);
            if (!$key) continue;

            match ($action) {
                'delete'        => (function () use (&$map, $key) { unset($map[$key]); })(),
                'update', 'add' => $map[$key] = $item,
                default         => null,
            };
        }

        return array_values($map);
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

    public function calcGoodQty($formId, $forms, $defectNg = [], $reworkNg = [])
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

        $totalNg = ($defectQty ?? 0) + ($reworkQty ?? 0);

        $forms[$formId]['GoodQty'] = ($form['total_inspect'] ?? 0) - $totalNg;
        $forms[$formId]['TotalNg'] = $totalNg;
        $forms[$formId]['TotalRework'] = $reworkQty;

        return [
            'GoodQty' => $forms[$formId]['GoodQty'],
            'TotalNg' => $forms[$formId]['TotalNg'],
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
            $defectsRaw = $this->dropdownRepo->getGroupedDefects($ppf, $inspectorId, $h->formId);

            $operatorDefects = $defectsRaw
                ->groupBy(fn($d) => strtolower(trim($d->defect)))
                ->map(function ($group) {
                    $first = $group->first();
                    return [
                        'id'   => $first->RECNO,
                        'type' => $first->defect,
                        'qty'  => $group->sum(fn($d) => $d->qty ?? 1),
                    ];
                })
                ->values()
                ->toArray();

            $defectNg[$h->hf_id] = collect($operatorDefects)->sum('qty');

            $reworksRaw = $this->dropdownRepo->getReworks($ppf, $inspectorId, $h->formId);

            $operatorRework = $reworksRaw->map(fn($r) => [
                'hfno'      => $r->hfid,
                'totalinsp' => $r->total_inspect ?? 0,
                'type'      => $r->rework_type,
                'quan'      => $r->qty ?? 1,
            ])->toArray();

            $reworkNg[$h->hf_id] = collect($operatorRework)->sum('quan');

            $smallDefectsRaw = $this->dropdownRepo->getSmallDefects($ppf, $inspectorId, $h->formId);

            $operatorSmallDefects = $smallDefectsRaw
                ->groupBy('largeDefect')
                ->mapWithKeys(fn($group, $largeDefect) => [
                    $largeDefect => collect($group)->map(fn($s) => [
                        'id'   => $s->RECNO,
                        'type' => $s->smallDefect,
                        'qty'  => $s->qty ?? 0,
                    ])->toArray(),
                ])
                ->toArray();

            $selectedLarge = array_key_first($operatorSmallDefects);

            $forms[$h->formId] = [
                'hf_id'               => $h->hfid,
                'ppfno'               => $h->ppfno,
                'total_inspect'       => $h->total_inspect,
                'finishingProcedure'  => $h->finishingProcedure ?? '',
                'open'                => true,
                'defects'             => $operatorDefects,
                'created_at'          => $h->created_at ?? null,
                'updated_date'        => $h->updated_at ?? null,
                'smallDefects'        => $operatorSmallDefects,
                'selectedLargeDefect' => $selectedLarge,
                'ForRework'           => (bool) $h->ForRework ?? null,
                'rework'              => $operatorRework,
                'formId'              => $h->formId ?? null,
                'Remarks'             => $h->remarks ?? null,
                'Operation'           => $h->operation ?? null,
                'Process'             => $h->process ?? null,
            ];
        }

        return compact('forms', 'defectNg', 'reworkNg');
    }

    /**
     * Builds a blank form entry. Replaces the 11 near-identical array
     * literals previously duplicated across the DropDown component's
     * addNew()/addNewHF()/addNewPL()/addNewSF()/addNewAuto*() methods.
     */
    public function buildFormTemplate(string $formId, array $overrides = []): array
    {
        return array_merge([
            'hf_id'              => '',
            'inspect_REC'        => uniqid(),
            'formId'             => $formId,
            'hf_name'            => '',
            'finishingProcedure' => '',
            'total_inspect'      => '',
            'open'               => true,
            'defects'            => [],
            'smallDefects'       => [],
            'rework'             => [],
            'ForRework'          => false,
            'TotalNg'            => [],
            'GoodQty'            => [],
            'TotalRework'        => [],
            'Remarks'            => '',
            'Operation'          => 'VI',
            'Process'            => '100% VI',
            'scroll_target'      => true,
        ], $overrides);
    }
}