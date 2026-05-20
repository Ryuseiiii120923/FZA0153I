<?php

namespace App\Livewire\Glcomponents;

use App\Models\GL\EnrollOperator;
use App\Models\Worker;
use App\Traits\InitializesInspector;
use Livewire\Component;

class EnrollOperatorPanel extends Component
{
    use InitializesInspector;

    public string $search = '';

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
        $isExist = Worker::with('employeeName')
            ->where('作業員CD', (string) $this->operatorID)
            ->exists();
        $worker = Worker::with('employeeName')
            ->where('作業員CD', (string) $this->operatorID)
            ->first();

        $this->operatorName = $worker?->employeeName?->名前 ?? '';

        if (!$isExist) {
            $this->addError('operatorID', "Operator ID \"{$this->operatorID}\" is not Exist.");
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

            $this->reset(['operatorName', 'operatorID']);
            $this->resetValidation();

            // Tell Alpine to close the modal and show success
            $this->dispatch('operator-saved');
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
            throw $e;
        }
    }

    // ── Delete ────────────────────────────────────────────────

    public function setDeleting(int $OperatorID): void
    {
        $this->deletingId = $OperatorID;
        // Tell Alpine to open the confirm modal
        $this->dispatch('open-confirm');
    }

    public function deleteOperator(): void
    {
        EnrollOperator::Where('OperatorID',$this->deletingId)->delete();
        $this->deletingId = null;
        // Tell Alpine to close the confirm modal
        $this->dispatch('operator-deleted');
    }

    public function resetForm(): void
    {
        $this->reset(['operatorName', 'operatorID']);
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $records = EnrollOperator::select('OperatorID', 'OperatorName')
            ->where('GLID', $this->encoder)
            ->when(
                $this->search !== '',
                fn($q) =>
                $q->where('OperatorName', 'like', '%' . $this->search . '%')
                    ->orWhere('OperatorID',  'like', '%' . $this->search . '%')
            )
            ->orderBy('OperatorName')
            ->get();

        return view('livewire.glcomponents.enroll-operator-panel', [
            'records' => $records,
        ]);
    }
}
