<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\AddRwk;
use App\Models\SmallDef;
use App\Models\ViCheck;
use App\Models\WorkerName;
use App\Services\DefectService;
use App\Services\PPFService;
use App\Services\ReworkService;
use Illuminate\Support\Facades\Auth as UserAuth;
use Livewire\Component;
use App\Traits\NormalizeSmallDefects;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use App\Traits\NormalizeDefects;

class Add extends Component
{
    use NormalizeSmallDefects;
    use NormalizeDefects;
    public $ppf;
    public $lotno;
    public $partno;
    public $matno;
    public $moldno;
    public $pressno;
    public $shift;
    public $opt;
    public $expct;
    public $excssqty = '';
    public $lackqty = '';
    public $reworkqty = '';
    public $sampleqty = '';
    public $goodqty = 0;
    public $defects = [];
    public $smalldefects = [];
    public $rework = [];
    public $totalngrework;
    public $Ng;
    public $details;
    public $InspectDates;
    public $UpdateDate;
    public $plant;
    public $auto;
    public $insp1, $insp2, $insp3, $insp4, $insp5;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $submitMethod;
    public $quantity;

    public $isAdd = true;
    public $ngratioqty;

    public $encoder, $username;
    public $Largedefects = [];
    public $dropdownForms = [];
    public $SmallDef;
    public $showParent = true;
    public $showChild = false;
    public $lastdef;
    public $lastqty;
    public $locked = false;
    public $canAdd = false;
    public $haserror = false;
    public $loadingSave = false, $loadingAdd = false, $loadingDelete = false;

    public $listeners = [
        'FromCheckppf' => 'Checkppf',
        'FromDefects' => 'Defects',
        'FromReworks' => 'Reworks',
        'FromGoodNg' => 'GoodNg',
        'FromInsp' => 'Insp',
        'FromReworksData' => 'ReworksData',
        'FromSmallDefects' => 'SmallDefects',
        'FromUpdate' => 'UpdateData',
        'Action' => 'ChooseAction',
        'FetchData' => 'FetchDatas',
        'DeleteToDb' => 'DeleteToDb',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm',
        'inspectorsValidated' => 'onInspectorsValidated',
        'loadProcessRecord' => 'loadProcessRecord',
        'InspectorUpdate' => 'InspectorUpdate'
    ];


    private function ppfService(): PPFService
    {
        return  app(PPFService::class);
    }

    private function reworkService(): ReworkService
    {
        return app(ReworkService::class);
    }

    private function defectService(): DefectService
    {
        return app(DefectService::class);
    }
    public function locked($data)
    {
        $this->locked = $data;
    }

    #[On('IsAdd')]
    public function IsAdd()
    {
        $this->isAdd = false;
    }

    public function ClearForm()
    {
        $this->auto = null;
        $this->plant = null;
        $this->InspectDates = Carbon::now()->format('Y-m-d');
        $this->details = null;
        $this->ppf = null;
    }

    public function ChooseAction($data)
    {
        $this->submitMethod = $data;
    }

    public function Reworks(array $reworksData)
    {
        $items = $reworksData['reworksData'] ?? $reworksData;

        if ($items instanceof \Illuminate\Support\Collection) {
            $items = $items->toArray();
        }

        $this->rework = $this->rework ?? [];

        foreach ($items as $data) {

            $hfno = $data['hfno'] ?? null;
            $type = strtoupper(trim($data['type'] ?? ''));

            if (!$hfno || !$type) {
                continue;
            }

            $quan = (int) ($data['quan'] ?? 0);
            $totalinsp = (int) ($data['totalinsp'] ?? 0);

            // ADD / UPDATE
            $this->rework[$hfno] = $this->rework[$hfno] ?? [];

            $this->rework[$hfno] = collect($this->rework[$hfno])
                ->reject(fn($r) => $r['type'] === $type)
                ->values()
                ->toArray();

            $this->rework[$hfno][] = [
                'type' => $type,
                'quan' => $quan,
                'totalinsp' => $totalinsp,
            ];
        }

        // totals
        $this->totalngrework = collect($this->rework)
            ->map(fn($types) => collect($types)->sum('quan'))
            ->sum();

        $hfnos = array_keys($this->rework);
        $this->hfno1 = $hfnos[0] ?? '';
        $this->hfno2 = $hfnos[1] ?? '';
        $this->hfno3 = $hfnos[2] ?? '';
        $this->hfno4 = $hfnos[3] ?? '';
        $this->hfno5 = $hfnos[4] ?? '';
    }

    public function ReworksData($data)
    {
        $this->totalngrework = $data['totalngrework'];
    }

    public function Checkppf($data)
    {
        $this->ppf = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->partno = $data['partno'];
        $this->matno = $data['matno'];
        $this->moldno = $data['moldno'];
        $this->pressno = $data['pressno'];
        $this->shift = $data['shift'];
        $this->opt = $data['opt'];
        $this->expct = $data['expct'];
    }


    public function GoodNg($data)
    {
        $this->goodqty = $data['goodqty'];
        $this->excssqty = $data['excssqty'];
        $this->lackqty = $data['lackqty'];
        $this->reworkqty = $data['reworkqty'];
        $this->sampleqty = $data['sampleqty'];
    }

    #[On('UpdatedGood')]
    public function UpdatedGood($data)
    {
        $this->goodqty = $data;
    }


    public function render()
    {
        return view('livewire.templates.add');
    }
    public function mount()
    {
        //->employeeName->名前 ?? '';
        $this->InspectDates = Carbon::now()->format('Y-m-d');
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $UserName = WorkerName::select('名前 ')->Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
    }


    //------------Crud Events---------------------
    public function FetchDatas($data)
    {
        $ppf = $this->resolvePpf($data);
        if (!$this->loadMainRecord($ppf)) {
            return;
        }
        $this->loadPlant($ppf);
        $this->loadDefects($ppf);
        $this->loadReworks($ppf);

        $this->calculateQuantities();
        $this->dispatchUpdates();
    }

    /* ---------------- Helper Methods ---------------- */

    private function resolvePpf($data)
    {
        return request()->input('ppf', $data);
    }


    #[On('LoadPlantGL')]
    public function loadPlant($ppf)
    {
        $GetPlant = ViCheck::Where('PPFNO', $ppf)->first();
        if ($GetPlant) {
            $this->plant = $GetPlant->Plant;
        }
    }

    public function loadDefects($ppf)
    {
        $result = $this->defectService()->loadDefectsGL($ppf);
        $this->defects = $result['defects'];
        if (!empty($this->defects)) {
            $this->smalldefects = $result['smallDefects'];
            $this->dispatch(
                'DefectFromUpdate',
                [
                    'defects'       => $this->defects,
                    'smallDefects' => $this->smalldefects,
                ]
            );
        }
    }

    private function loadReworks($ppf)
    {
        $result = $this->reworkService()->getReworks($ppf);
        $this->rework = $result['reworks'];

        if (!empty($this->reworks)) {
            $this->dispatch('ReworkFromUpdate', [
                'reworks' => $this->rework
            ]);
            $this->dispatch('FromReworks', [
                'reworksData' => $result['payload']
            ]);
        }

        $this->totalngrework = $result['total'];
    }
    public function submitAction()
    {
        if ($this->submitMethod === 'addToDb') {
            $this->AddtoDb();
        }
        if ($this->submitMethod === 'deleteToDb') {
            $this->dispatch('confirm-delete');
        }


        if ($this->submitMethod === 'editToDb') {
            $this->EditoDb();
        }
    }

    public function InspectorUpdate($inspectorId)
    {
        $slots = [
            'insp1',
            'insp2',
            'insp3',
            'insp4',
            'insp5',
        ];

        foreach ($slots as $slot) {
            if (empty($this->$slot)) {
                $this->$slot = $inspectorId;
                break;
            }
        }
    }

    #[On('saveDropdown-GL')]
    public function DropdownData($data)
    {
        $this->dropdownForms = $data;
    }

    #[On('LoadMainRecord')]
    public function loadMainRecord($ppf)
    {
        $data = $this->ppfService()->loadMainRecord($ppf);

        if (!$data) {
            session()->flash('failed', 'Record not found');
            return false;
        }

        // Assign to properties
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->dispatch('totalInspectedProgress');

        $this->dispatch('FromView', [
            'ppf' => $this->ppf,
            'lotno' => $this->lotno,
            'partno' => $this->partno,
            'matno' => $this->matno,
            'moldno' => $this->moldno,
            'pressno' => $this->pressno,
            'shift' => $this->shift,
            'opt' => $this->opt,
            'expct' => (int)$this->expct,
            'insp1' => $this->insp1,
            'insp2' => $this->insp2,
            'insp3' => $this->insp3,
            'insp4' => $this->insp4,
            'insp5' => $this->insp5,
            'goodqty' => $this->goodqty,
            'excssqty' => $this->excssqty,
            'lackqty' => $this->lackqty,
            'reworkqty' => $this->reworkqty,
            'sampleqty' => $this->sampleqty,
            'auto' => $this->auto
        ]);

        return true;
    }

    #[On('sendNg')]
    public function FetchNg($ng)
    {
        $this->Ng = $ng;
    }
    #[On('CalculateQuantities')]
    public function calculateQuantities()
    {

        $goodQtyNumeric = is_numeric($this->goodqty) ? $this->goodqty : 0;
        $totalNgNumeric = is_numeric(value: $this->Ng) ? $this->Ng : 0;
        $denominator = $goodQtyNumeric + $totalNgNumeric;


        if ($denominator === 0) {
            $this->ngratioqty = 0;
        } else {
            $this->ngratioqty = number_format(($this->Ng / $denominator) * 100, 2);
        }
        //dd([$this->Ng, $this->goodqty,$denominator, $this->ngratioqty]);
    }

    #[On('dispatchUpdates')]
    public function dispatchUpdates()
    {
        $this->dispatch('FromUpdate', [
            'goodqty' => $this->goodqty,
            'ngratioqty' => $this->ngratioqty
        ]);
    }
    public function DeleteToDb()
    {
        $this->loadingDelete = true;
        if (empty($this->ppf)) {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        static $executed = [];

        $ppf = (int) $this->ppf;

        if (in_array($ppf, $executed)) {
            return; // Prevent double execution
        }
        $executed[] = $ppf;

        $deleted = AddDefect::where('PPFNo', $ppf)->delete();

        if ($deleted) {
            SmallDef::where('PPFNo', $ppf)->delete();
            AddRwk::where('PPFNo', $ppf)->delete();
            ViCheck::where('PPFNO', $this->ppf)
                ->update([
                    'QtyOut' => null,
                    'NGQty' => null,
                    'Excess' => null,
                    'Lacking' => null,
                    'Rework' => null,
                    'Sample' => null,
                    'EncoderOut' => null,
                    'Plant' => null,
                    'InspectionDate' => null,
                    'Dateout' => null,
                ]);
            $this->loadingDelete = false;
            session()->flash('success', 'Deleted successfully!');
        } else {
            session()->flash('failed', 'Failed! PPF not found or already deleted.');
        }
    }

    public function EditoDb()
    {
        if (empty($this->ppf)) {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }

        if ($this->haserror) {
            $this->dispatch('haserror', ['message' => 'Please fix the error!']);
            return;
        }

        $deleteppf = AddDefect::where('PPFNo', $this->ppf)->delete();
        if ($deleteppf) {
            $this->isAdd = false;
            $this->AddtoDb();
        }
    }
    public function AddtoDb()
    {
        if ($this->submitMethod == 'addToDb') {
            $this->loadingAdd = true;
        } elseif ($this->submitMethod == 'editToDb') {
            $this->loadingSave = true;
        }
        $totalInspected = DB::table('Inspector_PR')->where('PPFNo', $this->ppf)
            ->sum('total_inspect');

        $hasRework = DB::table('hf_rework')
            ->where('PPFNo', $this->ppf)
            ->Where('ProceedToRework', 0)
            ->exists();
        $hasReworkHf = DB::table('dr_forms')
            ->where('PPFNo', $this->ppf)
            ->exists();
        $isDone = DB::table('hf_rework')
            ->where('PPFNo', $this->ppf)
            ->where('FlgDone', 1)
            ->exists();
        $isEncoded = DB::table('hf_forms')
            ->where('PPFNo', $this->ppf)
            ->Where('ForRework', 1)
            ->exists();

        if ((int)$totalInspected === (int)$this->expct) {
            $this->isAdd = false;
        }

        if ($this->haserror) {
            $this->dispatch('haserror', ['message' => 'Please fix the error!']);
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if (empty($this->insp1)) {
            $this->dispatch('haserror', ['message' => 'Please enter inspector!']);
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if (empty($this->plant)) {
            $this->dispatch('haserror', ['message' => 'Please enter plant']);
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if (empty($this->ppf)) {
            session()->flash('failed', 'Please Enter PPF!');
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if ($this->ppf === "0") {
            session()->flash('failed', 'Please Enter PPF!');
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if ($this->isAdd) {
            $this->dispatch('haserror', ['message' => 'Please accept first the quantity']);
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if ($hasRework) {
            $this->dispatch('haserror', ['message' => 'There are pending reworks for this PPF. Please resolve them before Saving.']);
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }
        if ($hasRework) {
            if (!$hasReworkHf) {
                $this->dispatch('haserror', ['message' => 'There are pending reworks in HF for this PPF. Please resolve them before Saving.']);
                $this->loadingAdd = false;
                $this->loadingSave = false;
                return;
            }
        }


        if ($hasRework) {
            if (!$isDone) {
                $this->dispatch('haserror', ['message' => 'This PPF rework is not yet marked as done. Please resolve them before Saving.']);
                $this->loadingAdd = false;
                $this->loadingSave = false;
                return;
            }
        }


        if ($isDone && !$isEncoded) {
            $this->dispatch('haserror', ['message' => 'This PPF rework is not yet encode in Operator. Please resolve them before Saving.']);
            $this->loadingAdd = false;
            $this->loadingSave = false;
            return;
        }


        //dd($this->rework);
        //dd($this->smalldefects);

        Db::table('DefectSMALL')->where('PPFNo', $this->ppf)->delete();
        Db::table('DefectRWK')->where('PPFNo', $this->ppf)->delete();
        if (!empty($this->rework)) {
            foreach ($this->rework as $hfno => $types) {
                foreach ($types as $rework) {
                    $type  = $rework['type'] ?? null;
                    $qty   = $rework['quan'] ?? 0;
                    $total = $rework['totalinsp'] ?? 0;

                    Db::table('DefectRWK')->insert([
                        'PPFNo'        => $this->ppf,
                        'HFNo'         => $hfno,   // now HFNO comes from the key
                        'Defect'       => $type ?? '',
                        'Quantity'     => $qty,
                        'TotalInspQty' => $total,
                    ]);
                }
            }
        }

        $ViCheck = Db::table('VICHECK')->select('QtyOut', 'NGQty', 'Excess', 'Lacking', 'Rework', 'Sample', 'EncoderOut', 'Plant', 'InspectionDate', 'DateOut')->where('PPFNO', $this->ppf)->first();
        if ($ViCheck) {
            Db::table('VICHECK')->where('PPFNO', $this->ppf)
                ->update([
                    'QtyOut' => $this->goodqty ?? 0,
                    'NGQty' => $this->ngratioqty ?? 0,
                    'Excess' => $this->excssqty ?? 0,
                    'Lacking' => $this->lackqty ?? 0,
                    'Rework' => $this->reworkqty ?? 0,
                    'Sample' => $this->sampleqty ?? 0,
                    'EncoderOut' => $this->encoder,
                    'Plant' => $this->plant,
                    'InspectionDate' => $this->InspectDates,
                    'Dateout' => Carbon::now()->format('Y-m-d h:i:s A'),
                ]);
        }




        if (empty($this->defects) || count($this->defects) === 0) {

            Db::table('Defect')->insert([

                'PPFNo' => (float) $this->ppf,
                'PartNo' => $this->partno,
                'Lotno' => $this->lotno,
                'MatNo' => $this->matno,
                'MDNo' => $this->moldno,
                'PressNo' => $this->pressno,
                'Shift' => $this->shift,
                'Operator' => $this->opt,
                'Total' => $this->expct,
                'Good' => $this->goodqty,
                'HFNo1' => $this->hfno1,
                'HFNo2' => $this->hfno2,
                'HFNo3' => $this->hfno3,
                'HFNo4' => $this->hfno4,
                'HFNo5' => $this->hfno5,
                'InspNo1' => $this->insp1 ?? '',
                'InspNo2' => $this->insp2 ?? '',
                'InspNo3' => $this->insp3 ?? '',
                'InspNo4' => $this->insp4 ?? '',
                'InspNo5' => $this->insp5 ?? '',
                'Defect' => '',
                'Quantity' => null,
                'Details' => $this->details,
                'InspectionDate' => $this->InspectDates ?? '',
                'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                'Encoder' => (int)$this->encoder,
                'ExcessQty' => $this->excssqty,
                'LackingQty' => $this->lackqty,
                'ReworkQty' => $this->reworkqty,
                'SampleQty' => $this->sampleqty,
                'MDate' => '',
                'AutoMachine' => $this->auto ?? '',
            ]);
        } else {
            foreach ($this->defects as  $defect) {
                //dd($this->defects);
                $type = $defect['type'] ?? $defect['newDefect'] ?? null;
                $qty  = isset($defect['qty']) ? (float)$defect['qty'] : (float)($defect['newQuan'] ?? '');
                if (!$type || $qty <= 0) continue;
                Db::table('Defect')->insert([
                    'PPFNo' => (float) $this->ppf,
                    'PartNo' => $this->partno,
                    'Lotno' => $this->lotno,
                    'MatNo' => $this->matno,
                    'MDNo' => $this->moldno,
                    'PressNo' => $this->pressno,
                    'Shift' => $this->shift,
                    'Operator' => $this->opt,
                    'Total' => $this->expct,
                    'Good' => $this->goodqty,
                    'HFNo1' => $this->hfno1,
                    'HFNo2' => $this->hfno2,
                    'HFNo3' => $this->hfno3,
                    'HFNo4' => $this->hfno4,
                    'HFNo5' => $this->hfno5,
                    'InspNo1' => $this->insp1 ?? '',
                    'InspNo2' => $this->insp2 ?? '',
                    'InspNo3' => $this->insp3 ?? '',
                    'InspNo4' => $this->insp4 ?? '',
                    'InspNo5' => $this->insp5 ?? '',
                    'Defect' => $type,
                    'Quantity' => $qty,
                    'Details' => $this->details ?? '',
                    'InspectionDate' => $this->InspectDates
                        ? Carbon::parse($this->InspectDates)->format('Y-m-d')
                        : null,
                    'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                    'Encoder' => (int)$this->encoder,
                    'ExcessQty' => $this->excssqty,
                    'LackingQty' => $this->lackqty,
                    'ReworkQty' => $this->reworkqty,
                    'SampleQty' => $this->sampleqty,
                    'MDate' => ' ',
                    'AutoMachine' => $this->auto ?? '',

                ]);
            }
        }



        if ($this->submitMethod === 'editToDb') {

            if (empty($this->ppf)) {
                session()->flash('failed', 'Please Enter PPF!');
                return;
            }

            if (!empty($this->smalldefects)) {
                // SmallDef::where('PPFNo', $this->ppf)
                //     ->where('dFlg', 'VI')
                //     ->delete();
                foreach ($this->smalldefects as $largeDefect => $smalls) {
                    foreach ($smalls as $small) {
                        DB::table('DefectSMALL')->insert([
                            'PPFNo'       => $this->ppf,
                            'LargeDefect' => $largeDefect, // <-- the name, not the array
                            'SmallDefect' => $small['newSmallDefect'] ?? $small['type'],
                            'Qty'         => $small['newSmallQuan'] ?? $small['qty']
                        ]);
                    }
                }
            }
        } elseif ($this->submitMethod === 'addToDb') {
            if (!empty($this->smalldefects)) {
                DB::table('DefectSMALL')->select('PPFNo', 'LargeDefect', 'SmallDefect', 'Qty')->where('PPFNo', $this->ppf)
                    ->where('dFlg', 'VI')
                    ->delete();

                foreach ($this->smalldefects as $largeDefect => $smalls) {
                    foreach ($smalls as $small) {
                        DB::table('DefectSMALL')->insert([
                            'PPFNo'       => $this->ppf,
                            'LargeDefect' => $largeDefect, // <-- the name, not the array
                            'SmallDefect' => $small['newSmallDefect'] ?? $small['type'],
                            'Qty'         => $small['newSmallQuan'] ?? $small['qty']
                        ]);
                    }
                }
            }
        }

        if ($this->submitMethod === 'editToDb') {
            session()->flash('success', 'Data updated successfully!');
        } else {
            session()->flash('success', 'Data inserted successfully!');
        }

        DB::statement('EXEC FZA153I_DEFECT_ADD_RTN ?', [
            $this->ppf
        ]);

        if ($this->submitMethod == 'addToDb') {
            $this->loadingAdd = false;
        } elseif ($this->submitMethod == 'editToDb') {
            $this->loadingSave = false;
        }
    }
}
