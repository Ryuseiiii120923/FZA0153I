<?php

namespace App\Livewire\Templates;

use App\Models\Rework as ModelsRework;
use App\Models\Worker;
use App\Models\WorkerName;
use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class Rework extends Component
{
    public Collection $reworkcheck;
    public $reworkss = [];
    public $newRework = '';
    //public $hfno = '';
    public $newQuan = '';
    //public $totalInsp = '';
    public array $totalngrework = [];
    public $hfworker = [];
    public $hfname;
    public $editingType;
    public $locked = false;
    public $formId;
    public $editinghfno;

    public array $hfno = [];
    public array $totalInsp = [];
    public $dispatchPrefix;

    // Multi-rework staging: entries pending before a single Confirm
    // [ ['hfno'=>'...','totalinsp'=>'...','type'=>'...','quan'=>0], ... ]
    public array $stagedReworks = [];


    public $listeners = [
        'FetchRework' => 'Fetch',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm'
    ];
    public $rules = [
        'newRework' => 'required|string|max:255',
        'newQuan' => 'required|numeric|min:1',
    ];

    protected $messages = [
        'newRework.required' => 'Please enter a rework type.',
        'newQuan.required' => 'Please enter a quantity.',
    ];


    public function mount($dispatchPrefix = null, $formId = null, $loadedRework = [])
    {
        $this->formId = $formId;
        $this->dispatchPrefix = $dispatchPrefix;
        $this->setReworks($loadedRework);
    }


    #[On('FetchHfNo')]
    public function fetchhfno($hf_id, $total_inspect, $form_id)
    {
        $this->hfno[$form_id] = $hf_id;
        $this->totalInsp[$form_id] = (int) $total_inspect;
    }

    public function setReworks($loadedReworks)
    {
        $this->reworkss = $loadedReworks;

        // Initialize total NG per form
        $this->totalngrework = [];

        foreach ($loadedReworks as $rework) {
            $formId = $rework['formId'] ?? $this->formId ?? 'default';

            if (!isset($this->totalngrework[$formId])) {
                $this->totalngrework[$formId] = 0;
            }

            $this->totalngrework[$formId] += (int) ($rework['quan'] ?? 0);
        }
    }
    public function locked($data)
    {
        $this->locked = $data;
    }
    public function render()
    {
        $rework = ModelsRework::all();
        return view('livewire.templates.rework', [
            'rework' => $rework,
        ]);
    }
    public function ClearForm($formId = null)
    {
        if ($formId) {
            // Clear only the specified form
            $this->reworkss = collect($this->reworkss)
                ->reject(fn($r) => ($r['formId'] ?? null) === $formId)
                ->values()
                ->toArray();

            $this->totalngrework[$formId] = 0;
        } else {
            // Clear all forms
            $this->reworkss = [];
            $this->totalngrework = [];
        }
    }

    public function Fetch($data)
    {
        $formId = $data['formId'] ?? $this->formId ?? 'default';

        // Filter reworks for this form
        $formReworks = collect($data['reworks'] ?? [])
            ->filter(fn($r) => ($r['formId'] ?? $formId) === $formId)
            ->values()
            ->toArray();

        $this->reworkss = $formReworks;

        // Calculate total NG for this form
        $this->totalngrework[$formId] = collect($formReworks)
            ->sum(fn($x) => (int) ($x['quan'] ?? 0));

        $this->totalngrework[$formId] = $this->totalngrework[$formId] ?? 0;
        $this->dispatch($this->dispatchPrefix . '.FetchNgDropdown', [
            'formId' => $formId,
            'totalReworkNg' => $this->totalngrework[$formId]
        ]);
    }

    /**
     * Stage one rework entry (validate + push to stagedReworks).
     * Does NOT close the modal — user can keep adding more.
     */
    public function stageRework()
    {
        $this->validate();

        $normalizedType = strtolower(trim($this->newRework));

        // Verify exists in master list
        $this->reworkcheck = ModelsRework::query()
            ->select('DefectType')
            ->distinct()
            ->whereNotNull('DefectType')
            ->orderBy('DefectType', 'ASC')
            ->get();

        $existsInMaster = $this->reworkcheck
            ->pluck('DefectType')
            ->map(fn($d) => strtolower(trim($d)))
            ->contains($normalizedType);

        if (!$existsInMaster) {
            $this->addError('newRework', 'This rework defect does not exist in the master list');
            return;
        }

        $currentHfNo = $this->hfno[$this->formId] ?? '';

        // Duplicate check: already committed
        $existsCommitted = collect($this->reworkss)->contains(
            fn($r) => ($r['hfno'] ?? '') === $currentHfNo
                && strtolower(trim($r['type'] ?? '')) === $normalizedType
        );

        // Duplicate check: already staged in this session
        $existsStaged = collect($this->stagedReworks)->contains(
            fn($r) => ($r['hfno'] ?? '') === $currentHfNo
                && strtolower(trim($r['type'] ?? '')) === $normalizedType
        );

        if ($existsCommitted || $existsStaged) {
            $this->addError('newRework', 'This rework type is already added');
            return;
        }

        // HF limit: max 5 unique HF numbers across committed + staged
        $committedHfs = collect($this->reworkss)->pluck('hfno')->unique();
        $stagedHfs    = collect($this->stagedReworks)->pluck('hfno')->unique();
        $allHfs       = $committedHfs->merge($stagedHfs)->unique();

        $isNewHf = !$allHfs->contains($currentHfNo);
        if ($isNewHf && $allHfs->count() >= 5) {
            $this->addError('hfno', 'You can only add up to 5 HF Numbers');
            return;
        }

        $this->stagedReworks[] = [
            'hfno'      => $currentHfNo,
            'totalinsp' => $this->totalInsp[$this->formId] ?? '',
            'type'      => trim($this->newRework),
            'quan'      => (int) $this->newQuan,
        ];

        // Reset only the per-entry fields; keep HF No & Total Insp for the next entry
        $this->newRework = '';
        $this->newQuan   = '';
        $this->resetErrorBag();
    }

    /**
     * Remove one entry from the staged list.
     */
    public function removeStagedRework(int $index): void
    {
        array_splice($this->stagedReworks, $index, 1);
    }

    /**
     * Reset the staging area (cancel / close modal).
     */
    public function resetReworkModal(): void
    {

        if (isset($this->stagedReworks)) {
            $this->dispatch('disregardStaged');
        }
    }

    #[On('confirmedDisregard')]
    public function resetStaged()
    {
        $this->stagedReworks = [];
        $this->newRework     = '';
        $this->newQuan       = '';
        $this->resetErrorBag();
        $this->dispatch('close-add-rework');
    }

    /**
     * Commit ALL staged reworks at once.
     */
    public function addRework()
    {
        // If nothing is staged but fields are filled, stage it first
        if (empty($this->stagedReworks) && !empty($this->newRework) && !empty($this->newQuan)) {
            $this->stageRework();
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        if (empty($this->stagedReworks)) {
            $this->addError('newRework', 'Please stage at least one rework entry before confirming.');
            return;
        }

        foreach ($this->stagedReworks as $entry) {
            $entryHfno = $entry['hfno'] ?? '';
            $entryType = strtolower(trim($entry['type'] ?? ''));

            // Find if this entry already exists in the committed list
            $existingIndex = null;
            foreach ($this->reworkss as $i => $r) {
                if (
                    trim($r['hfno'] ?? '') === $entryHfno &&
                    strtolower(trim($r['type'] ?? '')) === $entryType
                ) {
                    $existingIndex = $i;
                    break;
                }
            }

            if ($existingIndex !== null) {
                // Update existing row instead of duplicating
                $this->reworkss[$existingIndex] = $entry;
            } else {
                // Truly new entry — append
                $this->reworkss[] = $entry;
            }
        }

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => $this->reworkss,
            'formId'      => $this->formId,
            'action'      => 'add',
        ]);

        $this->UpdatedNgRework($this->formId);
        $this->resetStaged();
    }

    public function CheckHf()
    {
        try {
            if (empty($this->hfno)) {
                $this->hfname[$this->formId] = null;
                $this->resetErrorBag('hfno');
                return;
            }

            $searchValue = (strlen($this->hfno[$this->formId]) === 2) ? ' ' . $this->hfno[$this->formId] : $this->hfno[$this->formId];
            $hf = Worker::where('作業員CD', $searchValue)->first();

            if ($hf) {
                $name = WorkerName::where('社員CD', $hf->社員CD)->first();
                $this->hfname[$this->formId] = $name ? $name->名前 : null;
                $this->resetErrorBag('hfno');
            } else {
                $this->addError('hfno', 'This Operator does not exist');
                $this->hfno[$this->formId] = "";
                $this->hfname[$this->formId] = null;
            }
        } catch (\Exception $e) {
            $this->addError('hfno', 'An error occurred while validating the HF number');
            Log::error('Error validating HF number: ' . $e->getMessage());
            $this->hfno[$this->formId] = "";
            $this->hfname[$this->formId] = null;
        }
    }
    public function deleteRework($hfno, $type)
    {
        $hfno = trim($hfno);
        $type = trim(strtoupper($type));

        $this->reworkss = collect($this->reworkss)
            ->reject(
                fn($rework) =>
                trim($rework['hfno'] ?? $rework['newhfno'] ?? '') === $hfno &&
                    trim(strtoupper($rework['type'] ?? $rework['newtype'] ?? '')) === $type
            )
            ->values()
            ->toArray();

        $this->dispatch('NeedToDeleteRework', [
            'hfno' => $hfno,
            'type' => $type,
            'formId' => $this->formId
        ]);

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => [[
                'hfno' => $hfno,
                'type' => $type,
            ]],
            'formId' => $this->formId,
            'action' => 'delete'
        ]);
        $this->UpdatedNgRework($this->formId);
    }


    public function UpdatedNgRework($formId = null)
    {
        $formId = $formId ?? $this->formId;
        if (!$formId) return;

        // Sum only the reworks belonging to    this form
        $this->totalngrework[$formId] = collect($this->reworkss)
            ->filter(fn($r) => ($r['hfno'] ?? null) == ($this->hfno[$formId] ?? null))
            ->sum(fn($x) => (int) ($x['quan'] ?? 0));

        $this->dispatch($this->dispatchPrefix . '.FetchNgReworkDropdown', [
            'formId' => $formId,
            'totalReworkNg' => $this->totalngrework[$formId],
        ]);
    }

    public function updateRework()
    {
        foreach ($this->reworkss as &$rework) {
            if ($rework['type'] === $this->editingType && $rework['hfno'] === $this->editinghfno) {
                $rework['quan'] = $this->newQuan;
                $rework['hfno'] = $this->hfno[$this->formId];
                $rework['totalinsp'] = $this->totalInsp[$this->formId];
                break;
            }
        }



        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => [[
                'hfno' => $this->hfno[$this->formId] ?? null,
                'type' => $this->editingType,
                'quan' => $this->newQuan,
                'totalinsp' => $this->totalInsp[$this->formId] ?? null,
            ]],
            'formId' => $this->formId,
            'action' => 'update'
        ]);
        // $this->UpdatedNgRework($this->formId);


        $this->editingType = null;
        $this->newQuan = '';
        $this->totalInsp[$this->formId] = '';
    }


    public function startEditRework($formId, $type, $hfno)
    {
        $this->editingType = $type;
        $this->editinghfno = $hfno;
        $this->formId = $formId;

        $rework = collect($this->reworkss)
            ->first(
                fn($r) => ($r['type'] ?? '') === $type &&
                    ($r['hfno'] ?? '') == $hfno
            );

        if ($rework) {
            $this->hfno[$formId] = $rework['hfno'];
            $this->newQuan = $rework['quan'];
            $this->totalInsp[$formId] = $rework['totalinsp'];
        }
    }

    public function loadExistingStagedRework()
    {
        $this->stagedReworks = [];

        foreach ($this->reworkss as $rework) {
            $this->stagedReworks[] = [
                'hfno'      => $rework['hfno'] ?? '',
                'totalinsp' => $rework['totalinsp'] ?? '',
                'type'      => $rework['type'] ?? '',
                'quan'      => (int) ($rework['quan'] ?? 0),
            ];
        }
    }
}
