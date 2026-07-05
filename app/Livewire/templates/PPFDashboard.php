<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\GL\EnrollOperator;
use App\Models\Inspector\PRInsp;
use App\Traits\InitializesInspector;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PPFDashboard extends Component
{
    use WithPagination;
    use InitializesInspector;

    protected $perPage = 5;
    public string $search = '';

    public function mount()
    {
        $this->initializeInspector();
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
        ->when($this->search, fn($q) => $q->whereRaw(
            "CAST(CAST(ppfno AS BIGINT) AS NVARCHAR(50)) LIKE ?",
            ["%{$this->search}%"]
        ))
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
    public function confirm_ppf($ppf)
    {
        $this->dispatch('actionTable', ['actiondash' => 'Add', 'ppf' => $ppf]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function render()
    {
        return view('livewire.templates.ppfdashboard', [
            'ppfdata' => $this->ppfdata,
        ]);
    }
}
