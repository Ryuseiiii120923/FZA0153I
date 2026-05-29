<?php

namespace App\Livewire\Glcomponents;

use App\Models\GL\EnrollOperator;
use App\Models\Worker;
use App\Traits\InitializesInspector;
use Livewire\Attributes\On;
use Livewire\Component;

class EnrollOperatorPanel extends Component
{
    use InitializesInspector;

    public string $search = '';

    // Modal visibility — entangled with Alpine so they survive every re-render
    public bool $showAdd      = false;
    public bool $showConfirm  = false;
    public bool $showPrencode = false;

    // Currently operated operator
    public ?string $activeOperatorID   = null;
    public ?string $activeOperatorName = null;

    // Form fields
    public string $operatorName = '';
    public string $operatorID   = '';
    public ?int   $deletingId   = null;

    public function rules(): array
    {
        return [
            'operatorName' => 'required|string|max:255',

            'operatorID' => [
                'required',
                'string',
                'max:50',

                function (string $attribute, $value, $fail) {

                    // 1. Same GL duplicate check
                    $sameGl = EnrollOperator::where('OperatorID', $value)
                        ->where('GLID', $this->encoder)
                        ->exists();

                    if ($sameGl) {
                        $fail("Operator ID \"{$value}\" is already enrolled in this GL.");
                        return;
                    }

                    // 2. Other GL check
                    $other = EnrollOperator::where('OperatorID', $value)
                        ->where('GLID', '!=', $this->encoder)
                        ->first();

                    if ($other) {
                        $fail("Operator ID \"{$value}\" is already enrolled in another GL: {$other->GLID}");
                    }
                },
            ],
        ];
    }

    protected $messages = [
        'operatorName.required' => 'Operator name is required.',
        'operatorID.required'   => 'Operator ID is required.',
        'operatorID.max'        => 'Operator ID must not exceed 50 characters.',
    ];

    public function mount(): void
    {
        $this->initializeInspector();
    }

    // ── Modal open / close ────────────────────────────────────

    public function openModal(): void
    {
        $this->showAdd = true;
    }

    public function closeModal(): void
    {
        $this->showAdd = false;
        $this->reset(['operatorName', 'operatorID']);
        $this->resetValidation();
    }

    public function cancelDelete(): void
    {
        $this->showConfirm = false;
        $this->deletingId  = null;
    }

    // ── Real-time duplicate check as user types ─────────────────

    public function updatingOperatorID(string $value): void
    {
        $this->resetValidation('operatorID');
    }

    public function updatedOperatorID(string $value): void
    {
        if (trim($value) === '') return;

        $exists = EnrollOperator::where('OperatorID', trim($value))
            ->where('GLID', $this->encoder)
            ->exists();

        if ($exists) {
            $this->addError('operatorID', "Operator ID \"{$value}\" is already enrolled.");
        }
    }

    // ── Fetch worker name from ID ─────────────────────────────

    public function FetchWorkerName(): void
    {
        $worker = Worker::with('employeeName')
            ->where('作業員CD', (string) $this->operatorID)
            ->first();

        $this->operatorName = $worker?->employeeName?->名前 ?? '';

        if (!$worker) {
            $this->addError('operatorID', "Operator ID \"{$this->operatorID}\" does not exist.");
        }
    }

    // ── Save new operator ─────────────────────────────────────

    public function save(): void
    {
        $this->validate();

        try {
            EnrollOperator::create([
                'OperatorName' => $this->operatorName,
                'OperatorID'   => $this->operatorID,
                'GLID'         => $this->encoder,
                'ProgramID'    => 'VI',
            ]);

            $this->showAdd = false;
            $this->reset(['operatorName', 'operatorID']);
            $this->resetValidation();

            $this->dispatch('operator-saved');
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
            throw $e;
        }
    }

    // ── Delete ────────────────────────────────────────────────

    public function setDeleting(int $OperatorID): void
    {
        $this->deletingId  = $OperatorID;
        $this->showConfirm = true;
    }

    public function deleteOperator(): void
    {
        EnrollOperator::where('OperatorID', $this->deletingId)->delete();
        $this->deletingId  = null;
        $this->showConfirm = false;
        $this->dispatch('operator-deleted');
    }

    // ── Operate (open prencode modal for this operator) ─────────

    public function operateOperator(string $operatorID): void
    {
        $operator = EnrollOperator::where('OperatorID', $operatorID)->first();
        if (!$operator) return;

        $this->activeOperatorID   = $operator->OperatorID;
        $this->activeOperatorName = $operator->OperatorName;
        $this->showPrencode       = true;

        // Dispatch to Prencode component so it knows who the inspector is
        $this->dispatch('IdentifyOperator', operatorID: $operator->OperatorID);
    }

    public function closePrencode(): void
    {
        $this->showPrencode       = false;
        $this->activeOperatorID   = null;
        $this->activeOperatorName = null;
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $totalCount = EnrollOperator::where('GLID', $this->encoder)->count();

        $records = EnrollOperator::select('OperatorID', 'OperatorName')
            ->where('GLID', $this->encoder)
            ->when(
                $this->search !== '',
                fn($q) =>
                $q->where('OperatorName', 'like', '%' . $this->search . '%')
                    ->orWhere('OperatorID', 'like', '%' . $this->search . '%')
            )
            ->orderBy('OperatorName')
            ->get();

        return view('livewire.glcomponents.enroll-operator-panel', [
            'records'    => $records,
            'totalCount' => $totalCount,
        ]);
    }

    public function setOperator($operatorID): void
    {
        $operator = EnrollOperator::where('OperatorID', $operatorID)->first();

        if ($operator) {
            $this->operatorName = $operator->OperatorName;
            $this->operatorID   = $operator->OperatorID;
        }
    }

    #[On('PrencodeClosed')]
    public function closePrencodePanel(): void
    {
        $this->showPrencode = false;
        $this->activeOperatorID = null;
        $this->activeOperatorName = null;
         session()->flash('successNoRefresh', 'Data inserted successfully!');
    }
}