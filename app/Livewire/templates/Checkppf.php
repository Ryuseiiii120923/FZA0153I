<?php

namespace App\Livewire\Templates;

use Illuminate\Support\Facades\Log;
use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\CheckPPF as ModelsCheckPPF;
use App\Models\DefectInsp;
use App\Models\ReworkInsp;
use App\Models\SmallInsp;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

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

    public $defects = [];
    public $smalldefects = [];
    public $systemname;
    public $locked = false;
    public $lastdef;
    public $lastqty;

    public $encoder;

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
        'Views' => 'Views'
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

    private function loadProcessRecord()
    {
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

    public function mount($systemname = null)
    {
        $this->systemname = $systemname;
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
    }


    #[On('dash-ppf')]
    public function PPFCheckDash($data){
        $this->ppf = $data['ppf'];
        $this->actiondash = $data['actiondash'];

        $this->checkPPF();
    }


    #[On('post-ppf')]
    public function checkPPF()
    {
        $this->validate();
        $ppf = $this->ppf; // fallback
        if ($this->ppf === null) {
            $this->dispatch('ppf-error');
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
            $this->loadProcessRecord();
            $this->dispatch('LoadDefectsPren',$ppf);
            $this->dispatch('LoadReworksPren',$ppf);
            $this->dispatch('process');
            $this->dispatch('LoadDash');
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
