<?php

use App\Repositories\Rework\ReworkRepository;
use App\Services\Helpers\DraftService;
use App\Services\Operator\DropdownService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $forms = [];
    public $toggles = false;
    public $hasError = false;
    public $hasErrorForm = [];
    public $isSaved = false;
    public $inspectorId = '';

    public $activeTab = 'worker';
    public $hf_id = '', $ppf;
    public $total_inspect = '';
    public $finishingProcedure = '';
    public array $modalOpen = [];
    public array $modalMode = [];

    public $expectedQty = 0;
    public $isCheckPPF;
    public $dropdownForms = [];
    public array $reworkNg = [];
    public array $defectNg = [];

    // ── Process groups (worker tab) ───────────────────────────
    // groups[groupId] = ['key' => ?string, 'locked' => bool]
    public array $groups  = [];
    // hfDraft[groupId] = ['hf_id' => '', 'total_inspect' => '', 'finishingProcedure' => '']
    public array $hfDraft = [];
    public array $groupStats = [];
    public array $processInput = [];

    protected DraftService $draft;

    protected const AUTO_METHODS = ['AUTO_DIM', 'AUTO_SF', 'AUTO_PLSF', 'AUTO_NG', 'AUTO_DIM_NG', 'AUTO_SF_NG'];


    protected const PROCESS_OPTIONS = [
        'VI100' => ['label' => '100% VI', 'color' => '#0F3C89', 'overrides' => []],
        'PL'    => [
            'label' => 'PL',
            'color' => '#02367B',
            'overrides' => ['Operation' => null, 'Process' => null, 'method' => 'PL', 'Remarks' => '']
        ],
        'SF'    => [
            'label' => 'SF',
            'color' => '#0284c7',
            'overrides' => ['Operation' => null, 'Process' => null, 'method' => 'SF', 'ForRework' => null, 'Remarks' => '']
        ],
        'SRW'   => [
            'label' => 'SRW (Done Rework)',
            'color' => '#22d3ee',
            'overrides' => ['ForRework' => true, 'Operation' => 'VI', 'Process' => 'SRW']
        ],
    ];



    public function mount(int $operatorID)
    {
        $this->inspectorId = $operatorID;
    }

    public function boot(DraftService $draft): void
    {
        $this->draft = $draft;
    }

    public function with(): array
    {
        return [
            'processOptions' => self::PROCESS_OPTIONS,
            'anchors'        => collect(array_keys($this->groups))
                ->mapWithKeys(fn($g) => [(string) $g => $this->anchorId((string) $g)])
                ->all(),
        ];
    }

    protected function draftKey(): string
    {
        return (string) $this->inspectorId;
    }

    private function syncDraft(array $forms): void
    {
        $this->draft->syncComponent($this->draftKey(), 'dropdown', [
            'forms' => $forms,
        ]);
    }

    // ═════════════════════════════════════════════════════════
    // Process groups: Add form -> Process -> HFs -> Defects
    // ═════════════════════════════════════════════════════════

    /**
     * The form that carries the whole process's defects/rework:
     * the first one that already has data, else the first HF.
     */
    protected function anchorId(string $groupId): ?string
    {
        $ids = $this->groupFormIds($groupId);

        if ($ids === []) {
            return null;
        }

        foreach ($ids as $id) {
            $f = $this->forms[$id];

            if (! empty($f['defects']) || ! empty($f['smallDefects']) || ! empty($f['rework'])) {
                return (string) $id;
            }
        }

        return (string) $ids[0];
    }

    /**
     * Good qty for the WHOLE process: sum of all HF total_inspect minus
     * the process's NG (defects + rework, held by the anchor).
     */
    protected function calcGroupGoodQty(string $groupId): void
    {
        $ids = $this->groupFormIds($groupId);

        if ($ids === []) {
            unset($this->groupStats[$groupId]);
            return;
        }

        $anchor     = $this->anchorId($groupId);
        $groupTotal = collect($ids)->sum(fn($id) => (int) ($this->forms[$id]['total_inspect'] ?? 0));

        // Let the service compute NG as usual, but against the process total
        $tmp                                = $this->forms;
        $tmp[$anchor]['total_inspect']      = $groupTotal;

        $result = app(DropdownService::class)->calcGoodQty($anchor, $tmp, $this->defectNg, $this->reworkNg);

        $this->groupStats[$groupId] = [
            'total_inspect' => $groupTotal,
            'TotalNg'       => $result['TotalNg'],
            'GoodQty'       => $result['GoodQty'],
        ];

        // Per-form values: NG sits on the anchor only; totals still add up to the process total
        foreach ($ids as $id) {
            $ng = ((string) $id === $anchor) ? (int) $result['TotalNg'] : 0;

            $this->forms[$id]['TotalNg'] = $ng;
            $this->forms[$id]['GoodQty'] = (int) ($this->forms[$id]['total_inspect'] ?? 0) - $ng;
        }
    }



    /**
     * Remove one HF row. If it's the anchor, hand its defects/rework to the next HF first.
     */
    public function removeHf(string $formId): void
    {
        $groupId = $this->forms[$formId]['group_id'] ?? null;

        if ($groupId && $this->anchorId($groupId) === $formId) {
            $next = collect($this->groupFormIds($groupId))->first(fn($id) => (string) $id !== $formId);

            if ($next !== null) {
                foreach (['defects', 'smallDefects', 'rework'] as $key) {
                    $this->forms[$next][$key] = $this->forms[$formId][$key] ?? [];
                }

                $this->reworkNg[$next] = $this->reworkNg[$formId] ?? 0;
                $this->defectNg[$next] = $this->defectNg[$formId] ?? 0;
            }
        }

        $this->remove($formId);

        if ($groupId) {
            $this->calcGroupGoodQty($groupId);
        }
    }

    protected function addGroup(?string $optionKey = null): ?string
    {
        if ($optionKey === 'SRW' && ! $this->canAddDoneRework()) {
            return null;
        }

        $groupId = (string) Str::uuid();

        $this->groups[$groupId]  = ['key' => $optionKey, 'locked' => false];
        $this->hfDraft[$groupId] = ['hf_id' => '', 'total_inspect' => '', 'finishingProcedure' => ''];
        $this->processInput[$groupId] = '';
        $this->toggles = true;

        return $groupId;
    }

    protected function canAddDoneRework(): bool
    {
        $result          = app(ReworkRepository::class)->fetchFlag((int) $this->ppf);
        $flgDone         = $result['FlgDone'] ?? false;
        $proceedToRework = $result['ProceedToRework'] ?? false;

        if ($flgDone && $proceedToRework) {
            return true;
        }

        if (! $proceedToRework) {
            $this->dispatch('errorExisting', 'Please confirm first in For Rework Table in Dashboard.');
            return false;
        }

        $this->dispatch('errorExisting', 'Please Encode first in HFRW.');
        return false;
    }

    protected function groupFormIds(string $groupId): array
    {
        return collect($this->forms)
            ->filter(fn($f) => ($f['group_id'] ?? null) === $groupId)
            ->keys()
            ->all();
    }

    protected function optionKeyForForm(array $form): string
    {
        if (! empty($form['ForRework'])) {
            return 'SRW';
        }

        $byMethod = match ($form['method'] ?? '') {
            'PL'    => 'PL',
            'SF'    => 'SF',
            default => null,
        };

        if ($byMethod !== null) {
            return $byMethod;
        }

        // A Process value that isn't the template's default = a typed process
        $process = trim((string) ($form['Process'] ?? ''));

        if ($process !== '' && $process !== $this->defaultProcess()) {
            return 'CUSTOM';
        }

        return 'VI100';
    }
    protected function groupOverrides(array $group): array
    {
        if (($group['key'] ?? null) === 'CUSTOM') {
            return ['Operation' => 'VI', 'Process' => $group['label']];
        }

        return self::PROCESS_OPTIONS[$group['key']]['overrides'] ?? [];
    }

    /**
     * Process value the template gives a plain 100% VI form.
     */
    protected function defaultProcess(): string
    {
        return trim((string) (app(DropdownService::class)->buildFormTemplate('default', [])['Process'] ?? ''));
    }

    // Step 1 — choose process
    public function selectProcess(string $groupId, string $key): void
    {
        if (! isset($this->groups[$groupId]) || ! isset(self::PROCESS_OPTIONS[$key])) {
            return;
        }

        // Process can't be changed once HFs were encoded under it
        if ($this->groupFormIds($groupId) !== []) {
            return;
        }

        if ($key === 'SRW' && ! $this->canAddDoneRework()) {
            return;
        }

        $this->groups[$groupId]['key'] = $key;
        $this->groups[$groupId]['label'] = self::PROCESS_OPTIONS[$key]['label'];
    }

    public function changeProcess(string $groupId): void
    {
        if (! isset($this->groups[$groupId]) || $this->groupFormIds($groupId) !== []) {
            return;
        }

        $this->groups[$groupId]['key']   = null;
        $this->groups[$groupId]['label'] = null;
    }

    public function addCustomProcess(string $groupId): void
    {
        if (! isset($this->groups[$groupId]) || $this->groupFormIds($groupId) !== []) {
            return;
        }

        $errKey = "processInput.$groupId";
        $this->resetErrorBag($errKey);

        $name = trim(preg_replace('/\s+/', ' ', (string) ($this->processInput[$groupId] ?? '')));

        if ($name === '') {
            $this->addError($errKey, 'Type the process name.');
            return;
        }

        if (mb_strlen($name) > 50) {
            $this->addError($errKey, 'Process name must not exceed 50 characters.');
            return;
        }

        // Typed name matches a preset -> use the preset
        foreach (self::PROCESS_OPTIONS as $key => $opt) {
            if (mb_strtolower($opt['label']) === mb_strtolower($name) || mb_strtolower($key) === mb_strtolower($name)) {
                $this->selectProcess($groupId, $key);
                $this->processInput[$groupId] = '';
                return;
            }
        }

        $this->groups[$groupId]['key']   = 'CUSTOM';
        $this->groups[$groupId]['label'] = $name;
        $this->processInput[$groupId]    = '';
    }

    public function addHf(string $groupId): void
    {
        $group = $this->groups[$groupId] ?? null;

        if (! $group || $group['key'] === null || $group['locked']) {
            return;
        }

        $errHf    = "hfDraft.$groupId.hf_id";
        $errTotal = "hfDraft.$groupId.total_inspect";
        $this->resetErrorBag([$errHf, $errTotal]);

        $draft = $this->hfDraft[$groupId] ?? [];
        $hfId  = trim((string) ($draft['hf_id'] ?? ''));
        $total = trim((string) ($draft['total_inspect'] ?? ''));
        $fp    = trim((string) ($draft['finishingProcedure'] ?? ''));

        if ($hfId === '') {
            $this->addError($errHf, 'Enter the HF ID.');
            return;
        }

        if (! ctype_digit($total) || (int) $total < 1) {
            $this->addError($errTotal, 'Total inspect must be a number of at least 1.');
            return;
        }

        $duplicate = collect($this->forms)->contains(
            fn($f) => ($f['group_id'] ?? null) === $groupId && (string) ($f['hf_id'] ?? '') === $hfId
        );

        if ($duplicate) {
            $this->addError($errHf, "HF {$hfId} is already added in this process.");
            return;
        }

        $formId    = (string) Str::uuid();
        $overrides = array_merge(
            $this->groupOverrides($group),
            ['hf_id' => $hfId]          // seed the HF ID into the template itself
        );

        $this->forms[$formId] = app(DropdownService::class)->buildFormTemplate($formId, $overrides);

        // Same state the old hf-modal had before saveHF() ran
        $this->forms[$formId]['hf_id']              = $hfId;
        $this->forms[$formId]['total_inspect']      = $total;
        $this->forms[$formId]['finishingProcedure'] = $fp;
        $this->forms[$formId]['group_id']           = $groupId;

        try {
            $this->forms = app(DropdownService::class)->saveHF($formId, $this->forms, $hfId, $total, $fp);
        } catch (ValidationException $e) {
            unset($this->forms[$formId]);
            $this->addError($errHf, $e->validator->errors()->first());
            return;
        }

        $this->modalOpen[$formId] = false;

        $this->CheckHf($formId);       // fills hf_name, flags invalid HF on the row
        $this->CalcGoodQty($formId);
        $this->receiveDropdownData($this->forms);

        $this->hfDraft[$groupId] = ['hf_id' => '', 'total_inspect' => '', 'finishingProcedure' => $fp];
    }

    public function lockGroup(string $groupId): void
    {
        if (! isset($this->groups[$groupId])) {
            return;
        }

        $errHf = "hfDraft.$groupId.hf_id";
        $this->resetErrorBag($errHf);

        $ids = $this->groupFormIds($groupId);

        if ($ids === []) {
            $this->addError($errHf, 'Add at least one HF before encoding defects.');
            return;
        }

        foreach ($ids as $id) {
            if ($this->hasErrorForm[$id] ?? false) {
                $this->addError($errHf, 'Fix the HF errors first.');
                return;
            }
        }

        $this->groups[$groupId]['locked'] = true;
        $this->calcGroupGoodQty($groupId);

        // Prefill the rework modal (one rework component per process, bound to the anchor)
        $anchorId = $this->anchorId($groupId);

        $this->dispatch(
            'FetchHfNo',
            hf_id: $this->forms[$anchorId]['hf_id'] ?? '',
            total_inspect: (int) ($this->forms[$anchorId]['total_inspect'] ?? 0),
            form_id: $anchorId
        );

        $this->receiveDropdownData($this->forms);
    }

    public function unlockGroup(string $groupId): void
    {
        if (isset($this->groups[$groupId])) {
            $this->groups[$groupId]['locked'] = false;
        }
    }

    public function removeGroup(string $groupId): void
    {
        foreach ($this->groupFormIds($groupId) as $id) {
            $this->remove($id);
        }

        unset($this->groups[$groupId], $this->hfDraft[$groupId], $this->processInput[$groupId], $this->groupStats[$groupId]);
        $this->resetErrorBag(["hfDraft.$groupId.hf_id", "hfDraft.$groupId.total_inspect", "processInput.$groupId"]);
    }

    // ═════════════════════════════════════════════════════════
    // Add buttons (names kept so <x-ui.dropdown.tab-header /> still works)
    // ═════════════════════════════════════════════════════════

    public function addNew()
    {
        $this->addGroup();
    }
    public function addNewHF()
    {
        $this->addGroup();
    }
    public function addNewPL()
    {
        $this->addGroup('PL');
    }
    public function addNewSF()
    {
        $this->addGroup('SF');
    }
    public function addNewDoneRework()
    {
        $this->addGroup('SRW');
    }

    // ═════════════════════════════════════════════════════════
    // Auto forms — unchanged (old flow, no process group)
    // ═════════════════════════════════════════════════════════

    protected function addForm(array $overrides = [], string $seedHfId = ''): string
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();

        $overrides = array_merge(['hf_id' => $seedHfId], $overrides);

        $this->forms[$formId] = app(DropdownService::class)
            ->buildFormTemplate($formId, $overrides);

        $this->modalOpen[$formId] = true;
        $this->dispatch('scroll-to-form', formId: $formId);

        return $formId;
    }

    public function addNewAutoDimension()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto Dimension Checking', 'method' => 'AUTO_DIM']);
    }

    public function addNewAutoSF()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto SF Inspection', 'method' => 'AUTO_SF']);
    }

    public function addNewAutoPLSF()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto PL/SF Inspection', 'method' => 'AUTO_PLSF']);
    }

    public function addNewAutoNG()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto NG Checking', 'method' => 'AUTO_NG']);
    }

    public function addNewAutoDimNG()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto Dimension of VI Good from NG', 'method' => 'AUTO_DIM_NG']);
    }

    public function addNewAutoSFNG()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto SF Dimension of VI Good from NG', 'method' => 'AUTO_SF_NG']);
    }

    // ═════════════════════════════════════════════════════════
    // Shared (same as the old drop-down)
    // ═════════════════════════════════════════════════════════

    public function saveRemarks($formId)
    {
        $remarks = $this->forms[$formId]['Remarks'] ?? '';
        $this->forms[$formId]['Remarks'] = trim((string) $remarks);
        $this->receiveDropdownData($this->forms);
    }

    #[On('fetchppf')]
    public function fetchppf($data)
    {
        $this->ppf = $data;
    }

    public function updatedForms()
    {
        $this->syncDraft($this->forms);
    }

    #[On('IsCheckPPF')]
    public function IsCheckPPF($data)
    {
        $this->isCheckPPF = $data;
    }

    public function clearScrollTarget(string $formId): void
    {
        if (isset($this->forms[$formId])) {
            $this->forms[$formId]['scroll_target'] = false;
        }
    }

    #[On('expected')]
    public function expectedQty($data)
    {
        $this->expectedQty = $data;
    }

    public function saveHF($formId)
    {
        try {
            $this->forms = app(DropdownService::class)->saveHF(
                $formId,
                $this->forms,
                $this->forms[$formId]['hf_id'] ?? '',
                $this->forms[$formId]['total_inspect'] ?? '',
                $this->forms[$formId]['finishingProcedure'] ?? ''
            );

            $this->modalOpen[$formId] = false;

            $this->dispatch(
                'FetchHfNo',
                hf_id: $this->forms[$formId]['hf_id'],
                total_inspect: (int) $this->forms[$formId]['total_inspect'],
                form_id: $formId
            );

            $this->CalcGoodQty($formId);
            $this->receiveDropdownData($this->forms);

            $this->hf_id = '';
            $this->total_inspect = '';
            $this->finishingProcedure = '';
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    public function exitHF($formId)
    {
        if (!empty($this->forms[$formId]['hf_id'])) {
            $this->modalOpen[$formId] = false;
            return;
        }

        $this->modalOpen[$formId] = false;

        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);

        $this->hf_id = '';
        $this->total_inspect = '';
    }

    #[On('edit-ppf')]
    public function editPPFFromChild(int $ppf, string $inspectorId)
    {
        Log::info('in Edit PPF:', [
            'ppf' => $ppf,
            'inspectorId' => $this->inspectorId
        ]);
        $this->defectNg = [];

        $data = app(DropdownService::class)->editForms($ppf, $this->inspectorId);
        if (empty($data['forms'])) return;

        $this->toggles = true;
        $this->forms = $data['forms'];
        $this->defectNg = $data['defectNg'];
        $this->reworkNg = $data['reworkNg'];

        $hasAuto = false;

        foreach ($this->forms as $id => $form) {
            if (in_array($form['method'] ?? '', self::AUTO_METHODS)) {
                continue;
            }

            $key   = $this->optionKeyForForm($form);
            $label = $key === 'CUSTOM'
                ? trim((string) $form['Process'])
                : self::PROCESS_OPTIONS[$key]['label'];

            // One group per process; typed processes get one group per distinct name
            $groupId = $key === 'CUSTOM'
                ? 'g-CUSTOM-' . md5(mb_strtolower($label))
                : 'g-' . $key;

            $this->groups[$groupId]       ??= ['key' => $key, 'label' => $label, 'locked' => true];
            $this->hfDraft[$groupId]      ??= ['hf_id' => '', 'total_inspect' => '', 'finishingProcedure' => ''];
            $this->processInput[$groupId] ??= '';
            $this->forms[$id]['group_id'] = $groupId;
        }

        // Rebuild process groups from the loaded forms (already locked = defects visible)
        $this->groups  = [];
        $this->hfDraft = [];

        foreach ($this->forms as $id => $form) {
            if (in_array($form['method'] ?? '', self::AUTO_METHODS)) {
                continue;
            }

            $key     = $this->optionKeyForForm($form);
            $groupId = 'g-' . $key;

            $this->groups[$groupId]  ??= ['key' => $key, 'locked' => true];
            $this->hfDraft[$groupId] ??= ['hf_id' => '', 'total_inspect' => '', 'finishingProcedure' => ''];
            $this->forms[$id]['group_id'] = $groupId;
        }
        foreach (array_keys($this->groups) as $groupId) {
            $this->calcGroupGoodQty((string) $groupId);
        }

        $this->activeTab = $hasAuto ? 'auto' : 'worker';

        $this->syncDraft($this->forms);
        Log::info('Forms in Edit PPF:', [
            'forms' => $this->forms
        ]);
    }

    public function editHF($formId)
    {
        $this->modalMode[$formId] = 'edit';
        $this->modalOpen[$formId] = true;
    }

    public function CloseModal($formId)
    {
        $this->modalOpen[$formId] = false;
    }

    public function CheckHf($formId)
    {
        $result = app(DropdownService::class)->checkHf($formId, $this->forms);
        $this->forms = $result['forms'];

        if ($result['error']) {
            $this->addError('forms.' . $formId . '.hf_id', $result['error']);
            $this->hasErrorForm[$formId] = true;
        } else {
            $this->resetErrorBag('forms.' . $formId . '.hf_id');
            $this->hasErrorForm[$formId] = false;
        }

        $this->hasError = $this->hasErrorForm;
        $this->dispatch('hasErrorPren', [
            'message'      => $result['error'],
            'hasError'     => $this->hasError,
            'hasErrorForm' => $this->hasErrorForm,
        ]);
    }

    #[On('operator.defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $this->isSaved = false;

        $this->forms = app(DropdownService::class)->syncFormData(
            $this->forms,
            $data,
            fn($existing, $incoming, $action, $keyResolver) =>
            app(DropdownService::class)->syncCollection($existing, $incoming, $action, $keyResolver)
        );

        $formId = $data['formId'] ?? null;

        if ($formId) {
            $this->reworkNg[$formId] = collect($this->forms[$formId]['rework'] ?? [])->sum('quan');
            $this->CalcGoodQty($formId);
        }

        $this->receiveDropdownData($this->forms);
    }

    public function receiveDropdownData($data)
    {
        $this->dropdownForms = app(DropdownService::class)
            ->receiveDropdownData($data, $this->dropdownForms);

        $this->syncDraft($this->forms);
        $this->dispatch('dropdown-updated', ['forms' => $this->dropdownForms]);
    }

    public function CalcGoodQty($formId)
    {
        $groupId = $this->forms[$formId]['group_id'] ?? null;

        if ($groupId) {
            $this->calcGroupGoodQty($groupId);
            return;
        }

        $result = app(DropdownService::class)->calcGoodQty(
            $formId,
            $this->forms,
            $this->defectNg,
            $this->reworkNg
        );

        $this->forms[$formId]['GoodQty'] = $result['GoodQty'];
        $this->forms[$formId]['TotalNg'] = $result['TotalNg'];
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }

    private function trackDeletedForm(string $formId, array $formData): void
    {
        $draft = $this->draft->load($this->draftKey());
        $needToDelete = $draft['needToDeleteForm'] ?? [];

        $needToDelete[$formId] = $formData;

        $this->draft->syncComponent($this->draftKey(), 'needToDeleteForm', $needToDelete);
    }

    public function remove($formId)
    {
        if (!isset($this->forms[$formId])) return;

        $groupId = $this->forms[$formId]['group_id'] ?? null;

        $this->dispatch('removeError', $formId);
        $this->dispatch('NeedToDeleteForm', $this->forms[$formId]);

        $this->trackDeletedForm($formId, $this->forms[$formId]);

        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);
        $this->resetErrorBag('forms.' . $formId);
        unset($this->hasErrorForm[$formId]);

        $this->forms = [...$this->forms];

        // Last HF of a locked group removed -> back to the HF step
        if ($groupId && isset($this->groups[$groupId]) && $this->groupFormIds($groupId) === []) {
            $this->groups[$groupId]['locked'] = false;
        }

        $this->syncDraft($this->forms);
    }

    #[On('action-changed')]
    public function actionChanged()
    {
        $this->forms        = [];
        $this->groups       = [];
        $this->hfDraft      = [];
        $this->processInput = [];
        $this->groupStats   = [];
    }
};
?>

<div x-data="{
    showFixed: false,
    activeTab: @entangle('activeTab')
}"
    x-init="
    if (!activeTab) { activeTab = 'worker'; }
    const container = document.getElementById('prencode-scroll-container');
    const target = container ?? window;
    target.addEventListener('scroll', () => {
        showFixed = (container ? container.scrollTop : window.scrollY) > 260;
    });

    $wire.on('scroll-to-form', ({ formId }) => {
        $nextTick(() => {
            setTimeout(() => {
                const el = document.querySelector('[wire\\:key=\worker-form-' + formId + '\]');
                if (!el) return;
                const container = document.getElementById('prencode-scroll-container');
                if (container) {
                    const containerRect = container.getBoundingClientRect();
                    const elRect = el.getBoundingClientRect();
                    container.scrollTo({
                        top: container.scrollTop + elRect.top - containerRect.top - 20,
                        behavior: 'smooth'
                    });
                } else {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 150);
        });
    });
">
    <x-ui.dropdown.tab-header />

    @php $autoMethods = ['AUTO_DIM','AUTO_SF','AUTO_PLSF','AUTO_NG','AUTO_DIM_NG','AUTO_SF_NG']; @endphp

    <div class="space-y-4 mt-10">

        {{-- ═════════ WORKER TAB: process groups ═════════ --}}
        <div x-show="activeTab !== 'auto'" class="space-y-6">

            @forelse($groups as $groupId => $group)
            @php
            $option = $group['key'] ? ($processOptions[$group['key']] ?? null) : null;
            $groupForms = collect($forms)->filter(fn ($f) => ($f['group_id'] ?? null) === $groupId);
            $step = $group['key'] === null ? 1 : ($group['locked'] ? 3 : 2);
            $hideFp = in_array($option['overrides']['method'] ?? '', ['PL', 'SF']);
            $headerColor = $option['color'] ?? ($group['key'] ? '#4f46e5' : '#374151');
            @endphp

            <div wire:key="process-group-{{ $groupId }}" class="border rounded shadow bg-white overflow-hidden">

                {{-- Group header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-white"
                    @style(['background-color:' . $headerColor])>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold">
                            {{ $group['label'] ?? 'New Form — choose process' }}
                        </span>
                        @if($groupForms->isNotEmpty())
                        <span class="text-xs px-2 py-0.5 rounded bg-white/20">
                            {{ $groupForms->count() }} {{ Str::plural('HF', $groupForms->count()) }}
                        </span>
                        @endif
                    </div>

                    {{-- Steps --}}
                    <div class="flex items-center gap-2 text-xs font-medium">
                        @foreach([1 => 'Process', 2 => 'HF', 3 => 'Defects'] as $n => $label)
                        <span @class([ 'px-2.5 py-1 rounded-full' , 'bg-white text-gray-800'=> $step === $n,
                            'bg-white/20 text-white' => $step !== $n,
                            ])>{{ $n }}. {{ $label }}</span>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        @if($step === 2 && $groupForms->isEmpty())
                        <button type="button" wire:click="changeProcess('{{ $groupId }}')"
                            class="rounded px-3 py-1 bg-white/10 hover:bg-white/20 text-sm">Change process</button>
                        @endif
                        @if($step === 3)
                        <button type="button" wire:click="unlockGroup('{{ $groupId }}')"
                            class="rounded px-3 py-1 bg-white/10 hover:bg-white/20 text-sm">Back to HFs</button>
                        @endif
                        <button type="button"
                            wire:click="removeGroup('{{ $groupId }}')"
                            wire:confirm="Remove this process and all its HFs?"
                            class="rounded px-3 py-1 bg-red-600 hover:bg-red-700 text-sm">Remove</button>
                    </div>
                </div>

                <div class="p-4">

                    {{-- STEP 1: choose or type process --}}
                    @if($step === 1)
                    <p class="text-sm text-gray-600 mb-3">Choose the process to encode.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($processOptions as $key => $opt)
                        <button type="button"
                            wire:key="opt-{{ $groupId }}-{{ $key }}"
                            wire:click="selectProcess('{{ $groupId }}', '{{ $key }}')"
                            class="border border-gray-200 rounded-xl p-4 text-left text-sm font-semibold text-gray-800
               hover:border-blue-300 hover:bg-blue-50 transition-colors duration-150">
                            {{ $opt['label'] }}
                        </button>
                        @endforeach
                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or type a process</label>
                        <div class="flex items-center gap-2">
                            <input type="text" maxlength="50"
                                wire:model="processInput.{{ $groupId }}"
                                wire:keydown.enter="addCustomProcess('{{ $groupId }}')"
                                placeholder="e.g. Final Inspection"
                                class="flex-1 max-w-sm border border-gray-300 p-2 rounded text-sm text-gray-800
                   focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" wire:click="addCustomProcess('{{ $groupId }}')"
                                class="px-4 py-2 text-sm font-semibold bg-[#0F3C89] text-white rounded-lg
                   hover:bg-blue-800 transition-colors shadow-sm">
                                Use Process
                            </button>
                        </div>
                        @error('processInput.' . $groupId) <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif
                    {{-- STEP 2: encode HFs --}}
                    @if($step === 2)
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">HF ID</label>
                            <input type="text" maxlength="4"
                                wire:model="hfDraft.{{ $groupId }}.hf_id"
                                wire:keydown.enter="addHf('{{ $groupId }}')"
                                placeholder="Enter HF ID"
                                class="w-full border border-gray-300 p-2 rounded text-sm text-gray-800
                                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Inspect</label>
                            <input type="number" min="1"
                                wire:model="hfDraft.{{ $groupId }}.total_inspect"
                                wire:keydown.enter="addHf('{{ $groupId }}')"
                                placeholder="Enter Total Inspect"
                                class="w-full border border-gray-300 p-2 rounded text-sm text-gray-800
                                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <button type="button" wire:click="addHf('{{ $groupId }}')" wire:loading.attr="disabled"
                                class="w-full px-4 py-2 text-sm font-semibold bg-[#0F3C89] text-white rounded-lg
                                       hover:bg-blue-800 transition-colors shadow-sm">
                                + Add HF
                            </button>
                        </div>
                    </div>

                    @error('hfDraft.' . $groupId . '.hf_id') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
                    @error('hfDraft.' . $groupId . '.total_inspect') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror

                    {{-- Encoded HFs --}}
                    <div class="mt-4 space-y-2">
                        @forelse($groupForms as $formId => $form)
                        <div wire:key="hf-row-{{ $formId }}"
                            class="flex items-center justify-between gap-3 px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-gray-800">
                                <span class="font-semibold">HF #{{ $form['hf_id'] ?? '' }}</span>
                                @if(!empty($form['hf_name']))<span>{{ $form['hf_name'] }}</span>@endif
                                <span class="text-gray-500">Total Inspect: {{ $form['total_inspect'] ?? '' }}</span>
                                @if(!empty($form['finishingProcedure']))
                                <span class="text-gray-500">{{ $form['finishingProcedure'] }}</span>
                                @endif
                            </div>
                            <button type="button" wire:click="removeHf('{{ $formId }}')"
                                class="rounded px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm">Remove</button>
                        </div>
                        @error('forms.' . $formId . '.hf_id')
                        <p class="text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                        @empty
                        <p class="text-sm text-gray-400">No HF encoded yet for this process.</p>
                        @endforelse
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="button" wire:click="lockGroup('{{ $groupId }}')"
                            @disabled($groupForms->isEmpty())
                            class="px-6 py-2.5 text-sm font-semibold rounded-lg bg-green-700 text-white hover:bg-green-800
                            disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Done HFs, Encode Defects
                        </button>
                    </div>
                    @endif

                    {{-- STEP 3: ONE defects + rework for the whole process --}}
                    @if($step === 3)
                    @php
                    $anchorId = $anchors[$groupId] ?? null;
                    $anchorForm = $anchorId ? ($forms[$anchorId] ?? []) : [];
                    $loadedDefects = $anchorForm['defects'] ?? [];
                    $loadedSmallDefects = $anchorForm['smallDefects'] ?? [];
                    $loadedRework = $anchorForm['rework'] ?? [];
                    $stats = $groupStats[$groupId] ?? [];
                    @endphp

                    @if($anchorId)
                    <div class="space-y-4">

                        {{-- HFs of this process (read-only) --}}
                        <div class="flex flex-wrap gap-2">
                            @foreach($groupForms as $formId => $form)
                            <span wire:key="hf-chip-{{ $formId }}"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg bg-gray-100 border border-gray-200 text-gray-800">
                                <span class="font-semibold">HF #{{ $form['hf_id'] ?? '' }}</span>
                                @if(!empty($form['hf_name']))<span>{{ $form['hf_name'] }}</span>@endif
                                <span class="text-gray-500">Inspect: {{ $form['total_inspect'] ?? '' }}</span>
                            </span>
                            @endforeach
                        </div>

                        <div class="text-sm font-medium text-gray-700">
                            Total Inspect (all HFs): {{ $stats['total_inspect'] ?? 0 }}
                        </div>

                        {{-- Defects + Rework (shared by every HF in this process) --}}
                        <div class="flex flex-col sm:flex-row gap-6 mt-4">
                            <div class="w-full sm:w-1/2 flex justify-center">
                                <livewire:partials::defects
                                    :formId="$anchorId"
                                    :loadedDefects="$loadedDefects"
                                    :loadedSmallDefects="$loadedSmallDefects"
                                    :dispatchPrefix="'operator'"
                                    :key="'defects-'.$anchorId"
                                    :systemname="'operator'" />
                            </div>
                            <div class="w-full sm:w-1/2 flex justify-center">
                                <livewire:partials::rework
                                    :formId="$anchorId"
                                    :loadedRework="$loadedRework"
                                    :dispatchPrefix="'operator'"
                                    :key="'reworks-'.$anchorId"
                                    :systemname="'operator'" />
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700">Total Good Qty. (whole process)</label>
                            <input type="text" readonly value="{{ $stats['GoodQty'] ?? '' }}"
                                class="w-full border border-gray-300 p-2 rounded bg-gray-50 text-gray-800">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium text-gray-700">Remarks</label>
                            <div x-data="{
            saveTimer: null,
            buttonTimer: null,
            typing() {
                window.dispatchEvent(new CustomEvent('remarks-typing', { detail: { disabled: true } }));
                clearTimeout(this.saveTimer);
                clearTimeout(this.buttonTimer);
                this.saveTimer = setTimeout(() => { $wire.saveRemarks('{{ $anchorId }}'); }, 500);
                this.buttonTimer = setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('remarks-typing', { detail: { disabled: false } }));
                }, 1000);
            }
        }">
                                <input type="text" wire:model="forms.{{ $anchorId }}.Remarks" @input="typing()"
                                    class="w-full border border-gray-300 p-2 rounded text-gray-800">
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            @empty
            <div class="py-10 text-center text-sm text-gray-400">
                Click <strong>Add Form</strong> to start. Choose the process first, then encode the HFs, then the defects.
            </div>
            @endforelse
        </div>

        {{-- ═════════ AUTO TAB: old flow, no group ═════════ --}}
        @foreach($forms as $formId => $form)
        @continue(isset($form['group_id']))
        <x-ui.dropdown.form-card
            :form-id="$formId"
            :form="$form"
            :auto-methods="$autoMethods"
            :has-error-form="$hasErrorForm"
            :systemName="'operator'" />
        @endforeach

        @if(session()->has('success'))
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50" x-cloak>
            <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">✕</button>
                <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
                <p class="text-gray-700 mb-4">{{ session('success') }}</p>
                <button @click="open = false" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">OK</button>
            </div>
        </div>
        @endif
    </div>
</div>