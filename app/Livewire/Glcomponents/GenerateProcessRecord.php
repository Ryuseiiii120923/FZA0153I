<?php

namespace App\Livewire\GLcomponents;

use App\Models\AddDefect;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class GenerateProcessRecord extends Component
{
    use WithPagination;

    public string $search  = '';
    public int    $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function exportPdf(string $ppf): void
    {
         $this->dispatch('open-pdf', url: route('generate-pdf', ['ppf' => $ppf]));
    }

    public function render()
    {
        $search = trim($this->search);

        $records = AddDefect::query()
            ->select([

                DB::raw('CAST(CAST(PPFNo AS BIGINT) AS VARCHAR(50)) AS PPFNo_str'),
                'PartNo',
                DB::raw('MAX(DateEncode) as DateEncode'),
            ])
            ->when($search !== '', fn($q) =>
                $q->where('PPFNo_str', 'like', '%' . $search . '%')
            )
            ->groupBy(
                DB::raw('CAST(CAST(PPFNo AS BIGINT) AS VARCHAR(50))'),
                'PartNo'
            )
            ->orderBy(DB::raw('CAST(CAST(PPFNo AS BIGINT) AS VARCHAR(50))'))
            ->paginate($this->perPage);

        return view('livewire.glcomponents.generate-process-record', [
            'records' => $records,
        ]);
    }
}