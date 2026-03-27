<?php

namespace App\Livewire\Templates;

use Illuminate\Support\Facades\Log;
use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\CheckPPF as ModelsCheckPPF;
use App\Models\Operator\DefectInsp;
use App\Models\Operator\PRInsp;
use App\Models\Operator\ReworkInsp;
use App\Models\Operator\SmallInsp;
use App\Models\Worker;
use App\Repositories\InspectionRepository;
use App\Repositories\PPFRepository;
use App\Repositories\UserRepository;
use App\Services\PPFService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;

class Checkppf extends Component
{
    public $ppf, $lotno, $partno, $matno, $moldno, $pressno;
    public $shift, $opt, $errorexisting, $expct, $action;
    public $isPPF = false, $defects = [], $smalldefects = [];
    public $systemname, $locked = false, $ppfLoaded = false;
    public $lastdef, $lastqty, $showInspectionModal = false;
    public $totalInspection, $encoder, $inspectorID;
    public $progressInsp, $isAccept = false, $canEditTotal = false;
    public $actiondash, $method;
    protected $ppfService, $ppfRepo, $userRepo;

    public function mount( PPFRepository $ppfRepo, UserRepository $userRepository, $systemname = null, $ppf = null, $actiondash = null)
    {
        $this->ppfRepo = $ppfRepo ?? app(PPFRepository::class);
        $this->userRepo = $userRepository ?? app(UserRepository::class);

        $this->getUser();
        $this->systemname = $systemname;
        $this->ppf = $ppf;
        $this->actiondash = $actiondash;
    }

    private function ppfService(): PPFService
{
    return $this->ppfService ?? app(PPFService::class);
}

    public $rules = ['ppf' => 'required|numeric'];
    public $messages = [
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
    public function locked($data)
    {
        $this->locked = $data;
    }


    public function Views($data)
    {
        $this->ppf = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->matno = $data['matno'];
    }

    public function SystemName($data)
    {
        $this->systemname = $data['systemname'];
    }

    #[On('fromppf')]
    public function fromppf($data)
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


    //This confirm if the total inspect
    public function confirmAccept()
    {
        $this->dispatch('confirm-accept');
    }

    #[On('AcceptTotal')]
    public function AcceptTotal()
    {
        $excss = 0;
        $lack  = 0;
        if ($this->totalInspection > $this->expct) {
            $excss = $this->totalInspection - $this->expct;
        } elseif ($this->totalInspection < $this->expct) {
            $lack = $this->expct - $this->totalInspection;
        }
        $this->dispatch('UpdateQty', [
            'excssqty' => $excss,
            'lackqty'  => $lack,
        ]);

        $this->isPPF = false; //disable the progress
        $this->dispatch('IsAdd');
    }

    public function totalInspectedProgress()
    {
        $result = $this->ppfService()->totalInspectedProgress($this->ppf, $this->expct);

        if ($result['totalInspection'] === $this->totalInspection && $this->totalInspection != 0) {
            return;
        }

        $this->totalInspection = $result['totalInspection'];
        $this->progressInsp = $result['progressInsp'];

        if ((int)$this->totalInspection === (int)$this->expct) {
            $this->isAccept = true;
            $this->dispatch('UpdateQty', [
                'excssqty' => 0,
                'lackqty' => 0,
            ]);
        } else {
            $this->isAccept = false;
        }
    }

    public function totalInspectedProgressFetch()
    {
        $result = $this->ppfService()->totalInspectedProgressFetch($this->ppf, $this->inspectorID, $this->expct);
        $this->totalInspection = $result['totalInspection'];
        $this->progressInsp = $result['progressInsp'];
    }

    private function loadProcessRecord()
    {
        $result = $this->ppfService()->loadProcessRecord(
            $this->ppf,
            $this->inspectorID,
            $this->systemname,
            $this->actiondash
        );

        if (isset($result['error'])) {
            $this->errorexisting = $result['error'];
            return false;
        }
        $this->resetErrorBag();
        $this->resetValidation();
        $this->errorexisting = null;
        $this->dispatch('ppf-valid', error: false, message: '');

        $this->lotno   = $result['lotno'];
        $this->partno  = $result['partno'];
        $this->matno   = $result['matno'];
        $this->moldno  = $result['moldno'];
        $this->pressno = $result['pressno'];
        $this->shift   = $result['shift'];
        $this->opt     = $result['opt'];
        $this->expct   = $result['expct'];
        $this->totalInspection = $result['totalInspection'];
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
        return true;
    }


    public function handleFromView($data)
    {
        $this->fetchData($data);
        $this->fetchGoodNg($data);
        $this->fetchInsp($data);
        $this->fetchHF($data);
    }

    public function ToUpdate($data)
    {
        $this->dispatch('FetchRework', $data);
    }
    public function ToDefect($data)
    {
        $this->dispatch('FetchDefect', $data);
    }
    public function ToRework($data)
    {
        $this->dispatch('FetchRework', $data);
    }
    public function fetchGoodNg($data)
    {
        $this->dispatch('FetchGoodNg', $data);
    }
    public function fetchInsp($data)
    {
        $this->dispatch('fetchInsp', $data);
    }

    public function fetchHF($data)
    {
        $this->dispatch('fetchHF', $data);
    }
    public function fetchData($data)
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


    public function getUser()
    {
        $result = $this->userRepo->getUserData();
        $userencoder = $result['encoder'];
        $this->encoder = (int)$userencoder;
        $this->inspectorID = $result['inspectorID'];
    }
    public function EditActions($data)
    {
        $this->action = null;
        $this->action = $data;
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
    public function PPFCheckDash($data)
    {
        $this->ppf = $data['ppf'];
        $this->actiondash = $data['actiondash'];
        $this->checkPPF();
    }

    #[On('dash-ppfGL')]
    public function PPFCheck($data)
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
        $this->actiondash = 'add';
        $this->checkPPF();
    }

    #[On('post-ppf')]
    public function checkPPF()
    {
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
        $reinspect = $this->ppfService()->checkIfinFinal($this->ppf);
        if (isset($reinspect['errorExist'])) {
            $this->errorexisting = $reinspect['errorExist'];
            return;
        }
        if ($this->ppf === null || empty($this->ppf)) {
            $this->dispatch('ppf-error');
            return;
        }
        if ($this->systemname === 'ProcessRecord') {
            if ($this->loadProcessRecord()) {
                $this->dispatch('expected', $this->expct);
                $this->dispatch('process');
                $this->dispatch('LoadDash');
                $this->dispatch(
                    'edit-ppf',
                    ppf: $ppf,
                    inspectorId: $this->inspectorID
                );
                $this->dispatch('IsCheckPPF', true);
            }
        } elseif ($this->systemname === 'GLDashboard') {
            $this->dispatch('FetchTotalInspectionTable', $ppf);
            if ($this->action === 'Add') {
                if ($this->loadProcessRecord()) {
                    $this->isPPF = true; //enable the inspection Progress
                    $this->dispatch('LoadDefectsGL', $ppf);
                    $this->dispatch('LoadReworksGL', $ppf);
                }
            } elseif ($this->action === 'Edit') {
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                if ($this->ppf === null) {
                    $this->dispatch('ppf-error');
                    return;
                }


                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                $this->dispatch('FetchDataGL', $this->ppf);
            } elseif ($this->action === 'Delete') {
                if (isset($reinspect['errorExist'])) {
                    $this->errorexisting = $reinspect['errorExist'];
                    return;
                }
                $this->errorexisting = null;
                $this->dispatch('FetchDataGL', $this->ppf);
                $this->dispatch('locked', true);
                $this->dispatch('lock-fieldsss');
                $this->dispatch('set-js-flag', ['flag' => 'lockAfterDelete', 'value' => true]);
            } else {
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                $this->dispatch('FetchDataGL', $this->ppf);
                $this->dispatch('locked', true);
            }
        }
    }

    public function render()
    {
        return view('livewire.templates.checkppf');
    }
}
