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
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;

class Checkppf extends Component
{
    public $ppf;
    public $lotno;
    public $partno;
    public $matno;
    public $moldno;
    public $pressno;
    public $shift;
    public $opt;
    public $errorexisting;
    public $expct;
    public $action;
    public $isPPF = false;
    public $defects = [];
    public $smalldefects = [];
    public $systemname;
    public $locked = false;
    public $ppfLoaded = false;
    public $lastdef;
    public $lastqty;
    public $showInspectionModal = false; // controls the modal
    public $totalInspection;
    public $encoder, $inspectorID;
    public $progressInsp;
    public $isAccept = false;
    public $canEditTotal = false;
    public $actiondash;
    public $method; //if User press Add button method = Add, Update method = Update, Delete method = Delete, View method = View

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

    #[On('errorExisting')]
    public function hasError($data){
        $this->errorexisting = $data;
    }


    // public function totalInspectedProgress()
    // {
    //     $totalInspected = PRInsp::where('PPFNo', $this->ppf)
    //         ->sum('total_inspect');

    //     $this->totalInspection = $totalInspected;

    //     $this->progressInsp = $this->totalInspection . "/" . $this->expct;

    //     if ((int)$this->totalInspection === (int)$this->expct) {
    //         $this->isAccept = true;
    //         $this->dispatch('UpdateQty', [
    //             'excssqty' => 0,
    //             'lackqty' => 0,
    //         ]);
    //     } else {
    //         $this->isAccept = false;
    //     }
    // }

    public function totalInspectedProgress()
    {
        // Compute the current tota
        $totalInspected = PRInsp::where('PPFNo', $this->ppf)
            ->sum('total_inspect');

        // Only proceed if the sum changed
        if ($totalInspected === $this->totalInspection) {
            return; // nothing changed, exit early
        }

        // Update the properties since the sum changed
        $this->totalInspection = $totalInspected;
        $this->progressInsp = $this->totalInspection . "/" . $this->expct;

        // Update isAccept and dispatch event if fully inspected
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
        $totalInspected = PRInsp::where('PPFNo', $this->ppf)
            ->sum('total_inspect');

        $this->totalInspection = $totalInspected;

        $this->progressInsp = $this->totalInspection . "/" . $this->expct;
    }

    private function loadProcessRecord()
    {
        $ppfexisting = AddDefect::where('PPFNo', $this->ppf)->first();
        $ppfrecord = DefectInsp::where('InspectorID', $this->inspectorID)
            ->where('PPFNo', $this->ppf)
            ->exists()
            ||
            ReworkInsp::where('InspectorID', $this->inspectorID)
            ->where('PPFNo', $this->ppf)
            ->exists()
            || PRInsp::where('InspectorID', $this->inspectorID)
            ->where('PPFNo', $this->ppf)
            ->exists();
        $check = ModelsCheckPPF::where('流動NO', $this->ppf)->first();
        $hf = CheckHF::where('流動NO', $this->ppf)->first();
        $totalinsp = PRInsp::where('PPFNo', $this->ppf)->where('InspectorID', $this->inspectorID)->first();
        $this->dispatch('GoodNg');
        if ($this->actiondash != 'edit' && $this->actiondash != 'view') {

            if ($this->systemname === 'ProcessRecord') {
                if ($ppfrecord) {
                    $this->errorexisting = 'This PPF is already encoded. Kindly review the table below for details.';
                    return false;
                }
            }
        }
        if (!$check) {
            $this->errorexisting = 'PPF No does not encoded on Molding Result!';
            return false;
        }
        if (!$hf) {
            $this->errorexisting = 'PPF No does not encoded on Hand Finishing Result!';
            return false;
        }

        $pcValue = DB::table('Seihin')->where('', $check['品番']);
        $pcValue = $pcValue ?? 0;
        if ($pcValue != "0" && trim($check['金型NO']) != "") {
            $postcure = DB::table('Postcure')->where('PPFNo', $this->ppf)->first();

            if ($postcure) {
                $pc = (int) $postcure->Good;
                if (!$pc) {
                    $this->errorexisting = 'PPFNo is not registered on Postcure!';
                    return false;
                }
            }
        }

        if ($ppfexisting && $this->actiondash != 'view') {
            $this->errorexisting = 'Already Registered';
            $this->dispatch('ppf-error', error: true, message: 'Already Registered');
            return false;
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->errorexisting = null;
        $this->dispatch('ppf-valid', error: false, message: '');

        $this->lotno   = $check ? preg_replace('/\s+/', '', $check->成形ﾛｯﾄ) : '';
        $this->partno  = $check ? preg_replace('/\s+/', '', $check->品番) : '';
        $this->matno   = $check ? preg_replace('/\s+/', '', $check->材料名) : '';
        $this->moldno  = $check ? preg_replace('/\s+/', '', $check->金型NO) : '';
        $this->pressno = $check ? preg_replace('/\s+/', '', $check->PRESSNO) : '';
        $this->shift   = $check ? preg_replace('/\s+/', '', $check->班) : '';
        $this->opt     = $check ? preg_replace('/\s+/', '', $check->作業員CD) : '';
        $this->expct   = $hf ? round($hf->合格数) : 0;
        $this->totalInspection = $totalinsp ? $totalinsp->total_inspect : 0;
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

    public function mount($systemname = null, $ppf = null, $actiondash = null)
    {
        $this->systemname = $systemname;
        $this->ppf = $ppf;
        $this->actiondash = $actiondash;
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $this->inspectorID = Worker::where('社員CD', $this->encoder)
            ->value('作業員CD');
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
        // if ($this->ppfLoaded) {
        //     return;
        // }

        // $this->ppfLoaded = true;
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
        Log::debug('PPF received from JS:', ['ppf' => $this->ppf]);

        $reinspects = DB::table('FinalInspection')
            ->where('PPFNO', $ppf)
            ->orderBy('Reinspect')
            ->get();
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
                $this->dispatch('fetchppf', $this->ppf);
                $this->dispatch('GoodNg');
            }
        } elseif ($this->systemname === 'GLDashboard') {
            $this->dispatch('FetchTotalInspectionTable', $ppf);
            if ($this->action === 'Add') {
                if ($this->loadProcessRecord()) {
                    $this->isPPF = true; //enable the inspection Progress
                    $this->dispatch('LoadDefectsGL', $ppf);
                    $this->dispatch('LoadReworksGL', $ppf);
                    $this->dispatch('FetchDoneRework', $ppf);
                    $this->dispatch('fetchForRework',$ppf);
                }
            } elseif ($this->action === 'Edit') {
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                if ($this->ppf === null) {
                    $this->dispatch('ppf-error');
                    return;
                }

                foreach ($reinspects as $row) {
                    if ((string)$row->ReInspect === "0" || (string)$row->ReInspect === "") {
                        // Same as MsgBox
                        $this->errorexisting = 'Updating Denied! PPFNo was already encoded to Final Inspection Process.';
                        return;
                    }
                }
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;

                $this->dispatch('FetchDataGL', $this->ppf);
            } elseif ($this->action === 'Delete') {
                foreach ($reinspects as $row) {
                    if ((string)$row->ReInspect === "0" || (string)$row->ReInspect === "") {
                        // Same as MsgBox
                        $this->errorexisting = 'Updating Denied! PPFNo was already encoded to Final Inspection Process.';
                        return;
                    }
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
        } else {
            if ($this->action === 'Edit') {
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                if ($this->ppf === null) {
                    $this->dispatch('ppf-error');
                    return;
                }

                foreach ($reinspects as $row) {
                    if ((string)$row->ReInspect === "0" || (string)$row->ReInspect === "") {
                        // Same as MsgBox
                        $this->errorexisting = 'Updating Denied! PPFNo was already encoded to Final Inspection Process.';
                        return;
                    }
                }
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                $this->dispatch('FetchData', $this->ppf);
            } elseif ($this->action === 'Delete') {
                foreach ($reinspects as $row) {
                    if ((string)$row->ReInspect === "0" || (string)$row->ReInspect === "") {
                        // Same as MsgBox
                        $this->errorexisting = 'Updating Denied! PPFNo was already encoded to Final Inspection Process.';
                        return;
                    }
                }
                $this->errorexisting = null;
                $this->dispatch('FetchData', $this->ppf);
                $this->dispatch('locked', true);
                $this->dispatch('lock-fieldsss');

                // set a JS flag
                $this->dispatch('set-js-flag', ['flag' => 'lockAfterDelete', 'value' => true]);

                //$this->dispatch('DeleteToDb', ppf: $this->ppf);
            } elseif ($this->action === 'View') {
                $this->resetErrorBag();
                $this->resetValidation();
                $this->errorexisting = null;
                $this->dispatch('FetchData', $this->ppf);
                $this->dispatch('locked', true);
            } elseif ($this->action === 'Add') {
                $ppfexisting = AddDefect::where('PPFNo', $this->ppf)->first();
                $check = ModelsCheckPPF::where('流動NO', $this->ppf)->first();
                $hf = CheckHF::where('流動NO', $this->ppf)->first();
                $this->dispatch('GoodNg');

                if (!$check) {
                    $this->errorexisting = 'PPF No does not encoded on Molding Result!';
                    return;
                }
                if (!$hf) {
                    $this->errorexisting = 'PPF No does not encoded on Hand Finishing Result!';
                    return;
                }

                $pcValue = DB::table('Seihin')->where('', $check['品番']);
                $pcValue = $pcValue ?? 0;
                if ($pcValue != "0" && trim($check['金型NO']) != "") {
                    $postcure = DB::table('Postcure')->where('PPFNo', $this->ppf)->first();

                    if ($postcure) {
                        $pc = (int) $postcure->Good;
                        if (!$pc) {
                            $this->errorexisting = 'PPFNo is not registered on Postcure!';
                            return;
                        }
                    }
                }

                if ($ppfexisting) {
                    $this->errorexisting = 'Already Registered';
                    $this->dispatch('ppf-error', error: true, message: 'Already Registered');
                    return;
                } else {
                    $this->resetErrorBag();
                    $this->resetValidation();
                    $this->errorexisting = null;
                    $this->dispatch('ppf-valid', error: false, message: '');
                    if ($check && $hf) {
                        $this->lotno   = preg_replace('/\s+/', '', $check->成形ﾛｯﾄ);
                        $this->partno  = preg_replace('/\s+/', '', $check->品番);
                        $this->matno   = preg_replace('/\s+/', '', $check->材料名);
                        $this->moldno  = preg_replace('/\s+/', '', $check->金型NO);
                        $this->pressno = preg_replace('/\s+/', '', $check->PRESSNO);
                        $this->shift   = preg_replace('/\s+/', '', $check->班);
                        $this->opt     = preg_replace('/\s+/', '', $check->作業員CD);
                        $this->expct = round($hf->合格数);
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

                        //$this->emit($this->expct = round($hf->合格数));
                    } else {
                        $this->lotno = '';
                        $this->partno = '';
                        $this->matno = '';
                        $this->moldno = '';
                        $this->pressno = '';
                        $this->shift = '';
                        $this->opt = '';
                        $this->expct = '';
                    }
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.templates.checkppf');
    }
}
