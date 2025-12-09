<?php

namespace App\Livewire;

use App\Models\AddDefect;
use App\Models\AddRwk;
use App\Models\CheckHF;
use App\Models\Defects;
use App\Models\Rework;
use App\Models\SmallDef;
use App\Models\ViCheck;
use App\Models\WorkerName;
use Illuminate\Support\Facades\Auth as UserAuth;
use DateTime;
use Livewire\Component;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Livewire\Attributes\On;

class Add extends Component
{
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
    public $details;
    public $InspectDate;
    public $UpdateDate;
    public $plant;
    public $auto;
    public $insp1, $insp2, $insp3, $insp4, $insp5;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $submitMethod;
    public $quantity;
    public $ngratioqty;

    public $encoder, $username;
    public $Largedefects = [];
    public $SmallDef;
    public $showParent = true;
    public $showChild = false;
    public $lastdef;
    public $lastqty;
    public $locked = false;

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
        'ClearForm' => 'ClearForm'
    ];

    public function locked($data){
        $this->locked = $data;
    }

    public function ClearForm(){
        $this->auto = null;
        $this->plant = null;
        $this->InspectDate = null;
        $this->details = null;
    }

    public function ChooseAction($data)
    {
        $this->submitMethod = $data;
    }
    public function UpdateData() {}
    public function Reworks($reworksData)
    {
        $type = $reworksData['newtype'] ?? $reworksData['type'] ?? null;
        if (!$type) return;

        $this->rework = collect($this->rework)
            ->reject(fn($r) => ($r['type'] ?? $r['newtype']) === $type)
            ->values()
            ->toArray();

        $this->rework[] = $reworksData;

        $this->totalngrework = collect($this->rework)
            ->sum(fn($x) => (int) ($x['quan'] ?? $x['newquan'] ?? 0));
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
    public function Insp($data)
    {
        $this->insp1 = $data['insp1'] ?? '';
        $this->insp2 = $data['insp2'] ?? '';
        $this->insp3 = $data['insp3'] ?? '';
        $this->insp4 = $data['insp4'] ?? '';
        $this->insp5 = $data['insp5'] ?? '';
    }

    public function Defects($payload = [])
    {
        if (!$payload) return;

        $defectData = $payload['defectData'] ?? $payload;

        $newDefect = trim($defectData['newDefect'] ?? '');
        $newQuan   = (float)($defectData['newQuan'] ?? 0);
        $action    = $defectData['action'] ?? 'add';

        if (!$newDefect) return;

        $normalized = [];
        foreach ($this->defects as $def) {
            $type = $def['type'] ?? $def['newDefect'] ?? '';
            $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? 0);

            if ($type === '') continue;

            if (isset($normalized[strtolower($type)])) {
                $normalized[strtolower($type)]['qty'] += $qty;
            } else {
                $normalized[strtolower($type)] = [
                    'type' => $type,
                    'qty'  => (int) $qty
                ];
            }
        }

        $key = strtolower($newDefect);
        if ($action === 'update') {

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] = $newQuan;
            }
        } else {

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $newQuan;
            } else {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }

        $this->defects = array_values($normalized);
    }

    public function SmallDefects($smalldefectData)
    {
        $large = $smalldefectData['SelectedLargeDefect'];
        $type  = $smalldefectData['type'] ?? $smalldefectData['newSmallDefect'];
        $qty   = $smalldefectData['qty'] ?? $smalldefectData['newSmallQuan'];
        $action = $smalldefectData['action'] ?? 'add';

        if (!isset($this->smalldefects[$large])) {
            $this->smalldefects[$large] = [];
        }

        if ($action === 'update') {
            // Update the existing defect
            foreach ($this->smalldefects[$large] as &$small) {
                if ($small['type'] === $type) {
                    $small['qty'] = $qty;
                    break;
                }
            }
            unset($small); // break reference
        } else {
            // Add new defect
            $this->smalldefects[$large][] = [
                'SelectedLargeDefect' => $large,
                'type' => $type,
                'qty'  => $qty,
            ];
        }
    }
    


    public function GoodNg($data)
    {
        $this->goodqty = $data['goodqty'];
        $this->excssqty = $data['excssqty'];
        $this->lackqty = $data['lackqty'];
        $this->reworkqty = $data['reworkqty'];
        $this->sampleqty = $data['sampleqty'];
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

        if ($this->submitMethod === 'viewToDb') {
            dd('view');
        }
    }
    public function render()
    {
        return view('livewire.add');
    }
    public function mount()
    {
        //->employeeName->名前 ?? '';
        $this->encoder = UserAuth::user()->社員CD;
        $UserName = WorkerName::Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
    }


    //------------Crud Events---------------------
    public function FetchDatas($data)
    {
        $ppf = $data; // fallback
        if (request()->has('ppf')) {
            $ppf = request()->input('ppf');
        }
        $record = AddDefect::where('PPFNo', $ppf)->first();
        $getexpct = CheckHF::where('流動NO', $ppf)->first();
        $defect = AddDefect::where('PPFNo', $ppf)->get();
        $reworkss = AddRwk::where('PPFNo', $ppf)->get();
        $GetPlant = ViCheck::Where('PPFNO', $ppf)->first();

        if ($GetPlant) {
            $this->plant = $GetPlant->Plant;
        }


        if ($defect) {

            // Main defect list
            $this->defects = $defect->map(function ($item) {
                return [
                    'type' => $item->Defect,
                    'qty'  => (int) $item->Quantity
                ];
            })->toArray();

            $last = end($this->defects);

            $this->lastdef = $last['type'] ?? null;
            $this->lastqty = $last['qty'] ?? null;

            // Reset small defects array
            // $this->smalldefects = [];

            // Group small defects by large defect
            foreach ($defect as $item) {

                $large = $item->Defect;

                $smallDef = SmallDef::where('LargeDefect', $large)
                    ->where('PPFNo', $ppf)
                    ->get();

                $this->smalldefects[$large] = $smallDef->map(function ($s) {
                    return [
                        'SelectedLargeDefect' => $s->LargeDefect,
                        'type' => $s->SmallDefect,
                        'qty'  => $s->Qty
                    ];
                })->toArray();
            }
            //dd($this->smalldefects);
        }

        if ($this->defects) {
            $this->dispatch('DefectFromUpdate', [
                'defects'       => $this->defects,
                'smallDefects' => $this->smalldefects,
            ]);
        }
        // dd($this->smalldefects);


        if ($reworkss) {
            $this->rework = $reworkss->map(function ($item) {
                return [
                    'hfno' => $item->HFNo,
                    'totalinsp' => $item->TotalInspQty,
                    'type' => $item->Defect,
                    'quan' => $item->Quantity
                ];
            });
        }
        if ($this->rework) {
            $this->dispatch('ReworkFromUpdate', [
                'reworks' => $this->rework
            ]);
        }
        $this->totalngrework = collect($this->rework)
            ->sum(fn($x) => (int) $x['quan']);
        if ($getexpct) {
            $this->expct = $getexpct->合格数;
        }
        $this->Largedefects = Defects::select('LargeDefect')
            ->distinct()
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();
        if ($record) {
            $this->ppf = $record->PPFNo;
            $this->partno = $record->PartNo;
            $this->lotno = $record->Lotno;
            $this->matno = $record->MatNo;
            $this->moldno = $record->MDNo;
            $this->pressno = $record->PressNo;
            $this->shift = $record->Shift;
            $this->opt = $record->Operator;
            $this->expct = $record->Total;
            $this->goodqty = $record->Good;
            $this->hfno1 = $record->HFNo1;
            $this->hfno2 = $record->HFNo2;
            $this->hfno3 = $record->HFNo3;
            $this->hfno4 = $record->HFNo4;
            $this->hfno5 = $record->HFNo5;
            $this->insp1 = $record->InspNo1;
            $this->insp2 = $record->InspNo2;
            $this->insp3 = $record->InspNo3;
            $this->insp4 = $record->InspNo4;
            $this->insp5 = $record->InspNo5;

            $existingDefects = $record->Defect
                ? [['newDefect' => $record->Defect, 'newQuan' => $record->Quantity]]
                : [];

            foreach ($existingDefects as $existing) {
                $exists = collect($this->defects)->contains(function ($def) use ($existing) {
                    // Check if type already exists
                    $existingType = strtolower(trim($existing['newDefect']));
                    $defType = strtolower(trim($def['type'] ?? $def['newDefect'] ?? ''));
                    return $existingType === $defType;
                });

                if (!$exists) {
                    $this->defects[] = [
                        'type' => $existing['newDefect'],
                        'qty'  => $existing['newQuan']
                    ];
                }
            }
            $this->details = $record->Details;
            $this->InspectDate = Carbon::parse($record->InspectionDate)->format('Y-m-d');
            $this->encoder = $record->Encoder;
            $this->UpdateDate = Carbon::parse($record->DateEndcode)->format('Y-m-d');
            $this->excssqty = $record->ExcessQty;
            $this->lackqty = $record->LackingQty;
            $this->reworkqty = $record->ReworkQty;
            $this->sampleqty = $record->SampleQty;
            $this->auto = $record->AutoMachine;
            $UserName = WorkerName::Where('社員CD', $this->encoder)->first();
            $this->username = $UserName->名前  ?? '';
            $this->dispatch('FromView', [
                'ppf' => $this->ppf,
                'lotno' => $this->lotno,
                'partno' => $this->partno,
                'matno' => $this->matno,
                'moldno' => $this->moldno,
                'pressno' => $this->pressno,
                'shift' => $this->shift,
                'opt' => $this->opt,
                'expct' => $this->expct,
                'insp5' => $this->insp5,
                'insp1' => $this->insp1,
                'insp2' => $this->insp2,
                'insp3' => $this->insp3,
                'insp4' => $this->insp4,
                'goodqty' => $this->goodqty,
                'ngratioqty' => $this->ngratioqty,
                'excssqty' => $this->excssqty,
                'lackqty' => $this->lackqty,
                'reworkqty' => $this->reworkqty,
                'sampleqty' => $this->sampleqty,
                'TotalNg' => $this->totalngrework,
                'auto' => $this->auto
            ]);
        } else {
            session()->flash('failed', 'Record not found');
            return;
        }
        $this->goodqty = (float)$this->expct
            - (float)$this->totalngrework
            + (float)$this->excssqty
            - (float)$this->lackqty
            - (float)$this->reworkqty
            - (float)$this->sampleqty;

        // $this->ngratioqty = number_format(($this->totalngrework / ($this->goodqty + $this->totalngrework)) * 100, 2);

         $denominator = $this->goodqty + $this->totalngrework;

        if ($denominator === 0) {
            $this->ngratioqty = 0;
        } else {
            $this->ngratioqty = number_format(($this->totalngrework / $denominator) * 100, 2);
        }

        $this->dispatch('FromUpdate', [
            'goodqty' => $this->goodqty,
            'ngratioqty' => $this->ngratioqty
        ]);

    }


    public function DeleteToDb()
    {
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
        $deleteppf = AddDefect::where('PPFNo', $this->ppf)->delete();
        if ($deleteppf) {
            $this->AddtoDb();
        }
    }
    public function AddtoDb()
    {
        if (empty($this->ppf)) {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        if (!empty($this->rework)) {

            AddRwk::where('PPFNo', $this->ppf)->delete();
            foreach ($this->rework as $reworks) {
                $type = $reworks['type'] ?? $reworks['newtype'] ?? null;
                $qty  = isset($reworks['quan']) ? (float)$reworks['quan'] : (float)($reworks['newquan'] ?? 0);
                $hfno = $reworks['newhfno'] ?? $reworks['hfno'];
                AddRwk::Create([
                    'PPFNo' => $this->ppf,
                    'Defect' => $type ?? '',
                    'Quantity' => $qty ?? '',
                    'TotalInspQty' => $reworks['totalinsp'] ?? '',
                    'HFNo' => $hfno ?? '',
                    $this->hfno1 => $hfno[0] ?? '',
                    $this->hfno2 => $hfno[1] ?? '',
                    $this->hfno3 => $hfno[2] ?? '',
                    $this->hfno4 => $hfno[3] ?? '',
                    $this->hfno5 => $hfno[4] ?? '',
                ]);
            }
        }

        $ViCheck = ViCheck::where('PPFNO', $this->ppf)->first();
        if ($ViCheck) {
            ViCheck::where('PPFNO', $this->ppf)
                ->update([
                    'QtyOut' => $this->goodqty ?? 0,
                    'NGQty' => $this->ngratioqty ?? 0,
                    'Excess' => $this->excssqty ?? 0,
                    'Lacking' => $this->lackqty ?? 0,
                    'Rework' => $this->reworkqty ?? 0,
                    'Sample' => $this->sampleqty ?? 0,
                    'EncoderOut' => $this->encoder,
                    'Plant' => $this->plant,
                    'InspectionDate' => $this->InspectDate,
                    'Dateout' => Carbon::now()->format('Y-m-d'),
                ]);
        }




        if (empty($this->defects) || count($this->defects) == 0) {
            AddDefect::create([
                'PPFNo' => (float) $this->ppf,
                'PartNo' => $this->partno,
                'LotNo' => $this->lotno,
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
                'Quantity' => 0,
                'Details' => $this->details ?? '',
                'InspectionDate' => $this->InspectDate ?? '',
                'DateEncode' => Carbon::now()->format('Y-m-d'),
                'Encoder' => (int)$this->encoder,
                'ExcessQty' => $this->excssqty,
                'LackingQty' => $this->lackqty,
                'ReworkQty' => $this->reworkqty,
                'SampleQty' => $this->sampleqty,
                'AutoMachine' => $this->auto,
            ]);
        } else {
            foreach ($this->defects as  $defect) {
                //dd($this->defects);
                $type = $defect['type'] ?? $defect['newDefect'] ?? null;
                $qty  = isset($defect['qty']) ? (float)$defect['qty'] : (float)($defect['newQuan'] ?? 0);

                if (!$type || $qty <= 0) continue;
                AddDefect::create([
                    'PPFNo' => (float) $this->ppf,
                    'PartNo' => $this->partno,
                    'LotNo' => $this->lotno,
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
                    'InspectionDate' => $this->InspectDate
                        ? Carbon::parse($this->InspectDate)->format('Y-m-d')
                        : null,
                    'DateEncode' => Carbon::now(),
                    'Encoder' => (int)$this->encoder,
                    'ExcessQty' => $this->excssqty,
                    'LackingQty' => $this->lackqty,
                    'ReworkQty' => $this->reworkqty,
                    'SampleQty' => $this->sampleqty,
                    'AutoMachine' => $this->auto,

                ]);
            }
        }



        if ($this->submitMethod === 'editToDb') {
            
            if (!empty($this->smalldefects)) {
                // SmallDef::where('PPFNo', $this->ppf)
                //     ->where('dFlg', 'VI')
                //     ->delete();

                SmallDef::where('PPFNo', $this->ppf)
                    ->delete();


                foreach ($this->smalldefects as $largeDefect => $smalls) {
                    foreach ($smalls as $small) {
                        SmallDef::create([
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
                SmallDef::where('PPFNo', $this->ppf)
                    ->where('dFlg', 'VI')
                    ->delete();

                foreach ($this->smalldefects as $largeDefect => $smalls) {
                    foreach ($smalls as $small) {
                        SmallDef::create([
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
    }
}
