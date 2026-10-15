<?php

use App\Actions\Operator\PrencodeActions;
use App\Models\Forms\HF;
use App\Models\WorkerName;
use App\Services\Helpers\DraftService;
use App\Services\Operator\EnrollOperatorService;
use App\Traits\HasNotifications;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{

    use HasNotifications;
    protected DraftService $draft;
    protected EnrollOperatorService $service;

    public string $search = '';

    // Modal visibility — entangled with Alpine so they survive every re-render
    public bool $showAdd        = false;
    public bool $showConfirm    = false;
    public bool $showPrencodeVI = false;

    // Currently operated operator
    public ?string $activeOperatorID   = null;
    public ?string $activeOperatorName = null;

    // Form fields
    public string $operatorName = '';
    public string $operatorID   = '';
    public ?int   $deletingId   = null;

    public int $encoder;
    public string $username = '';
    public string|null $action = '';
    public int $ppf = 0;


    protected $messages = [
        'operatorName.required' => 'Operator name is required.',
        'operatorID.required'   => 'Operator ID is required.',
        'operatorID.max'        => 'Operator ID must not exceed 50 characters.',
    ];

    public function boot(EnrollOperatorService $service, DraftService $draft): void
    {
        $this->service = $service;
        $this->draft   = $draft;
    }

    public function mount(): void
    {
        $this->encoder = (int) Auth::user()->社員CD;

        $this->username = WorkerName::select('名前')
            ->where('社員CD', $this->encoder)
            ->value('名前') ?? '';
    }

    public function rules(): array
    {
        return [
            'operatorName' => 'required|string|max:255',

            'operatorID' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, $value, $fail) {
                    if ($this->service->isEnrolledInGl($value, $this->encoder)) {
                        $fail("Operator ID \"{$value}\" is already enrolled in this GL.");
                    }

                    // Cross-GL duplicate check — intentionally disabled for now.
                    // $other = $this->service->findEnrolledInOtherGl($value, $this->encoder);
                    // if ($other) {
                    //     $fail("Operator ID \"{$value}\" is already enrolled in another GL: {$other->GLID}");
                    // }
                },
            ],
        ];
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
        $value = trim($value);

        if ($value === '') {
            return;
        }

        if ($this->service->isEnrolledInGl($value, $this->encoder)) {
            $this->addError('operatorID', "Operator ID \"{$value}\" is already enrolled.");
        }
    }

    // ── Fetch worker name from ID ─────────────────────────────

    public function FetchWorkerName(): void
    {
        $result = $this->service->findWorkerName($this->operatorID);

        $this->operatorName = $result['name'];

        if (! $result['found']) {
            $this->addError('operatorID', "Operator ID \"{$this->operatorID}\" does not exist.");
        }
    }

    // ── Save new operator ─────────────────────────────────────

    public function save(): void
    {
        $this->validate();

        try {
            $this->service->enroll($this->operatorName, $this->operatorID, $this->encoder);

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
        $this->service->remove($this->deletingId);

        $this->deletingId  = null;
        $this->showConfirm = false;
        $this->dispatch('operator-deleted');
    }

    // ── Operate (open prencode modal for this operator) ─────────

    public function operateOperator(string $operatorID): void
    {
        $operator = $this->service->findByOperatorId($operatorID);

        if (! $operator) {
            return;
        }

        $this->activeOperatorID   = $operator->OperatorID;
        $this->activeOperatorName = $operator->OperatorName;
        $this->showPrencodeVI     = true;
        $this->action              = '';   // i-match sa fresh state ng bagong ppf-look-up instance
        $this->ppf                 = 0;

        $this->dispatch('IdentifyOperator', operatorID: $operator->OperatorID);
    }

    public function closePrencode(): void
    {
        $this->showPrencodeVI     = false;
        $this->activeOperatorID   = null;
        $this->activeOperatorName = null;
    }

    public function setOperator(string $operatorID): void
    {
        $operator = $this->service->findByOperatorId($operatorID);

        if ($operator) {
            $this->operatorName = $operator->OperatorName;
            $this->operatorID   = $operator->OperatorID;
        }
    }

    #[On('PrencodeClosed')]
    public function closePrencodePanel(): void
    {
        $this->showPrencodeVI     = false;
        $this->activeOperatorID   = null;
        $this->activeOperatorName = null;
        session()->flash('successNoRefresh', 'Data inserted successfully!');
    }

    public function with(): array
    {
        return [
            'records'    => $this->service->search($this->encoder, $this->search),
            'totalCount' => $this->service->countForGl($this->encoder),
        ];
    }

    // ── Action Section ─────────

    public function setAction(string $action = ''): void
    {
        $this->action   = $action;
        $this->ppf      = 0;

        $this->dispatch('action-changed', $action);
    }

    public function submit(): void
    {
        if ($this->action === '' || $this->activeOperatorID === null) {

            return;
        }

        $draft = $this->draft->load((string) $this->activeOperatorID);
        $forms = $draft['dropdown']['forms'] ?? [];
        $ppf   = (int) ($draft['ppf']['ppf'] ?? $this->ppf);

        if ($forms === [] || $ppf === 0) {
            return;
        }

        $service    = app(PrencodeActions::class);
        $doneAction = $this->action;

        $result = match ($doneAction) {
            'add'    => $service->create($this->encoder, $this->activeOperatorID, $forms, $ppf),
            'edit'   => $service->update($this->encoder, $this->activeOperatorID, $forms, $ppf),
            'delete' => $service->delete($this->activeOperatorID, $ppf),
            default  => null,
        };

        if ($result === null) {
            return;
        }

        $this->draft->clear((string) $this->activeOperatorID);

        $this->action = '';
        $this->ppf    = 0;
        $this->closePrencode();

        session()->flash('successNoRefresh', match ($doneAction) {
            'edit'   => 'Record updated successfully!',
            'delete' => 'Record deleted successfully!',
            default  => 'Data inserted successfully!',
        });
    }

    #[On('checked-ppf')]
    #[Renderless]
    public function checkedPPF(int $ppf): void
    {
        $this->ppf = $ppf;

        if ($this->activeOperatorID !== null) {
            $this->draft->syncComponent((string) $this->activeOperatorID, 'ppf', [
                'ppf' => $ppf,
            ]);
        }

        $existInForms = HF::where('ppfno', $ppf)->exists();

        if ($existInForms) {
            $this->notifyFail('Already Added', 'This PPF already exists. Please select **Update** to modify it.', 6000);
        }
    }
}; ?>

<div
    class="w-full"
    x-data="{
        toast: '',
        showToast(msg){ this.toast = msg; setTimeout(() => this.toast = '', 3000) }
    }"
    @operator-saved.window="showToast('Operator enrolled successfully.')"
    @operator-deleted.window="showToast('Operator removed.')">
    <livewire:notification.pop-up-notif />
    <livewire:notification.session-modal-notification />
    @if (session()->has('successNoRefresh'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
        class="fixed inset-0 flex items-start justify-center pt-5 z-50 mt-5 pointer-events-none"
        x-cloak>

        <div class="flex items-center justify-center gap-3 bg-green-500 text-white px-5 py-4 rounded-xl shadow-xl min-w-[320px]">

            {{-- Icon --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 mt-0.5 flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 13l4 4L19 7" />
            </svg>

            {{-- Content --}}
            <div class="flex-1">
                <h2 class="text-sm font-semibold">Success</h2>
                <p class="text-sm text-green-100">
                    {{ session('successNoRefresh') }}
                </p>
            </div>

            {{-- Close --}}
            <button
                @click="show = false"
                class="text-white/70 hover:text-white">
                ✕
            </button>

        </div>
    </div>
    @endif

    {{-- Toast --}}
    <div
        x-show="toast !== ''"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="mb-4 flex items-center gap-2 px-4 py-3 rounded-lg
               bg-green-50 border border-green-200 text-green-800 text-sm"
        style="display:none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <span x-text="toast"></span>
    </div>

    {{-- Panel Header --}}
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-white">Enroll Operator</h2>
        <p class="text-sm text-gray-500 mt-1">Register and manage operators in the system.</p>
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <div class="relative flex-1 min-w-[180px]">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z" />
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or ID…"
                class="w-full pl-9 pr-4 py-2 text-sm bg-white text-gray-800
                       border border-gray-300 rounded-lg shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
        </div>
        <button
            type="button"
            wire:click="openModal"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                   bg-[#0F3C89] text-white rounded-lg hover:bg-blue-800
                   transition-colors duration-150 shadow-sm whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New Operator
        </button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

        @forelse ($records as $record)
        @php
        $words = explode(' ', trim($record->OperatorName));
        $initials = strtoupper(
        count($words) >= 2
        ? substr($words[0], 0, 1) . substr(end($words), 0, 1)
        : substr($words[0], 0, 2)
        );
        @endphp

        <div
            wire:key="op-{{ $record->OperatorID }}"
            class="bg-white border border-gray-200 rounded-xl p-4
                       flex items-center gap-3
                       hover:border-blue-300 hover:bg-blue-50
                       transition-colors duration-150 group">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100
                            flex items-center justify-center text-sm font-semibold text-blue-800">
                {{ $initials }}
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $record->OperatorName }}</p>
                <p class="text-xs text-gray-500 mt-0.5">ID: {{ $record->OperatorID }}</p>
                <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-md bg-green-100 text-green-800">
                    Inspector
                </span>
            </div>

            <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                {{-- Operate button --}}
                <button
                    type="button"
                    wire:click="operateOperator('{{ $record->OperatorID }}')"
                    title="Operate"
                    class="w-10 h-10 flex items-center justify-center rounded-md
                               border border-gray-200 text-gray-500
                               hover:text-blue-700 hover:border-blue-300 hover:bg-blue-50
                               transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-5.197-3.01A1 1 0 008 9.054v5.892a1 1 0 001.555.832l5.197-3.01a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                {{-- Delete button --}}
                <button
                    type="button"
                    wire:click="setDeleting({{ $record->OperatorID }})"
                    title="Remove"
                    class="w-10 h-10 flex items-center justify-center rounded-md
                               border border-gray-200 text-gray-500
                               hover:text-red-700 hover:border-red-300 hover:bg-red-50
                               transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        @empty
        <div
            wire:key="op-empty"
            class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-sm">No operators enrolled yet.</p>
            <p class="text-xs mt-1">Click <strong>New Operator</strong> to add one.</p>
        </div>
        @endforelse

        {{-- Always in the DOM — hidden when empty, never inserted/removed --}}
        <button
            wire:key="op-add-card"
            type="button"
            wire:click="openModal"
            @class([ 'bg-white border border-dashed border-gray-300 rounded-xl p-4' , 'flex items-center justify-center gap-2 text-sm text-gray-400' , 'hover:text-blue-600 hover:border-blue-400 hover:bg-blue-50' , 'transition-colors duration-150' , 'hidden'=> $totalCount === 0,
            ])
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add operator
        </button>
    </div>

    @if($totalCount > 0)
    <p class="mt-4 text-xs text-gray-400">
        {{ $totalCount }} {{ Str::plural('operator', $totalCount) }} enrolled
    </p>
    @endif

    @include('components.ui.operator.showAddModal')

    @include('components.ui.operator.showConfirmModal')

    {{-- ═══════════════════════════════════════════════════════════
         PRENCODE MODAL — opens when an operator is being operated
    ═══════════════════════════════════════════════════════════ --}}
    @if($showPrencodeVI)
    <div
        class="fixed inset-0 z-50 flex flex-col bg-white overflow-hidden"
        x-data
        @keydown.escape.window="$wire.call('closePrencode')">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 bg-[#0F3C89] text-white shadow-md shrink-0">
            <div class="flex items-center gap-3">
                {{-- Avatar --}}
                @php
                $words = explode(' ', trim($activeOperatorName ?? ''));
                $initials = strtoupper(
                count($words) >= 2
                ? substr($words[0], 0, 1) . substr(end($words), 0, 1)
                : substr($words[0], 0, 2)
                );
                @endphp
                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">
                    {{ $initials }}
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight">{{ $activeOperatorName }}</p>
                    <p class="text-xs text-blue-200 leading-tight">ID: {{ $activeOperatorID }}</p>
                </div>
            </div>

            <button
                type="button"
                wire:click="closePrencode"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                       bg-white/10 hover:bg-white/20 rounded-lg transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Close
            </button>
        </div>

        <div class="flex justify-center gap-3 p-6">
            @foreach([
            'add' => ['ti-plus', 'Add', 'blue'],
            'edit' => ['ti-edit', 'Update', 'green'],
            'view' => ['ti-eye', 'View', 'yellow'],
            'delete' => ['ti-trash', 'Delete', 'red'],
            ] as $key => [$icon, $label, $color])
            <button
                wire:click="setAction('{{ $key }}')"
                wire:loading.attr="disabled"
                @class([ 'flex flex-col items-center gap-1.5 py-3 flex-1 rounded-xl border-2 text-sm font-medium transition-all' , 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'=> $action !== $key,
                'border-blue-600 bg-blue-50 text-blue-700' => $action === $key && $key === 'add',
                'border-green-700 bg-green-50 text-green-700' => $action === $key && $key === 'edit',
                'border-yellow-500 bg-yellow-50 text-yellow-700' => $action === $key && $key === 'view',
                'border-red-700 bg-red-50 text-red-700' => $action === $key && $key === 'delete',
                ])>
                <i class="ti {{ $icon }} text-xl"></i>
                <span>{{ $label }}</span>
            </button>
            @endforeach
        </div>
        {{-- Modal Body — renders the full Prencode component --}}
        <div class="flex-1 overflow-y-auto bg-gray-50" id="prencode-scroll-container">
            @if($action !== 'add' && $action != '')
            <div class="p-3">
                <livewire:partials::operator.operator-table :action="$action" :operatedId="$activeOperatorID" :key="'operator-table'" />
            </div>
            @endif
            <livewire:partials::ppf-look-up
                :key="'operatedId-' . $activeOperatorID"
                :operatorID="$activeOperatorID"
                :systemname="'operator'" />
            <livewire:partials::operator.drop-down :operatorID="$activeOperatorID" />
        </div>
        <div class="flex items-center justify-center mt-4">
            @if($action !== '' && $action !== 'view')
            <div class="flex justify-center p-6">
                <button
                    wire:click="submit"
                    @if($action=='delete' ) @click.prevent="if (confirm('Are you sure you want to delete this ppf?')) $wire.submit()" @endif
                    @class([ 'px-12 py-2.5 rounded-lg text-white text-sm font-medium transition' , 'bg-blue-700 hover:bg-blue-800'=> $action === 'add',
                    'bg-green-700 hover:bg-green-800' => $action === 'edit',
                    'bg-red-700 hover:bg-red-800' => $action === 'delete',
                    ])>
                    {{ match($action) {
            'add'    => 'Submit',
            'edit'   => 'Update',
            'delete' => 'Confirm Delete',
            default  => 'Submit'
        } }}
                </button>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>