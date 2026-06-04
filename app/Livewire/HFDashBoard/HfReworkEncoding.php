<?php

namespace App\Livewire\HFDashBoard;

use App\Models\Worker;
use App\Models\WorkerName;
use App\Services\DoneReworkService;
use App\Services\DropdownService;
use App\Services\ForReworkService;
use App\Services\HfDashboardService;
use App\Services\PPFService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class HfReworkEncoding extends Component
{
    use WithPagination;
    public $pendingRework = [], $defects = [], $rework = [];
    public $ppf, $totalngrework;
    public $open = false;
    public $toggles = false;
    public $selectedPPF = null;
    public $error, $hasError;
    public $insp1, $insp2, $insp3, $insp4, $insp5;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $hfname, $hf_id, $total_inspect;
    public $encoder, $username, $successMessage, $errorMessage;
    public $defectNg, $reworkNg;
    public $confirmingDelete = false;
    public $ppfToDelete = null;
    public $isEdit = false;
    public $status;
    public $deletedppf;
    public $forms = [];
    public $modalOpen = [];
    public $modalMode;
    public $dropdownForms = [];
    public $hasErrorForm = [];
    public $needdeleteSmall = [], $needdeleteDefect = [], $needdeleteForm = [];
    public $inspectRec = [];
    public $isSaved = false;
    public $reworkNo;
    public $perPage = 10;
    public string $search  = '';
    private function ppfService(): PPFService
    {
        return $this->ppfService ?? app(PPFService::class);
    }

    private function forReworkService(): ForReworkService
    {
        return app(ForReworkService::class);
    }
    private function doneReworkService(): DoneReworkService
    {
        return app(DoneReworkService::class);
    }
    private function  HfdashboardService(): HfDashboardService
    {
        return app(HfDashboardService::class);
    }

    public function clearSearch()
    {
        $this->search = '';
    }
    public function updatingSearch(): void
    {
        $this->resetPage();

    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function addNew()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'hf_name' => '',
            'total_inspect' => 0,
            'defects' => [],
            'rework' => [],
            'smallDefects' => [],
            'defectNg' => 0,
            'reworkNg' => 0,
            'status' => 'Pending',
            'ppfno' => (int)$this->selectedPPF,
            'open' => false,
            'Process' => 'HFRW'
        ];
        $this->modalOpen[$formId] = true;
    }

    public function editHF($formId)
    {
        $this->modalMode[$formId] = "edit";
        $this->modalOpen[$formId] = true;
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }

    public function saveHF($formId)
    {
        try {
            app(DropdownService::class)->saveHF(
                $formId,
                $this->forms,
                $this->hf_id,
                $this->total_inspect
            );

            $this->modalOpen[$formId] = false;

            $this->dispatch(
                'FetchHfNo',
                hf_id: $this->forms[$formId]['hf_id'],
                total_inspect: (int) $this->forms[$formId]['total_inspect'],
                form_id: $formId
            );

            $this->CalcGoodQty($formId);
            $this->receiveDropdownData(['forms' => $this->forms]);

            // Reset fields
            $this->hf_id = '';
            $this->total_inspect = '';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    public function CalcGoodQty($formId)
    {
        if (!isset($this->forms[$formId])) return;

        $form = $this->forms[$formId];

        $defectQty = isset($this->defectNg[$formId])
            ? $this->defectNg[$formId]
            : collect($form['defects'] ?? [])->sum('qty');

        $reworkQty = isset($this->reworkNg[$formId])
            ? $this->reworkNg[$formId]
            : collect($form['rework'] ?? [])->sum('quan');

        $totalNg = $defectQty ?? 0 + $reworkQty ?? 0;

        $this->forms[$formId]['GoodQty'] = ($form['total_inspect'] ?? 0) - $totalNg - $reworkQty;
        $this->forms[$formId]['TotalNg'] = $totalNg;
        $this->forms[$formId]['TotalRework'] = $reworkQty;

        return [
            $this->forms[$formId]['GoodQty'],
            $this->forms[$formId]['TotalNg']
        ];
    }

    #[On('operator.defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $this->isSaved = false;

        $this->forms = app(DropdownService::class)->syncFormData(
            $this->forms,
            $data,
            function ($existing, $incoming, $action, $keyResolver) {
                return $this->syncCollection($existing, $incoming, $action, $keyResolver);
            }
        );

        $formId = $data['formId'] ?? null;

        if ($formId) {
            $this->reworkNg[$formId] = collect($this->forms[$formId]['rework'] ?? [])->sum('quan');
            $this->CalcGoodQty($formId);
        }

        $this->receiveDropdownData(['forms' => $this->forms]);
    }

    public function receiveDropdownData($data)
    {
        foreach ($this->dropdownForms as $formId => $form) {
            if (!isset($data['forms'][$formId])) {
                unset($this->dropdownForms[$formId]);
            }
        }

        foreach ($data['forms'] as $formId => $formData) {

            if (!isset($this->dropdownForms[$formId])) {
                $this->dropdownForms[$formId] = $formData;
            } else {

                foreach ($formData as $key => $value) {

                    if (is_array($value)) {
                        $this->dropdownForms[$formId][$key] = $value;
                    } else {
                        $this->dropdownForms[$formId][$key] = $value;
                    }
                }
            }
            $this->dispatch(
                'FetchHfNo',
                hf_id: $formData['hf_id'] ?? null,
                total_inspect: (int) ($formData['total_inspect'] ?? 0),
                form_id: $formId
            );
        }
        $this->dropdownForms = $data['forms'];
    }


    public function remove($id)
    {
        unset($this->forms[$id]);
        $this->needdeleteForm[] = [
            'formId' => $this->inspectRec[$id] ?? null,
        ];
    }
    #[On('NeedToDeleteDefect')]
    public function deleteDefectFromChild($data)
    {
        $formId = $data['formId'];
        $type = $data['type'];
        $this->needdeleteDefect[] = [
            'formId' => $this->inspectRec[$formId] ?? null,
            'type' => $type,
        ];
    }

    #[On('NeedToDeleteSmall')]
    public function deleteSmallFromChild($data)
    {
        $formId = $data['formId'];
        $type = $data['type'];
        $largeDefect = $data['largeDefect'];
        $this->needdeleteSmall[] = [
            'formId' => $this->inspectRec[$formId] ?? null,
            'type' => $type,
            'largeDefect' => $largeDefect,
        ];
    }

    public function mount()
    {
        $pending = $this->HfdashboardService()->fetchHfReworkData();
        $this->pendingRework = $pending;
        $this->status = collect($pending)->pluck('status', 'ppfno')->toArray();
        foreach ($this->pendingRework as $item) {
            $this->total_inspect = $item['total_rework'];
        }
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $UserName = WorkerName::select('名前 ')->Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
        foreach ($this->forms as $formId => $form) {
            $this->modalOpen[$formId] = false;
        }
    }

    public function render()
    {
        $search = trim($this->search);

        $filtered = collect($this->pendingRework)
        ->when(
            $search !== '',
            fn($collection) => $collection->filter(fn ($item) 
                => str_contains((string) $item['ppfno'], $search)
            )
        )
        ->values();

        $currentPage = $this->getPage();
        $perPage = $this->perPage;

        $paginated = new LengthAwarePaginator(
            $filtered->forPage($currentPage, $perPage),
            $filtered->count(),
            $perPage,
            $currentPage,
            ['pageName' => 'page']
        );

        return view('livewire.hfdashboard.hf-rework-encoding', [
            'filteredReworks' => $paginated 
        ]);
    }

    public function confirm_ppf($ppf, $reworkNo)
    {

        $this->reworkNo = $reworkNo;
        $this->selectedPPF = $ppf;
        $this->open = true;
        $this->dispatch('transferHf', [
            'hf_id' => $this->hf_id ?? null,
            'total_inspect' => $this->total_inspect ?? 0
        ]);
    }
    public function removeSelectedPPF()
    {
        $this->selectedPPF = null;
        $this->forms = [];
        $this->modalOpen = [];
        $this->open = false;
    }

    private function syncCollection(array $existing, array $incoming, string $action, callable $keyBuilder)
    {
        $map = collect($existing)
            ->keyBy($keyBuilder)
            ->toArray();

        foreach ($incoming as $item) {

            $key = $keyBuilder($item);

            if (!$key) continue;

            switch ($action) {
                case 'delete':
                    unset($map[$key]);
                    break;

                case 'update':
                case 'add':
                    $map[$key] = $item;
                    break;
            }
        }

        return array_values($map);
    }



    public function edit_ppf($ppf)
    {
        $this->selectedPPF = $ppf;

        $defect = $this->HfdashboardService()->fetchDefectsByPPF($ppf);
        $this->hf_id = $defect['hf_id'] ?? null;
        $this->dispatch('FetchDefect', [
            'defects' => $defect['defect'] ?? [],
            'smallDefects' => $defect['smallDefects'] ?? []
        ]);
        $this->dispatch('transferHf', [
            'hf_id' => $this->hf_id,
            'total_inspect' => $this->total_inspect
        ]);
        $this->open = true;
    }

    public function delete_ppf()
    {
        try {
            $this->HfdashboardService()->deleteDoneRework($this->ppfToDelete, $this->reworkNo);
            $this->resetModal();
            session()->flash('success', 'Deleted Successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to delete PPF.');
        }
    }

    public function confirmDelete($ppf, $reworkNo)
    {
        $this->reworkNo = $reworkNo;
        $this->ppfToDelete = $ppf;
        $this->confirmingDelete = true;
    }


    private function resetModal()
    {
        $this->ppf = null;
        $this->selectedPPF = null;
        $this->dispatch('ClearDefects');
        $this->dispatch('ClearReworks');
        $this->open = false;
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
            'hasError' => $this->hasError,
            'hasErrorForm' => $this->hasErrorForm
        ]);
    }

    #[On('FromDefects')]
    public function Defects($payload = [])
    {
        if (!$payload) return;
        $defectData = $payload['defectData'] ?? $payload;

        if (isset($defectData['newDefect'])) {
            $defectData = [$defectData];
        }

        $normalized = [];
        foreach ($this->defects as $def) {
            $type = trim($def['type'] ?? '');
            $qty  = (float)($def['qty'] ?? 0);
            if ($type === '') continue;

            $key = strtolower($type);
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
            } else {
                $normalized[$key] = [
                    'type' => $type,
                    'qty'  => $qty
                ];
            }
        }

        foreach ($defectData as $data) {
            $newDefect = trim($data['newDefect'] ?? '');
            $newQuan   = (float)($data['newQuan'] ?? 0);
            $action    = $data['action'] ?? 'add';

            if (!$newDefect) continue;

            $key = strtolower($newDefect);


            if ($action === 'delete') {
                unset($normalized[$key]);
            } elseif ($action === 'update') {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            } elseif ($action === 'add') {
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] += $newQuan;
                } else {
                    $normalized[$key] = [
                        'type' => $newDefect,
                        'qty'  => $newQuan
                    ];
                }
            } else {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }
        $this->defects = array_values($normalized);
    }

    #[On('FromSmallDefects')]
    public function SmallDefects($smalldefectData)
    {
        $large  = $smalldefectData['SelectedLargeDefect'];
        $type   = $smalldefectData['type'] ?? $smalldefectData['newSmallDefect'] ?? null;
        $qty    = $smalldefectData['qty'] ?? $smalldefectData['newSmallQuan'] ?? 0;
        $action = $smalldefectData['action'] ?? 'add';

        if (!$type) {
            throw new \Exception("Small defect type is required.");
        }

        $key = strtolower($type);

        if (!isset($this->smalldefects[$large])) {
            $this->smalldefects[$large] = [];
        }

        if ($action === 'add') {

            if (isset($this->smalldefects[$large][$key])) {
                $this->smalldefects[$large][$key]['qty'] += $qty;
            } else {
                $this->smalldefects[$large][$key] = [
                    'type' => $type,
                    'qty'  => $qty,
                ];
            }
        } elseif ($action === 'update') {

            if (isset($this->smalldefects[$large][$key])) {
                $this->smalldefects[$large][$key]['qty'] = $qty;
            } else {
                // fallback: if not existing, create it
                $this->smalldefects[$large][$key] = [
                    'type' => $type,
                    'qty'  => $qty,
                ];
            }
        } elseif ($action === 'delete') {

            unset($this->smalldefects[$large][$key]);

            if (empty($this->smalldefects[$large])) {
                unset($this->smalldefects[$large]);
            }
        }
        foreach ($this->smalldefects as $largeKey => $items) {
            $this->smalldefects[$largeKey] = array_values($items);
        }
    }

    #[On('sendNg')]
    public function fetchDefectNg($data)
    {
        $this->defectNg = $data;
    }

    public function CloseModal($formId)
    {
        $this->modalOpen[$formId] = false;
    }


    public function saveHFRework()
    {
        try {

            $forms = collect($this->forms)
                ->filter(fn($form) => ($form['ppfno'] ?? null) == $this->selectedPPF)
                ->map(function ($form) {

                    $defectNg = collect($form['defects'] ?? [])->sum('qty');

                    $form['GoodQty'] = ($form['total_inspect'] ?? 0) - $defectNg;

                    return $form;
                })
                ->values()
                ->toArray();

            if (empty($forms)) {
                throw new \Exception("No forms to save.");
            }

            $data = [
                'ppfno' => $this->selectedPPF,
                'encoder' => $this->encoder ?? 'system',
                'forms' => $forms,
                'reworkNo' => $this->reworkNo
            ];
            if ($this->isEdit == false) {
                $this->doneReworkService()->saveDoneRework($data);
            } else {
                $this->doneReworkService()->editDonerework($data, $this->needdeleteSmall, $this->needdeleteDefect, $this->needdeleteForm);
                $this->isEdit == false;
            }
            // reset AFTER everything
            $this->removeSelectedPPF();

            session()->flash('success', 'Saved Successfully!');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function editPPFFromChild($ppf, $reworkNo)
    {
        $this->reworkNo = $reworkNo;
        $this->selectedPPF = $ppf;
        $this->defectNg = [];
        $this->isEdit = true;

        $data = app(DropdownService::class)->editFormsforFinishing($ppf, $this->encoder, $reworkNo);
        if (empty($data['forms'])) return;

        $this->toggles = true;
        $this->forms = $data['forms'];
        $this->defectNg = $data['defectNg'];

        foreach ($this->forms as $id => $form) {
            $this->CheckHf($id);
            $this->CalcGoodQty($id);
            $this->modalOpen[$id] = false;
            $this->inspectRec[$id] = $form['inspect_REC'] ?? null;
        }
        if ($this->hasError) {
            $this->dispatch('hasErrorPren', $this->hasError);
        } else {
            $this->receiveDropdownData(['forms' => $this->forms]);
        }
    }
}
