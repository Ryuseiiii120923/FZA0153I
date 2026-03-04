<?php

namespace App\Livewire\Templates;

use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
use App\Models\HF\SmallDefect;
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
            $existingReworks = $this->dropdownForms[$formId]['rework'] ?? [];

            $incomingDefects = $formData['defects'] ?? [];
            $incomingSmallDefects = $formData['smallDefects'] ?? [];
            $incomingReworks = $formData['rework'] ?? [];

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

            foreach ($incomingReworks as $rework) {
                $exists = collect($existingReworks)->contains(fn($r) => $r['hfno'] === $rework['hfno'] && $r['type'] === $rework['type']);
                if (!$exists) {
                    $existingReworks[] = $rework;
                }
            }

            // Assign back to dropdownForms without overwriting merged defects
            $this->dropdownForms[$formId] = $formData;
            $this->dropdownForms[$formId]['defects'] = $existingDefects;
            $this->dropdownForms[$formId]['smallDefects'] = $existingSmallDefects;
            $this->dropdownForms[$formId]['rework'] = $existingReworks;
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
        Defect::where('ppfno', $this->ppf)->delete();
        Rework::where('ppfno', $this->ppf)->delete();
        SmallDefect::where('ppfno', $this->ppf)->delete();
        HF::where('ppfno', $this->ppf)->delete();
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
        if (empty($this->ppf) || $this->ppf === "0") {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }

        $mergedDefects = [];
        $mergedSmallDefects = [];
        $mergedReworks = [];

        foreach ($this->dropdownForms as $formData) {

            HF::create([
                'hf_id' => (int)$formData['hf_id'] ?? null,
                'total_inspect' => $formData['total_inspect'] ?? null,
                'created_at' => now(),
                'updated_by' => $this->inspectorID,
                    'ppfno' => $this->ppf
            ]);
            // 🔵 MERGE LARGE DEFECTS
            foreach ($formData['defects'] ?? [] as $defect) {

                Defect::create(
                    [
                        'hf_id' => (int)$formData['hf_id'] ?? null,
                        'defect' => $defect['type'] ?? null,
                        'qty' => $defect['qty'] ?? null,
                        'created_at' => now(),
                        'updated_by' => $this->inspectorID,
                    'ppfno' => $this->ppf
                    ]
                );

                $type = $defect['type'] ?? null;
                $qty  = (float)($defect['qty'] ?? 0);

                if (!$type || $qty <= 0) continue;

                if (!isset($mergedDefects[$type])) {
                    $mergedDefects[$type] = 0;
                }

                $mergedDefects[$type] += $qty;
            }

            // 🔵 MERGE SMALL DEFECTS
            foreach ($formData['smallDefects'] ?? [] as $large => $smalls) {

                foreach ($smalls as $small) {

                SmallDefect::create([
                        'hf_id' => (int)$formData['hf_id'] ?? null,
                        'large_defect' => $large ?? null,
                        'small_defect' => $small['type'] ?? null,
                        'qty' => $small['qty'] ?? null,
                        'created_at' => now(),
                        'updated_by' => $this->inspectorID,
                    'ppfno' => $this->ppf
                    ]);
                    $type = $small['type'] ?? null;
                    $qty  = (float)($small['qty'] ?? 0);

                    if (!$type || $qty <= 0) continue;

                    if (!isset($mergedSmallDefects[$large][$type])) {
                        $mergedSmallDefects[$large][$type] = 0;
                    }

                    $mergedSmallDefects[$large][$type] += $qty;
                }
            }

            // 🔵 MERGE REWORKS
            foreach ($formData['rework'] ?? [] as $rework) {
                Rework::create([
                    'hf_id' => (int)$formData['hf_id'] ?? null,
                    'hfno' => $rework['hfno'] ?? null,
                    'rework_type' => $rework['type'] ?? null,
                    'qty' => $rework['quan'] ?? null,
                    'created_at' => now(),
                    'updated_by' => $this->inspectorID,
                    'ppfno' => $this->ppf
                ]);
                $type = $rework['type'] ?? null;
                $qty  = (float)($rework['quan'] ?? 0);
                $hfno = $rework['hfno'] ?? '';

                if (!$type || $qty <= 0) continue;

                $key = $hfno . '_' . $type;

                if (!isset($mergedReworks[$key])) {
                    $mergedReworks[$key] = [
                        'hfno' => $hfno,
                        'type' => $type,
                        'totalinsp' => $rework['totalinsp'] ?? null,
                        'qty' => 0
                    ];
                }

                $mergedReworks[$key]['qty'] += $qty;
            }
        }

        // ✅ SAVE MAIN RECORD ONCE
        PRInsp::create([
            'InspectorID'   => $this->inspectorID,
            'insp_name'     => $this->username,
            'PPFNo'         => $this->ppf,
            'total_inspect' => $this->totalInspection,
            'DateEncode'    => now(),
            'Process'       => $this->process
        ]);

        // ✅ SAVE MERGED LARGE DEFECTS
        foreach ($mergedDefects as $type => $qty) {
            DefectInsp::create([
                'PPFNo'       => (float)$this->ppf,
                'Defect'      => $type,
                'Quantity'    => $qty,
                'DateEncode'  => now(),
                'InspectorID' => $this->inspectorID,
                'insp_name'   => $this->username,
                'Process'     => $this->process
            ]);
        }

        // ✅ SAVE MERGED SMALL DEFECTS
        foreach ($mergedSmallDefects as $large => $smalls) {
            foreach ($smalls as $type => $qty) {
                SmallInsp::create([
                    'InspectorID' => $this->inspectorID,
                    'PPFNo'       => $this->ppf,
                    'LargeDefect' => $large,
                    'SmallDefect' => $type,
                    'Qty'         => $qty,
                    'Process'     => $this->process
                ]);
            }
        }

        // ✅ SAVE MERGED REWORKS
        foreach ($mergedReworks as $rework) {

            $hfno = $rework['hfno'];

            ReworkInsp::create([
                'HFNo'         => $hfno,
                'HFNo1'        => $hfno[0] ?? null,
                'HFNo2'        => $hfno[1] ?? null,
                'HFNo3'        => $hfno[2] ?? null,
                'HFNo4'        => $hfno[3] ?? null,
                'HFNo5'        => $hfno[4] ?? null,
                'InspectorID'  => $this->inspectorID,
                'insp_name'    => $this->username,
                'PPFNo'        => $this->ppf,
                'Defect'       => $rework['type'],
                'Quantity'     => $rework['qty'],
                'DateEncode'   => now(),
                'TotalInspQty' => $rework['totalinsp'],
                'Process'      => $this->process
            ]);
        }

        session()->flash('successAdd', 'Data inserted successfully!');
    }
}
