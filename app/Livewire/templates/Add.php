<?php

namespace App\Livewire\Templates;

use App\Http\Livewire\FetchDataHandler;
use App\Models\AddDefect;
use App\Models\AddRwk;
use App\Models\CheckHF;
use App\Models\Defects;
use App\Models\Operator\PRInsp;
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
use Illuminate\Support\Facades\DB;

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
    public $SmallDef;
    public $showParent = true;
    public $showChild = false;
    public $lastdef;
    public $lastqty;
    public $locked = false;
    public $canAdd = false;
    public $haserror = false;

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

    // public function Reworks(array $reworksData)
    // {
    //     $type = $reworksData['newtype'] ?? $reworksData['type'] ?? null;
    //     if (!$type) return;

    //     // Normalize once
    //     $normalized = [
    //         'hfno'      => $reworksData['newhfno'] ?? $reworksData['hfno'] ?? '',
    //         'type'      => strtoupper(trim($type)),
    //         'quan'      => (int) ($reworksData['newquan'] ?? $reworksData['quan'] ?? 0),
    //         'totalinsp' => (int) ($reworksData['totalinsp'] ?? 0),
    //     ];

    //     if (($reworksData['action'] ?? '') === 'delete') {
    //         // DELETE only matching hfno + type
    //         $this->rework = collect($this->rework)
    //             ->reject(
    //                 fn($r) =>
    //                 $r['hfno'] === $normalized['hfno'] &&
    //                     $r['type'] === $normalized['type']
    //             )
    //             ->values()
    //             ->toArray();
    //     } else {
    //         // ADD or UPDATE based on hfno + type
    //         $this->rework = collect($this->rework)
    //             ->reject(
    //                 fn($r) =>
    //                 $r['hfno'] === $normalized['hfno'] &&
    //                     $r['type'] === $normalized['type']
    //             )
    //             ->push($normalized)
    //             ->values()
    //             ->toArray();
    //     }

    //     // Recalculate
    //     $this->totalngrework = collect($this->rework)->sum('quan');
    // }

    public function Reworks(array $reworksData)
    {
        // If the data is nested under 'reworksData', use it
        $data = $reworksData['reworksData'] ?? $reworksData;


        $type = $data['newtype'] ?? $data['type'] ?? null;
        if (!$type) return;
        // Normalize
        $normalized = [
            'hfno'      => $data['newhfno'] ?? $data['hfno'] ?? '',
            'type'      => strtoupper(trim($type)),
            'quan'      => (int) ($data['newquan'] ?? $data['quan'] ?? 0),
            'totalinsp' => (int) ($data['totalinsp'] ?? 0),
        ];

        // Ensure $this->rework is an array
        $this->rework = $this->rework ?? [];

        if (($data['action'] ?? '') === 'delete') {
            // Remove matching rework
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->values()
                ->toArray();
        } else {
            // Add or update
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->push($normalized)
                ->values()
                ->toArray();
        }

        // Recalculate total quantity
        $this->totalngrework = collect($this->rework)->sum('quan');

        $hfnos = array_column($this->rework, 'hfno');
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
    public function Insp($data)
    {
        $this->insp1 = $data['insp1'] ?? '';
        $this->insp2 = $data['insp2'] ?? '';
        $this->insp3 = $data['insp3'] ?? '';
        $this->insp4 = $data['insp4'] ?? '';
        $this->insp5 = $data['insp5'] ?? '';
    }

    // public function Defects($payload = [])
    // {
    //     if (!$payload) return;

    //     $defectData = $payload['defectData'] ?? $payload;

    //     $newDefect = trim($defectData['newDefect'] ?? '');
    //     $newQuan   = (float)($defectData['newQuan'] ?? '');
    //     $action    = $defectData['action'] ?? 'add';

    //     if (!$newDefect) return;

    //     $normalized = [];
    //     foreach ($this->defects as $def) {
    //         $type = $def['type'] ?? $def['newDefect'] ?? '';
    //         $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? '');

    //         if ($type === '') continue;

    //         if (isset($normalized[strtolower($type)])) {
    //             $normalized[strtolower($type)]['qty'] += $qty;
    //         } else {
    //             $normalized[strtolower($type)] = [
    //                 'type' => $type,
    //                 'qty'  => (int) $qty
    //             ];
    //         }
    //     }


    //     $key = strtolower($newDefect);
    //     if ($action === 'delete') {
    //         unset($normalized[$key]);
    //         $this->defects = array_values($normalized);
    //         return;
    //     }


    //     if ($action === 'update') {

    //         if (isset($normalized[$key])) {
    //             $normalized[$key]['qty'] = $newQuan;
    //         }
    //     } else {
    //         if (isset($normalized[$key])) {
    //             $normalized[$key]['qty'] += $newQuan;
    //         } else {

    //             $normalized[$key] = [
    //                 'type' => $newDefect,
    //                 'qty'  => $newQuan
    //             ];
    //         }
    //     }

    //     $this->defects = array_values($normalized);
    //     dd($this->defects);
    // }

    public function Defects($payload = [])
    {
        if (!$payload) return;

        // Get defectData; it might be a single defect or an array of defects
        $defectData = $payload['defectData'] ?? $payload;

        // Ensure we have an array of defects
        if (isset($defectData['newDefect'])) {
            $defectData = [$defectData]; // single defect → array
        }

        // Step 1: Normalize existing defects (sum same types)
        $normalized = [];
        foreach ($this->defects as $def) {
            $type = trim($def['type'] ?? '');
            $qty  = (float)($def['qty'] ?? 0);
            if ($type === '') continue;

            $key = strtolower($type);
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
            } else {
                $normalized[$key] = [
                    'type' => $type,
                    'qty'  => $qty
                ];
            }
        }

        // Step 2: Apply all new defects on top
        foreach ($defectData as $data) {
            $newDefect = trim($data['newDefect'] ?? '');
            $newQuan   = (float)($data['newQuan'] ?? 0);
            $action    = $data['action'] ?? 'add';

            if (!$newDefect) continue;

            $key = strtolower($newDefect);


            if ($action === 'delete') {
                unset($normalized[$key]);
            } elseif ($action === 'update') {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            } elseif ($action === 'add') {
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] += $newQuan;
                } else {
                    $normalized[$key] = [
                        'type' => $newDefect,
                        'qty'  => $newQuan
                    ];
                }
            } else { // initial load → just set it without adding
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }

        // Step 3: Save back
        $this->defects = array_values($normalized);
    }


    public function SmallDefects($smalldefectData)
    {
        $large  = $smalldefectData['SelectedLargeDefect'];
        $type   = $smalldefectData['type'] ?? $smalldefectData['newSmallDefect'];
        $qty    = $smalldefectData['qty'] ?? $smalldefectData['newSmallQuan'];
        $action = $smalldefectData['action'] ?? 'add';

        if (!isset($this->smalldefects[$large])) {
            $this->smalldefects[$large] = [];
        }

        // Normalize existing small defects by lowercase type
        $normalized = [];
        foreach ($this->smalldefects[$large] as $small) {
            $smallType = strtolower($small['type'] ?? '');
            if ($smallType === '') continue;

            if (isset($normalized[$smallType])) {
                $normalized[$smallType]['qty'] += $small['qty'];
            } else {
                $normalized[$smallType] = [
                    'type' => $small['type'],
                    'qty'  => $small['qty']
                ];
            }
        }

        $key = strtolower($type);

        if ($action === 'delete') {
            // Remove the small defect
            //dd('here');
            unset($normalized[$key]);
        } elseif ($action === 'update') {
            // Update the quantity if it exists
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] = $qty;
            }
        } else {
            // Add new small defect
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
            } else {
                $normalized[$key] = [
                    'type' => $type,
                    'qty'  => $qty
                ];
            }
        }

        // Save back normalized array
        $this->smalldefects[$large] = array_values($normalized);
    }


    public function GoodNg($data)
    {
        $this->goodqty = $data['goodqty'];
        $this->excssqty = $data['excssqty'];
        $this->lackqty = $data['lackqty'];
        $this->reworkqty = $data['reworkqty'];
        $this->sampleqty = $data['sampleqty'];
    }

    public function onInspectorsValidated($payload)
    {
        $this->canAdd = $payload['isValid'];
        $this->haserror = !$this->canAdd;
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
        return view('livewire.templates.add');
    }
    public function mount()
    {
        //->employeeName->名前 ?? '';
        $this->InspectDates = Carbon::now()->format('Y-m-d');
        $userencoder= UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $UserName = WorkerName::select('名前 ')->Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
    }


    //------------Crud Events---------------------
    public function FetchDatas($data)
    {
        $ppf = $this->resolvePpf($data);
        if (!$this->loadMainRecord($ppf)) {
            return; // stop the rest of FetchDatas if record not found
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

    // #[On('ReceiveDefectsFromGL')]
    // public function ReceiveDefectsFromGL(){
    //     $this->defects
    // }

    private function loadDefects($ppf)
    {
        $defect = AddDefect::select('Defect', 'Quantity')->where('PPFNo', $ppf)->get();

        if ($defect) {
            // Main defect list
            $this->defects = $defect->map(function ($item) {
                return [
                    'type' => $item->Defect,
                    'qty'  => (int) $item->Quantity
                ];
            })->filter(fn($d) => $d['qty'] > 0)
                ->values()
                ->toArray();

            $last = end(array: $this->defects);
            $this->lastdef = $last['type'] ?? null;
            $this->lastqty = $last['qty'] ?? null;

            // Group small defects by large defect
            foreach ($defect as $item) {
                $large = $item->Defect;

                $smallDef = SmallDef::select('LargeDefect', 'SmallDefect', 'Qty')->where('LargeDefect', $large)
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

            if ($this->defects) {
                $this->dispatch('DefectFromUpdate', [
                    'defects'       => $this->defects,
                    'smallDefects' => $this->smalldefects,
                ]);
            }
        }
    }

    private function loadReworks($ppf)
    {
        $reworkss = AddRwk::select('HFNo', 'TotalInspQty', 'Defect', 'Quantity')->where('PPFNo', $ppf)->get();

        if ($reworkss) {
            $this->rework = $reworkss->map(function ($item) {
                return [
                    'hfno' => $item->HFNo,
                    'totalinsp' => $item->TotalInspQty,
                    'type' => $item->Defect,
                    'quan' => $item->Quantity
                ];
            });

            if ($this->rework) {
                $this->dispatch('ReworkFromUpdate', [
                    'reworks' => $this->rework
                ]);
            }
        }

        $this->totalngrework = collect($this->rework)
            ->sum(fn($x) => (int) $x['quan']);
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



    #[On('LoadMainRecord')]
    public function loadMainRecord($ppf)
    {
        $record = AddDefect::select('PPFNo', 'PartNo', 'Lotno', 'MatNo', 'MDNo', 'PressNo', 'Shift', 'Operator', 'Total', 'Good', 'HFNo1', 'HFNo2', 'HFNo3', 'HFNo4', 'HFNo5', 'InspNo1', 'InspNo2', 'InspNo3', 'InspNo4', 'ExcessQty', 'LackingQty', 'ReworkQty', 'SampleQty', 'AutoMachine', 'Details', 'Encoder')->where('PPFNo', $ppf)->first();
        $getexpct = CheckHF::where('流動NO', $ppf)->first();

        if ($record) {

            if ($getexpct) {
                $this->expct = $getexpct->合格数;
                $this->dispatch('totalInspectedProgress');
            }

            $this->Largedefects = Defects::select('LargeDefect')
                ->distinct()
                ->whereNotNull('LargeDefect')
                ->orderBy('LargeDefect', 'ASC')
                ->get();

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
                if ($existing['newQuan'] <= 0) continue;

                $exists = collect($this->defects)->contains(function ($def) use ($existing) {
                    return strtolower(trim($existing['newDefect'])) === strtolower(trim($def['type']));
                });

                if (!$exists) {
                    $this->defects[] = [
                        'type' => $existing['newDefect'],
                        'qty'  => $existing['newQuan']
                    ];
                }
            }

            $this->details = $record->Details;
            $this->InspectDates = Carbon::parse($record->InspectionDate)->format('Y-m-d');
            $this->encoder = $record->Encoder;
            $this->UpdateDate = Carbon::parse($record->DateEndcode)->format('Y-m-d h:i:s A');
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
                'expct' => (int)$this->expct,
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
            return true;
        } else {
            session()->flash('failed', 'Record not found');
            return false;
        }
    }

    #[On('sendNg')]
    public function FetchNg($ng)
    {
        $this->Ng = $ng;
    }
    #[On('CalculateQuantities')]
    public function calculateQuantities()
    {
        // $this->goodqty = (float)$this->expct
        //     - (float)$this->totalngrework
        //     + (float)$this->excssqty
        //     - (float)$this->lackqty
        //     - (float)$this->reworkqty
        //     - (float)$this->sampleqty;

        $goodQtyNumeric = is_numeric($this->goodqty) ? $this->goodqty : 0;
        $totalNgNumeric = is_numeric(value: $this->Ng) ? $this->Ng : 0;
        $denominator = $goodQtyNumeric + $totalNgNumeric;


        if ($denominator === 0) {
            $this->ngratioqty = 0;
        } else {
            $this->ngratioqty = number_format(($this->Ng / $denominator) * 100, 2);
        }
        // dd([$this->Ng, $this->goodqty,$denominator, $this->ngratioqty]);
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
        $totalInspected = PRInsp::where('PPFNo', $this->ppf)
            ->sum('total_inspect');

        if ((int)$totalInspected === (int)$this->expct) {
            $this->isAdd = false;
        }

        if ($this->haserror) {
            $this->dispatch('haserror', ['message' => 'Please fix the error!']);
            return;
        }
        if (empty($this->insp1)) {
            $this->dispatch('haserror', ['message' => 'Please enter inspector!']);
            return;
        }
        if (empty($this->plant)) {
            $this->dispatch('haserror', ['message' => 'Please enter plant']);
            return;
        }
        if (empty($this->ppf)) {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        if ($this->ppf === "0") {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        if ($this->isAdd) {
            $this->dispatch('haserror', ['message' => 'Please accept first the quantity']);
            return;
        }


        //dd($this->rework);
        //dd($this->smalldefects);

        SmallDef::where('PPFNo', $this->ppf)->delete();
        AddRwk::where('PPFNo', $this->ppf)->delete();
        if (!empty($this->rework)) {
            foreach ($this->rework as $reworks) {
                $type = $reworks['type'] ?? $reworks['newtype'] ?? null;
                $qty  = isset($reworks['quan']) ? (float)$reworks['quan'] : (float)($reworks['newquan'] ?? '');
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

        $ViCheck = ViCheck::select('QtyOut', 'NGQty', 'Excess', 'Lacking', 'Rework', 'Sample', 'EncoderOut', 'Plant', 'InspectionDate', 'DateOut')->where('PPFNO', $this->ppf)->first();
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
                    'InspectionDate' => $this->InspectDates,
                    'Dateout' => Carbon::now()->format('Y-m-d h:i:s A'),
                ]);
        }




        if (empty($this->defects) || count($this->defects) === 0) {

            AddDefect::create([

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
                AddDefect::create([
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
                SmallDef::select('PPFNo', 'LargeDefect', 'SmallDefect', 'Qty')->where('PPFNo', $this->ppf)
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

        DB::statement('EXEC FZA153I_DEFECT_ADD_RTN ?', [
            $this->ppf
        ]);
    }
}
