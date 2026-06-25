<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\Operator\PRInsp;
use App\Traits\InitializesInspector;
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
    }

    #[Computed(cache: false)]
    public function ppfdata()
    {
        $results = PRInsp::query()
            ->join('OperatorEnroll as oe', 'oe.OperatorID', '=', 'Inspector_PR.InspectorID')
            ->select('Inspector_PR.PPFNo')
            ->selectRaw('SUM(Inspector_PR.total_inspect) as total_inspect')
            ->selectRaw('MAX(Inspector_PR.DateEncode) as DateEncode')
            ->where('oe.GLID', $this->encoder)
            ->whereNotIn('Inspector_PR.PPFNo', function ($query) {
                $query->select('PPFNo')->from('Defect');
            })
            ->when($this->search, fn($q) => $q->whereRaw("CAST(Inspector_PR.PPFNo AS NVARCHAR) LIKE ?", ["%{$this->search}%"]))
            ->groupBy('Inspector_PR.PPFNo')
            ->paginate($this->perPage);


        $ppfNos = $results->getCollection()->pluck('PPFNo')->map(fn($v) => (int)$v);

        $hfMap = CheckHF::whereIn('流動NO', $ppfNos)
            ->get()
            ->keyBy('流動NO');

        $results->getCollection()->transform(function ($item) use ($hfMap) {
            $hf = $hfMap->get((int)$item->PPFNo);
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
        logger('search updated to: ' . $this->search);
    }
    public function render()
    {
        logger('render called, search: ' . $this->search);
        return view('livewire.templates.ppfdashboard', [
            'ppfdata' => $this->ppfdata,
        ]);
    }
}
