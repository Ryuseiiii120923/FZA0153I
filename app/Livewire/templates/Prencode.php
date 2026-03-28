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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public $hasError = false;
    public $isReceive = false;
    public $dropdownFinal = [];
    public $forms = [];
    public $isCheckPPF;
    public $methodSave;
    public $needToDeleteForm = [];


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

    #[On('dash-ppf1')]
    public function actions($data)
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



    #[On('hasErrorPren')]
    public function hasError($error)
    {
        $this->hasError = $error;
    }

    #[On('removeError')]
    public function removeError()
    {
        $this->hasError = false;
    }

    //from the dropdown
    #[On('dropdown-updated')]
    public function receiveDropdownData($data)
    {

        foreach ($this->dropdownForms as $formId => $form) {
            if (!isset($data['forms'][$formId])) {
                unset($this->dropdownForms[$formId]);
            }
        }

        foreach ($data['forms'] as $formId => $formData) {

            if (!isset($this->dropdownForms[$formId])) {
                $this->dropdownForms[$formId] = $formData;
            } else {

                foreach ($formData as $key => $value) {

                    if (is_array($value)) {
                        $this->dropdownForms[$formId][$key] = $value;
                    } else {
                        $this->dropdownForms[$formId][$key] = $value;
                    }
                }
            }
            $this->dispatch(
                'FetchHfNo',
                hf_id: $formData['hf_id'] ?? null,
                total_inspect: (int) ($formData['total_inspect'] ?? 0),
                form_id: $formId
            );
        }
        $this->dropdownForms = $data['forms'];
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
        $this->inspectorID = Worker::where('社員CD', $this->encoder)
            ->value('作業員CD');
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
        Defect::where('ppfno', $this->ppf)->where('updated_by', $this->inspectorID)->delete();
        Rework::where('ppfno', $this->ppf)->where('updated_by', $this->inspectorID)->delete();
        SmallDefect::where('ppfno', $this->ppf)->where('updated_by', $this->inspectorID)->delete();
        HF::where('ppfno', $this->ppf)->where('updated_by', $this->inspectorID)->delete();
        session()->flash('success', 'Delete successfully!');
    }

    #[On('NeedToDeleteForm')]
    public function deleteForm(array $data)
    {
        $this->needToDeleteForm = $data;
    }

    public function editPrencode()
    {
        DB::beginTransaction();
        try {
            $ppfno = $this->ppf;
            DB::table('hf_forms')
                ->where('ppfno', $ppfno)
                ->where('updated_by', $this->inspectorID)
                ->delete();

            DB::table('hf_defect')
                ->where('ppfno', $ppfno)
                ->where('updated_by', $this->inspectorID)
                ->delete();

            DB::table('hf_rework')
                ->where('ppfno', $ppfno)
                ->where('updated_by', $this->inspectorID)
                ->delete();

            DB::table('hf_small')
                ->where('ppfno', $ppfno)
                ->where('updated_by', $this->inspectorID)
                ->delete();

            // 🔴 DELETE SUMMARY TABLES (1 query each)
            DB::table('Inspector_Defect')
                ->where('InspectorID', $this->inspectorID)
                ->where('PPFNo', $this->ppf)
                ->delete();

            DB::table('Inspector_Rework')
                ->where('InspectorID', $this->inspectorID)
                ->where('PPFNo', $this->ppf)
                ->delete();

            DB::table('Inspector_Small')
                ->where('InspectorID', $this->inspectorID)
                ->where('PPFNo', $this->ppf)
                ->delete();

            DB::table('Inspector_PR')
                ->where('InspectorID', $this->inspectorID)
                ->where('PPFNo', $this->ppf)
                ->delete();

            // 🔵 RE-SAVE USING OPTIMIZED METHOD
            $this->methodSave = 'edit';
            $this->submitPrencode();

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Edit PR Encode Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ]);

            session()->flash('failed', 'Edit failed!');
        }
    }

    #[On('fetchTotalInspection')]
    public function fetchTotalInspection($data)
    {
        $this->totalInspection = $data;
    }

    public function addPrencode()
    {

        //dd($this->dropdownForms);
        foreach ($this->dropdownForms as $formData) {
            $totalinspect = $formData['total_inspect'] ?? null;
            $this->totalInspection += (int)$totalinspect;
        }
        $this->submitPrencode();
    }

    #[On('IsCheckPPF')]
    public function IsCheckPPF($data)
    {
        $this->isCheckPPF = $data;
    }

    public function submitPrencode()
    {
        if (empty($this->ppf) || $this->ppf === "0") {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        $totalGoodQty = 0;
        $totalNg = 0;
        $totalRework = 0;

        DB::beginTransaction();

        try {

            $now = now();

            $hfRows = [];
            $defectRows = [];
            $smallRows = [];
            $reworkRows = [];

            $mergedDefects = [];
            $mergedSmallDefects = [];
            $mergedReworks = [];

            // 🔵 LOOP FORMS
            foreach ($this->dropdownForms as $formData) {


                $hf_id = isset($formData['hf_id']) ? (int)$formData['hf_id'] : null;
                $goodQty = $formData['GoodQty'] ?? 0;
                $totalGoodQty += $goodQty;

                // ✅ HF TABLE
                $hfRows[] = [
                    'hf_id'         => $hf_id,
                    'total_inspect' => $formData['total_inspect'] ?? null,
                    'updated_by'    => $this->inspectorID,
                    'ppfno'         => $this->ppf,
                    'inspect_REC'   => $formData['inspect_REC'],
                    'created_at'    => $now,
                    'updated_date'    => $now,
                    'IsDoneRework' =>  0,
                    'ForRework' => !empty($formData['isRework']) ? 1 : 0,
                    'GoodQty' => $goodQty
                ];

                // 🔵 DEFECTS
                foreach ($formData['defects'] ?? [] as $defect) {

                    $type = $defect['type'] ?? null;
                    $qty  = (float)($defect['qty'] ?? 0);

                    $totalNg += $qty;

                    if (!$type || $qty <= 0) continue;

                    $defectRows[] = [
                        'hf_id'       => $hf_id,
                        'defect'      => $type,
                        'qty'         => $qty,
                        'updated_by'  => $this->inspectorID,
                        'ppfno'       => $this->ppf,
                        'inspect_REC' => $formData['inspect_REC'],
                    ];

                    // merge
                    $mergedDefects[$type] = ($mergedDefects[$type] ?? 0) + $qty;
                }

                // 🔵 SMALL DEFECTS
                foreach ($formData['smallDefects'] ?? [] as $large => $smalls) {
                    foreach ($smalls as $small) {

                        $type = $small['type'] ?? null;
                        $qty  = (float)($small['qty'] ?? 0);

                        if (!$type || $qty <= 0) continue;

                        $smallRows[] = [
                            'hf_id'         => $hf_id,
                            'large_defect'  => $large,
                            'small_defect'  => $type,
                            'qty'           => $qty,
                            'updated_by'    => $this->inspectorID,
                            'ppfno'         => $this->ppf,
                            'inspect_REC'   => $formData['inspect_REC'],
                        ];

                        $mergedSmallDefects[$large][$type] =
                            ($mergedSmallDefects[$large][$type] ?? 0) + $qty;
                    }
                }

                // 🔵 REWORKS
                foreach ($formData['rework'] ?? [] as $rework) {

                    $type = $rework['type'] ?? null;
                    $qty  = (float)($rework['quan'] ?? 0);
                    $hfno = $rework['hfno'] ?? '';
                    $totalRework += $qty;

                    if (!$type || $qty <= 0) continue;

                    $reworkRows[] = [
                        'hf_id'       => $hf_id,
                        'hfno'        => $hfno,
                        'rework_type' => $type,
                        'qty'         => $qty,
                        'totalinsp'   => $rework['totalinsp'] ?? null,
                        'updated_by'  => $this->inspectorID,
                        'ppfno'       => $this->ppf,
                        'inspect_REC' => $formData['inspect_REC'],
                    ];

                    $key = $hfno . '_' . $type;

                    if (!isset($mergedReworks[$key])) {
                        $mergedReworks[$key] = [
                            'hfno'      => $hfno,
                            'type'      => $type,
                            'totalinsp' => $rework['totalinsp'] ?? null,
                            'qty'       => 0
                        ];
                    }

                    $mergedReworks[$key]['qty'] += $qty;
                }
            }
            DB::table('hf_forms')->insert($hfRows);
            DB::table('hf_defect')->insert($defectRows);
            DB::table('hf_small')->insert($smallRows);
            DB::table('hf_rework')->insert($reworkRows);

            // 🚀 BULK UPSERTS

            $totalInspect = array_sum(
                array_column(
                    array_filter($hfRows, function ($row) {
                        return $row['ForRework'] == 0;
                    }),
                    'total_inspect'
                )
            );

            // ✅ MAIN RECORD
            DB::table('Inspector_PR')->insert([
                'InspectorID'   => $this->inspectorID,
                'PPFNo'         => $this->ppf,
                'total_inspect' => $totalInspect,
                'DateEncode'    => $now,
                'Process'       => $this->process,
                'TotalNg' => $totalNg,
                'TotalRework' => $totalRework,
                'TotalGood' => $totalGoodQty

            ]);

            // 🔵 MERGED DEFECTS
            $defectInspRows = [];
            foreach ($mergedDefects as $type => $qty) {
                $defectInspRows[] = [
                    'PPFNo'       => $this->ppf,
                    'Defect'      => $type,
                    'Quantity'    => $qty,
                    'DateEncode'  => $now,
                    'InspectorID' => $this->inspectorID,
                    'Process'     => $this->process
                ];
            }

            DB::table('Inspector_Defect')->insert($defectInspRows);

            // 🔵 MERGED SMALL DEFECTS
            $smallInspRows = [];
            foreach ($mergedSmallDefects as $large => $smalls) {
                foreach ($smalls as $type => $qty) {
                    $smallInspRows[] = [
                        'InspectorID' => $this->inspectorID,
                        'PPFNo'       => $this->ppf,
                        'LargeDefect' => $large,
                        'SmallDefect' => $type,
                        'Qty'         => $qty,
                        'Process'     => $this->process
                    ];
                }
            }

            DB::table('Inspector_Small')->insert($smallInspRows);

            // 🔵 MERGED REWORKS
            $reworkInspRows = [];
            foreach ($mergedReworks as $r) {

                $hfno = $r['hfno'];
                $reworkInspRows[] = [
                    'HFNo'         => $hfno,
                    'InspectorID'  => $this->inspectorID,
                    'PPFNo'        => $this->ppf,
                    'Defect'       => $r['type'],
                    'Quantity'     => $r['qty'],
                    'DateEncode'   => $now,
                    'TotalInspQty' => $r['totalinsp'],
                    'Process'      => $this->process
                ];
            }

            DB::table('Inspector_Rework')->insert($reworkInspRows);

            DB::commit();

            session()->flash('successAdd', 'Data inserted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PR Encode Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ]);

            session()->flash('failed', 'Something went wrong!');
        }
    }
}
