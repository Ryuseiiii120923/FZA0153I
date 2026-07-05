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
    public bool   $searched = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->searched = true;
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search   = '';
        $this->searched = false;
        $this->resetPage();
    }

    public function exportPdf(string $ppf): void
    {
        $this->dispatch('open-pdf', url: route('generate-pdf', ['ppf' => $ppf]));
    }

    public function render()
    {
        $records = null;

        if ($this->searched) {
            $search = trim($this->search);

            $records = AddDefect::query()
                ->select([
                    'PPFNo_str AS PPFNo',
                    'PartNo',
                    DB::raw('MAX(DateEncode) as DateEncode'),
                ])
                ->when(
                    $search !== '',
                    fn($q) => $q->where('PPFNo_str', 'like', '%' . $search . '%')
                )
                ->groupBy('PPFNo_str', 'PartNo')
                ->orderBy('PPFNo_str')
                ->paginate($this->perPage);
        }

        return view('livewire.glcomponents.generate-process-record', [
            'records' => $records,
        ]);
    }
}
