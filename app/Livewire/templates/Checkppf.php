<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\CheckPPF as ModelsCheckPPF;
use App\Models\Operator\DefectInsp;
use App\Models\Operator\PRInsp;
use App\Models\Operator\ReworkInsp;
use App\Models\Worker;
use App\Services\PrencodeService;
use App\Traits\ClearErrors;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;

class Checkppf extends Component
{
    use ClearErrors;
    public string|null $ppf, $lotno, $partno, $matno, $moldno, $pressno, $shift, $opt, $expct;
    public string|null $errorexisting;
    public string $action;

    public array $defects = [];
    public array $smalldefects = [];
    public string $systemname;

    public string $lastdef;
    public string $lastqty;
    public string|null $actiondash = "";
    public string $method;
    public string $totalInspection;
    public string|null $encoder = "", $inspectorID = "" ;
    public string|null $progressInsp;

    public bool $isAccept = false;
    public bool $canEditTotal = false;
    public bool $locked = false;
    public bool $ppfLoaded = false;
    public bool $showInspectionModal = false; // controls the modal
    public bool $isPPF = false;

    public $rules = ['ppf' => 'required|numeric'];
    public  $messages = [
        'ppf.required' => 'Please enter ppf',
        'ppf.integer' => 'PPF must be integer'
    ];
    protected $listeners = [
        'FromView' => 'handleFromView',
        'DefectFromUpdate' => 'ToDefect',
        'ReworkFromUpdate' => 'ToRework',
        'EditAction' => 'EditActions',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm',
        'SystemName' => 'SystemName',
        'Views' => 'Views',
        'totalInspectedProgress' => 'totalInspectedProgressFetch'
    ];
    public function locked(bool $data)
    {
        $this->locked = $data;
    }

    public function Views(array $data)
    {
        $this->ppf = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->matno = $data['matno'];
    }

    public function SystemName(array|null $data)
    {
        $this->systemname = $data['systemname'];
    }

    #[On('fromppf')]
    public function fromppf(array|null $data)
    {
        $this->ppf = $data;
        $this->checkPPF();
    }

    public function saveInspection()
    {
        $this->validate([
            'totalInspection' => 'required|numeric|min:1',
        ]);
        $this->showInspectionModal = false;
        $this->dispatch('fetchTotalInspection', $this->totalInspection);
    }
    // public function confirmAccept()
    // {
    //     $this->dispatch('confirm-accept');
    // }

    // #[On('AcceptTotal')]
    // public function AcceptTotal()
    // {
    //     $excss = 0;
    //     $lack  = 0;
    //     if ($this->totalInspection > $this->expct) {
    //         $excss = $this->totalInspection - $this->expct;
    //     } elseif ($this->totalInspection < $this->expct) {
    //         $lack = $this->expct - $this->totalInspection;
    //     }
    //     $this->dispatch('UpdateQty', [
    //         'excssqty' => $excss,
    //         'lackqty'  => $lack,
    //     ]);

    //     $this->isPPF = false; //disable the progress
    //     $this->dispatch('IsAdd');
    // }

    #[On('errorExisting')]
    public function hasError(string|null $data)
    {
        $this->errorexisting = $data;
    }

    public function totalInspectedProgress()
    {
        $totalInspected = PRInsp::where('PPFNo', $this->ppf)
            ->sum('total_inspect');
        if ($totalInspected === $this->totalInspection) {
            return;
        }

        $this->totalInspection = $totalInspected;
        $this->progressInsp = $this->totalInspection . "/" . $this->expct;

        if ((int)$this->totalInspection === (int)$this->expct) {
            $this->isAccept = true;
            $this->dispatch('UpdateQty', [
                'excssqty' => 0,
                'lackqty' => 0,
            ]);
            $this->dispatch('GoodNg');
        } else {
            $this->isAccept = false;
        }
    }




    public function totalInspectedProgressFetch()
    {
        $totalInspected = PRInsp::where('PPFNo', $this->ppf)
            ->sum('total_inspect');

        $this->totalInspection = $totalInspected;

        $this->progressInsp = $this->totalInspection . "/" . $this->expct;
    }

    private function loadProcessRecord()
    {
        $this->actiondash = strtolower($this->actiondash);
        $data = app(PrencodeService::class)->loadData($this->ppf, $this->inspectorID, $this->systemname, $this->actiondash);
        if (isset($data['error'])) {
            $this->errorexisting = $data['error'];
            $this->dispatch('lockbuttons');
            return false;
        }

        $this->dispatch('removelock');
        if($this->actiondash == 'view'){
            $this->dispatch('lockview');
        }


        $this->showInspectionModal = $data['showModal'] ?? false;

        $this->dispatch('GoodNg');
        $this->clearErrors();
        $this->dispatch('ppf-valid', error: false, message: '');

        $this->lotno   = $data['lotno'] ?? "";
        $this->partno  = $data['partno'] ?? "";
        $this->matno   = $data['matno'] ?? "";
        $this->moldno  = $data['moldno'] ?? "";
        $this->pressno = $data['pressno'] ?? "";
        $this->shift   = $data['shift'] ?? "";
        $this->opt     = $data['opt'] ?? "";
        $this->expct   = $data['expct'] ?? 0;
        $this->totalInspection = $data['totalInspection'] ?? 0;
        $this->dispatch('fetchTotalInspection', $this->totalInspection);
        $this->dispatch('sendExcpt', $this->expct);
        $this->dispatch('FromCheckppf', [
            'ppf' => $this->ppf,
            'lotno' => $this->lotno,
            'partno' => $this->partno,
            'matno' => $this->matno,
            'moldno' => $this->moldno,
            'pressno' => $this->pressno,
            'shift' => $this->shift,
            'opt' => $this->opt,
            'expct' => $this->expct
        ]);
        if ($this->actiondash != 'edit') {
            if ($this->systemname === 'ProcessRecord') {
                $this->showInspectionModal = true;
            }
        }
        $this->totalInspectedProgress();
        return true;
    }



    public function handleFromView(array|null $data)
    {
        $this->fetchData($data);
        $this->fetchGoodNg($data);
        $this->fetchInsp($data);
        $this->fetchHF($data);
    }

    public function ToUpdate(array|null $data)
    {
        $this->dispatch('FetchRework', $data);
    }
    public function ToDefect(array|null $data)
    {
        $this->dispatch('FetchDefect', $data);
    }
    public function ToRework(array|null $data)
    {
        $this->dispatch('FetchRework', $data);
    }
    public function fetchGoodNg(array|null $data)
    {
        $this->dispatch('FetchGoodNg', $data);
    }
    public function fetchInsp(array|null $data)
    {
        $this->dispatch('fetchInsp', $data);
    }

    public function fetchHF(array|null $data)
    {
        $this->dispatch('fetchHF', $data);
    }
    public function fetchData(array|null $data)
    {
        $this->ppf = $data['ppf'] ?? null;
        $this->lotno = $data['lotno'] ?? null;
        $this->partno = $data['partno'] ?? null;
        $this->matno = $data['matno'] ?? null;
        $this->moldno = $data['moldno'] ?? null;
        $this->pressno = $data['pressno'] ?? null;
        $this->shift = $data['shift'] ?? null;
        $this->opt = $data['opt'] ?? null;
        $this->expct = $data['expct'] ?? 0;
    }

    public function mount(string|null $systemname = null, string|null $ppf = null)
    {
        $this->systemname = $systemname;
        $this->ppf = $ppf;
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $this->inspectorID = Worker::where('社員CD', $this->encoder)
            ->value('作業員CD');
    }
    public function EditActions(string|null $data)
    {
        $this->actiondash = null;
        $this->actiondash = $data;
    }

    public function ClearForm()
    {

        $this->ppf =  null;
        $this->lotno =  null;
        $this->partno =  null;
        $this->matno =  null;
        $this->moldno =  null;
        $this->pressno =  null;
        $this->shift = null;
        $this->opt = null;
        $this->expct = 0;
        $this->progressInsp = null;
    }


    #[On('dash-ppf')]
    public function PPFCheckDash(array|null $data)
    {

        $this->ppf = $data['ppf'];
        $this->actiondash = $data['actiondash'];
        $this->inspectorID = $data['encoder'] ?? $this->inspectorID;
        $this->checkPPF();
    }

    #[On('dash-ppfGL')]
    public function PPFCheck(array|null $data)
    {
        $this->ppf = $data['ppf'];
        $this->actiondash = $data['actiondash'];
        $this->checkPPF();
    }

    #[On('ProgDis')]
    public function ProgDis()
    {
        $this->isPPF = false;
    }

    public function EnterPPF()
    {
        $this->dispatch('ClearFormDropdown');
        $this->dispatch('dash-ppf1', ['actiondash' => 'add']); // action in Prencode
        if ($this->systemname === 'ProcessRecord') {
            $this->actiondash = 'Add';
        }
        $this->checkPPF();
    }


    private function isLockedByFinalInspection(int $ppf): bool
    {
        return DB::table('FinalInspection')
            ->where('PPFNO', $ppf)
            ->where(function ($q) {
                $q->where('ReInspect', '0')
                    ->orWhere('ReInspect', '');
            })
            ->exists();
    }

    private function handleProcessRecord()
    {
        if (!$this->loadProcessRecord()) {
            $this->dispatch('IsLoading', false);
            return;
        }
        $this->dispatch('expected', $this->expct);
        $this->dispatch('process');
        $this->dispatch('LoadDash');
        $this->dispatch(
            'edit-ppf',
            ppf: $this->ppf,
            inspectorId: $this->inspectorID
        );
        // $this->dispatch('IsCheckPPF', true); //para malaman kung nacheck ang ppf
        $this->dispatch('fetchppf', $this->ppf);
        $this->dispatch('GoodNg');
        $this->dispatch('IsLoading', false);
    }

    private function handleGLDashboard()
    {
        if (!$this->loadProcessRecord()) {
            return;
        }
        $this->dispatch('FetchTotalInspectionTable', $this->ppf);
        match ($this->actiondash) {
            'add' => $this->handleGlAdd($this->ppf),
            'edit' => $this->handleGlEdit($this->ppf),
            'delete' => $this->handleGlDelete($this->ppf),
            default => $this->handleGlDefault($this->ppf),
        };
    }

    private function handleGlAdd(string $ppf)
    {
        $this->isPPF = true; //enable the inspection Progress
        $this->dispatch('LoadDefectsGL', $ppf);
        $this->dispatch('LoadReworksGL', $ppf);
        $this->dispatch('FetchDoneRework', $ppf);
        $this->dispatch('fetchForRework', $ppf);
    }

    private function handleGlEdit(string $ppf)
    {
        $this->clearErrors();
        if ($this->ppf === null) {
            $this->dispatch('ppf-error');
            return;
        }

        if ($this->isLockedByFinalInspection($ppf)) {
            $this->errorexisting = 'Updating Denied! PPFNo was already encoded to Final Inspection Process.';
            return;
        }

        $this->clearErrors();

        $this->dispatch('FetchDataGL', $ppf);
    }

    public function handleGlDelete(string $ppf)
    {
        if ($this->isLockedByFinalInspection($ppf)) {
            $this->errorexisting = 'Updating Denied! PPFNo was already encoded to Final Inspection Process.';
            return;
        }
        $this->errorexisting = null;
        $this->dispatch('FetchDataGL', $ppf);
        $this->dispatch('locked', true);
        $this->dispatch('lock-fieldsss');
        $this->dispatch('set-js-flag', ['flag' => 'lockAfterDelete', 'value' => true]);
    }

    private function handleGlDefault(string $ppf)
    {
        $this->clearErrors();
        $this->dispatch('FetchDataGL', $ppf);
        $this->dispatch('locked', true);
    }
    #[On('post-ppf')]
    public function checkPPF()
    {
        $this->dispatch('lockbuttons');
        $this->dispatch('removelockview');
        $this->validate();
        $ppf = $this->ppf; // fallback
        if ($this->ppf === null) {
            $this->dispatch(event: 'ppf-error');
            return;
        }
        if (request()->has('ppf')) {
            $ppf = request()->input('ppf');
        }
        $this->ppf = (int) $ppf;

        if ($this->ppf === null || empty($this->ppf)) {
            $this->dispatch('ppf-error');
            return;
        }
        match ($this->systemname) {
            'ProcessRecord' => $this->handleProcessRecord(),
            'GLDashboard' => $this->handleGLDashboard(),
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.templates.checkppf');
    }
}
