<?php

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\FinalInspection;
use App\Models\GL\EnrollOperator;
use App\Models\Inspector\PRInsp;
use App\Models\MoldingResult;
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

    public function mount()
    {
        $this->ppfdata();
    }

    #[Computed]
    public function ppfdata()
    {
        $search = $this->search;

        $results = AddDefect::selectRaw('PPFNo as ppfno')
            ->selectRaw('MAX(Total) as total_inspect')
            ->selectRaw('MAX(DateEncode) as updated_at')
            ->when($search, function ($q) use ($search) {
                $q->whereRaw('PPFNo_str LIKE ?', ["%{$search}%"]);
            })
            ->groupBy('PPFNo')
            ->orderByRaw('MAX(DateEncode) DESC')
            ->paginate(5);

        $ppfNos = $results->getCollection()->pluck('ppfno')->map(fn($ppf) => (int) $ppf);

        $moldingResult = MoldingResult::whereIn('流動NO', $ppfNos)
            ->get()
            ->keyBy('流動NO');

        $finalInspMap = FinalInspection::whereIn('PPFNo', $ppfNos)
            ->where(function ($q) {
                $q->where('ReInspect', '0')
                    ->orWhere('ReInspect', '')
                    ->orWhereNull('ReInspect');
            })
            ->get()
            ->groupBy('PPFNo');

        $results->getCollection()->transform(function ($item) use ($moldingResult, $finalInspMap) {
            $item->molding = $moldingResult->get($item->ppfno);
            $item->finalInspections = $finalInspMap->get($item->ppfno, collect());

            $hf = $moldingResult->get($item->ppfno);
            $item->expct = $hf ? round($hf->未仕上) : 0;

            return $item;
        });

        return $results;
    }

    public function confirm_ppf(int $ppf)
    {
        $this->dispatch('confirm-ppf', ppf: $ppf);
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
                <td class="px-4 py-2 text-center">{{(int) $data->total_inspect }} / {{ $data->expct }}</td>
                <td class="px-4 py-2 text-center">{{ $data->updated_at }}</td>
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
                        wire:click.throttle.10000ms="confirm_ppf('{{ $data->ppfno }}')">
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