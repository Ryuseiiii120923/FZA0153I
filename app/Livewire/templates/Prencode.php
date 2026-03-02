<?php

namespace App\Livewire\Templates;

use App\Models\Operator\DefectInsp;
use App\Models\Operator\PRInsp;
use App\Models\Operator\ReworkInsp;
use App\Models\Operator\SmallInsp;
use App\Models\Worker;
use App\Models\WorkerName;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;
use Livewire\Attributes\On;

class Prencode extends Component
{
    public $ppf;
    public $lotno;
    public $partno;
    public $matno;

    public $encoder, $username, $inspectorID;
    public $lastdef;
    public $lastqty;
    public $totalInspection;

    public $actiondash;

    public $defects = [];
    public $smalldefects = [];
    public $rework = [];
    public $dropdownForms = [];
    public $totalngrework;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $process;


    public $listeners = [
        'FromCheckppf' => 'Checkppf',
        'FromDefects' => 'Defects',
        'FromSmallDefects' => 'SmallDefects',
        'FromReworks' => 'Reworks',
        'ClearForm' => 'ClearForm',
        'LoadDefectsPren' => 'LoadDefectsPren',
        'LoadReworksPren' => 'LoadReworksPren'
    ];

    #[On('dash-ppf')]
    public function action($data)
    {
        $this->actiondash = $data['actiondash'];
    }

    public function Reworks(array $reworksData)
    {

        $type = $reworksData['newtype'] ?? $reworksData['type'] ?? null;
        if (!$type) return;

        // Normalize once
        $normalized = [
            'hfno'      => $reworksData['newhfno'] ?? $reworksData['hfno'] ?? '',
            'type'      => strtoupper(trim($type)),
            'quan'      => (int) ($reworksData['newquan'] ?? $reworksData['quan'] ?? 0),
            'totalinsp' => (int) ($reworksData['totalinsp'] ?? 0),
        ];

        if (($reworksData['action'] ?? '') === 'delete') {
            // DELETE only matching hfno + type
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->values()
                ->toArray();
        } else {
            // ADD or UPDATE based on hfno + type
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

        // Recalculate
        $this->totalngrework = collect($this->rework)->sum('quan');
    }


    //From Adding Reworks
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
    }


    //From adding defects
    public function Defects($payload = [])
    {
        if (!$payload) return;

        $defectData = $payload['defectData'] ?? $payload;

        $newDefect = trim($defectData['newDefect'] ?? '');
        $newQuan   = (float)($defectData['newQuan'] ?? '');
        $action    = $defectData['action'] ?? 'add';

        if (!$newDefect) return;

        $normalized = [];
        foreach ($this->defects as $def) {
            $type = $def['type'] ?? $def['newDefect'] ?? '';
            $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? '');

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

        if ($action === 'delete') {
            unset($normalized[$key]);
            $this->defects = array_values($normalized);
            return;
        }


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


    //To Fetch Defect
    #[On('LoadDefectsPren')]
    public function LoadDefectsPren($ppf)
    {
        $defect = DefectInsp::select('Defect', 'Quantity')->where('PPFNo', $ppf)->where('InspectorID', $this->inspectorID)->get();

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

            $last = end($this->defects);
            $this->lastdef = $last['type'] ?? null;
            $this->lastqty = $last['qty'] ?? null;

            // Group small defects by large defect
            foreach ($defect as $item) {
                $large = $item->Defect;

                $smallDef = SmallInsp::select('LargeDefect', 'SmallDefect', 'Qty')->where('LargeDefect', $large)
                    ->where('PPFNo', $ppf)
                    ->where('InspectorID', $this->inspectorID)
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

    //updating from dropdown
   #[On('dropdown-updated')]
public function receiveDropdownData($data)
{
    foreach ($data['forms'] as $formId => $formData) {

        $existingDefects = $this->dropdownForms[$formId]['defects'] ?? [];
        $existingSmallDefects = $this->dropdownForms[$formId]['smallDefects'] ?? [];

        $incomingDefects = $formData['defects'] ?? [];
        $incomingSmallDefects = $formData['smallDefects'] ?? [];
        
        foreach ($incomingDefects as $def) {
            $exists = collect($existingDefects)->contains(fn($d) => $d['type'] === $def['type']);
            if (!$exists) {
                $existingDefects[] = $def;
            }
        }

        // Merge small defects grouped by LargeDefect
       // Merge small defects grouped by LargeDefect
        foreach ($incomingSmallDefects as $large => $smalls) {

            if (!isset($existingSmallDefects[$large])) {
                $existingSmallDefects[$large] = [];
            }

            foreach ($smalls as $small) {
                $exists = collect($existingSmallDefects[$large])
                    ->contains(fn($s) => strtolower(trim($s['type'] ?? '')) === strtolower(trim($small['type'] ?? '')));
                if (!$exists && !empty($small['type'])) {
                    $existingSmallDefects[$large][] = $small;
                }
            }
        }

        // Assign back to dropdownForms without overwriting merged defects
        $this->dropdownForms[$formId] = $formData;
        $this->dropdownForms[$formId]['defects'] = $existingDefects;
        $this->dropdownForms[$formId]['smallDefects'] = $existingSmallDefects;
    }
}

    //To Fetch Rework
    #[On('LoadReworksPren')]
    public function LoadReworksPren($ppf)
    {
        $reworkss = ReworkInsp::select('HFNo', 'TotalInspQty', 'Defect', 'Quantity')->where('PPFNo', $ppf)->where('InspectorID', $this->inspectorID)->get();

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


    public function render()
    {

        return view('livewire.templates.prencode');
    }

    public function mount()
    {
        $this->dispatch('removelock');
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $UserName = WorkerName::select('名前')->Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
        $inspectorID = Worker::select('作業員CD')->Where('社員CD', $this->encoder)->first();
        $this->inspectorID = $inspectorID->作業員CD;
        $this->process = session('process');

        if ($this->process === 'VI') {
            $this->dispatch('ProcessVI');
        } elseif ($this->process === 'MD') {
            $this->dispatch('ProcessMD');
        } else {
            $this->dispatch('ProcessHF');
        }
    }

    #[On('DeletePPFPren')]
    public function fetchdeleteppf($data)
    {
        $this->ppf = $data['ppf'];
        $this->dispatch('confirm-deletePren');
    }

    #[On('deletePrencode')]
    public function deletePrencode()
    {
        DefectInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        ReworkInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        SmallInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        PRInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        session()->flash('success', 'Delete successfully!');
    }
    public function editPrencode()
    {
        DefectInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        ReworkInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        SmallInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        PrInsp::where('InspectorID', $this->inspectorID)->where('PPFNo', $this->ppf)->delete();
        $this->submitPrencode();
    }

    #[On('fetchTotalInspection')]
    public function fetchTotalInspection($data)
    {
        $this->totalInspection = $data;
    }

    public function submitPrencode()
    {
        if (empty($this->ppf)) {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        if ($this->ppf === "0") {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }

        if (!empty($this->ppf)) {
            PRInsp::Create([
                'InspectorID' => $this->inspectorID,
                'insp_name' => $this->username,
                'PPFNo' => $this->ppf,
                'total_inspect' => $this->totalInspection,
                'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                'Process' => $this->process
            ]);
        }

        if (!empty($this->rework)) {
            foreach ($this->rework as $reworks) {
                $type = $reworks['type'] ?? $reworks['newtype'] ?? null;
                $qty  = isset($reworks['quan']) ? (float)$reworks['quan'] : (float)($reworks['newquan'] ?? 0);
                $hfno = $reworks['newhfno'] ?? $reworks['hfno'];

                if (!$type || $qty <= 0) {
                    continue;
                }

                ReworkInsp::create([
                    'HFNo' => $hfno ?? '',
                    $this->hfno1 => $hfno[0] ?? '',
                    $this->hfno2 => $hfno[1] ?? '',
                    $this->hfno3 => $hfno[2] ?? '',
                    $this->hfno4 => $hfno[3] ?? '',
                    $this->hfno5 => $hfno[4] ?? '',
                    'InspectorID' => $this->inspectorID,
                    'insp_name' => $this->username,
                    'PPFNo' => $this->ppf,
                    'Defect' => $type ?? null,
                    'Quantity' => $qty ?? null,
                    'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                    'TotalInspQty' => $reworks['totalinsp'] ?? null,
                    'Process' => $this->process
                ]);
            }
        } else {
            ReworkInsp::create([
                'HFNo' => null,
                'InspectorID' => $this->inspectorID,
                'insp_name' => $this->username,
                'PPFNo' => $this->ppf,
                'Defect' => null,
                'Quantity' => null,
                'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                'TotalInspQty' => null,
                'Process' => $this->process
            ]);
        }

        if (empty($this->defects) || count($this->defects) === 0) {

            DefectInsp::create([

                'PPFNo' => (float) $this->ppf,
                'Defect' => '',
                'Quantity' => null,
                'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                'InspectorID' => $this->inspectorID,
                'insp_name' => $this->username,
                'Process' => $this->process
            ]);
        } else {

            foreach ($this->defects as  $defect) {
                //dd($this->defects);
                $type = $defect['type'] ?? $defect['newDefect'] ?? null;
                $qty  = isset($defect['qty']) ? (float)$defect['qty'] : (float)($defect['newQuan'] ?? '');
                if (!$type || $qty <= 0) continue;
                DefectInsp::create([
                    'PPFNo' => (float) $this->ppf,
                    'Defect' => $type,
                    'Quantity' => $qty,
                    'DateEncode' => Carbon::now()->format('Y-m-d h:i:s A'),
                    'InspectorID' => $this->inspectorID,
                    'insp_name' => $this->username,
                    'Process' => $this->process
                ]);
            }
        }

        if (!empty($this->smalldefects)) {
            // SmallInsp::select('PPFNo', 'LargeDefect', 'SmallDefect', 'Qty')->where('PPFNo', $this->ppf)
            //     ->where('dFlg', 'VI')
            //     ->delete();

            foreach ($this->smalldefects as $largeDefect => $smalls) {
                foreach ($smalls as $small) {
                    SmallInsp::create([
                        'InspectorID' => $this->inspectorID,
                        'PPFNo'       => $this->ppf,
                        'LargeDefect' => $largeDefect, // <-- the name, not the array
                        'SmallDefect' => $small['newSmallDefect'] ?? $small['type'],
                        'Qty'         => $small['newSmallQuan'] ?? $small['qty'],
                        'Process' => $this->process
                    ]);
                }
            }
        }



        session()->flash('successAdd', 'Data inserted successfully!');
    }
}
