<?php

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\GL\EnrollOperator;
use App\Models\Inspector\PRInsp;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected $perPage = 5;
    public string $search = '';

    #[Reactive]
    public string $action = '';

    #[Reactive]
    public string $operatedId = '';

    public function mount()
    {
        $this->ppfdata();
    }

    #[Computed]
    public function ppfdata()
    {
        $encoder = Auth::user()->社員CD;
        $enrolledIds = app(EnrollOperator::class)
            ->query()
            ->where('GLID', $encoder)
            ->pluck('OperatorID');

        $baseQuery = PRInsp::query()
            ->select('ppfno')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->selectRaw('MAX(created_at) as DateEncode')
            ->where('operation', 'VI')
            ->whereIn('inspectorId', $enrolledIds) // replaces whereHas
           ->when($this->search, fn($q) => $q->whereRaw('ppfno_str LIKE ?', ["%{$this->search}%"]))
            ->groupBy('ppfno');

        $candidatePPFNos = (clone $baseQuery)->pluck('ppfno');

        $defectedPPFNos = AddDefect::query()
            ->select('PPFNo')
            ->whereIn('PPFNo', $candidatePPFNos)
            ->pluck('PPFNo');

        $results = $baseQuery
            ->whereNotIn('ppfno', $defectedPPFNos)
            ->paginate($this->perPage);

        $ppfNos = $results->getCollection()->pluck('ppfno')->map(fn($v) => (int) $v);
        $hfMap = CheckHF::whereIn('流動NO', $ppfNos)
            ->get()
            ->keyBy(fn($item) => (int) $item->流動NO);

        $results->getCollection()->transform(function ($item) use ($hfMap) {
            $hf = $hfMap->get((int) $item->ppfno);
            $item->expct = $hf ? round($hf->合格数) : 0;
            return $item;
        });

        return $results;
    }
    public function confirm_ppf(int $ppf)
    {
        $this->dispatch('confirm-ppf', ppf: $ppf);
        $this->dispatch('edit-ppf',ppf : $ppf, inspectorId : $this->operatedId);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
};
?>

<div class="w-full flex flex-col justify-center mt-3 overflow-x-auto">
    <div class="w-full flex justify-end mb-3">
        <input
            type="text"
            wire:model.live.debounce.400ms="search"
            placeholder="Search PPF No..."
            class="border border-gray-400 rounded px-3 py-2 text-black w-full sm:w-64">
    </div>
    <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-900 text-white text-center">
            <tr>
                <th class="px-4 py-2">PPFNo</th>
                <th class="px-6 py-2">Inspection Total</th>
                <th class="px-4 py-2">Encode Date</th>
                <th class="px-4 py-2">Action</th>
            </tr>
        </thead>
        <tbody class="bg-gray-700">
            @forelse($this->ppfdata as $data)
            <tr>
                <td class="px-4 py-2 text-center">{{ (int)$data->ppfno }}</td>
                <td class="px-4 py-2 text-center">{{ $data->total_inspect }} / {{ $data->expct }}</td>
                <td class="px-4 py-2 text-center">{{ $data->DateEncode }}</td>
                <td class="px-4 py-2 flex justify-center gap-2">
                    @php
                    [$btnLabel, $btnColor] = match($action) {
                    'add' => ['Add', 'bg-blue-700 hover:bg-blue-800'],
                    'edit' => ['Update', 'bg-green-700 hover:bg-green-800'],
                    'view' => ['View', 'bg-yellow-500 hover:bg-yellow-600'],
                    'delete' => ['Delete', 'bg-red-700 hover:bg-red-800'],
                    default => ['Confirm', 'bg-gray-600 hover:bg-gray-700'],
                    };
                    @endphp
                    <button
                        class="text-white px-4 py-2 rounded {{ $btnColor }}"
                        wire:loading.attr="disabled"
                        wire:click="confirm_ppf('{{ $data->ppfno }}')">
                        {{ $btnLabel }}
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center">No defects added yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="w-full">
        {{ $this->ppfdata->links() }}
    </div>
</div>