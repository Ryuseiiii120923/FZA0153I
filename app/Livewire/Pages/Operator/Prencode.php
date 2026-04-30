<?php

namespace App\Livewire\Pages\Operator;

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
use App\Services\PrencodeService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use App\Traits\NormalizeSmallDefects;
use App\Traits\NormalizeDefects;

class Prencode extends Component
{
    use NormalizeSmallDefects;
    use NormalizeDefects;
    public $ppf;
    public $lotno;
    public $partno;
    public $matno;

    public $encoder, $username, $inspectorID;
    public $lastdef;
    public $lastqty;
    public $totalInspection;

    public $actiondash;
    public $hasErrorForm = [];
    public $defects = [];
    public $smalldefects = [];
    public $rework = [];
    public $dropdownForms = [];
    public $totalngrework;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $process;
    public $hasError = [];
    public $hasAnyError = false;
    public $isReceive = false;
    public $dropdownFinal = [];
    public $forms = [];
    public $isCheckPPF;
    public $methodSave;
    public $needToDeleteForm = [], $needToDeleteDefect = [], $needToDeleteRework = [], $needToDeleteDefectSmall = [];
    public $loading = false;


    protected function prencodeService()
    {
        return app(PrencodeService::class);
    }

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
    #[On('hasErrorPren')]
    public function hasError($data)
    {
        $this->hasErrorForm = $data['hasErrorForm'] ?? [];
        $this->hasError = $data['hasError'] ?? [];
        $this->hasAnyError = in_array(true, $this->hasError, true);
    }

    #[On('removeError')]
    public function removeError($formId)
    {

        unset($this->hasErrorForm[$formId]);
        $this->hasError = $this->hasErrorForm;

        $this->hasAnyError = in_array(true, $this->hasError, true);
    }

    public function Reworks(array $reworksData)
    {
        $result = $this->prencodeService()->handleReworks($this->rework, $reworksData);
        $this->rework = $result['reworks'];
        $this->totalngrework = $result['total'];
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
        $this->defects = $this->prencodeService()->handleDefect($this->defects, $payload);
    }


    //To Fetch Defect
    #[On('LoadDefectsPren')]
    public function LoadDefectsPren($ppf)
    {
        $result = $this->prencodeService()->LoadDefectsPrencode($ppf, $this->inspectorID);

        $this->defects = $result['defects'];
        $this->smalldefects = $result['smallDefects'];
        $this->lastdef = $result['lastdef'];
        $this->lastqty = $result['lastqty'];

        if (isset($result['defects']) || isset($result['smallDefects'])) {
            $this->dispatch('DefectFromUpdate', [
                'defects'       => $this->defects,
                'smallDefects' => $this->smalldefects,
            ]);
        }
    }

    //To Fetch Rework
    #[On('LoadReworksPren')]
    public function LoadReworksPren($ppf)
    {
        $result = $this->prencodeService()->LoadReworksPrencode($ppf, $this->inspectorID);

        $this->rework = $result['reworks'];
        $this->totalngrework = $result['totalNgRework'];

        if (isset($result['reworks'])) {
            $this->dispatch('ReworkFromUpdate', [
                'reworks' => $this->rework
            ]);
        }
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
                    $this->dropdownForms[$formId][$key] = $value;
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

    public function render()
    {

        return view('livewire.pages.operator.prencode');
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

    #[On('NeedToDeleteDefect')]
    public function deleteDefectFromChild($data)
    {
        $formId = $data['formId'];
        $type = $data['type'];


        $this->needToDeleteDefect[$formId][] = $type;
    }

    #[On('NeedToDeleteSmall')]
    public function deleteSmallFromChild($data)
    {
        $formId = $data['formId'];
        $type = $data['type'];
        $largeDefect = $data['largeDefect'];
        $this->needToDeleteDefectSmall[$formId][] = [
            'type' => $type,
            'largeDefect' => $largeDefect
        ];
    }

    #[On('NeedToDeleteRework')]
    public function deleteRework($data)
    {
        $formId = $data['formId'];
        $type = $data['type'];
        $hfno = $data['hfno'];

        $this->needToDeleteRework[$formId][] = [
            'type' => $type,
            'hfno' => $hfno,
        ];
    }


    public function editPrencode()
    {

        Db::beginTransaction();
        try {
            $this->loading = true;
            $ppfno = $this->ppf;
            if (!empty($this->needToDeleteForm)) {
                foreach ($this->needToDeleteForm as $form) {
                    $hf_id = $form['hf_id'] ?? null;
                    if ($hf_id) {
                        DB::table('hf_forms')
                            ->where('hf_id', $hf_id)
                            ->where('ppfno', $ppfno)
                            ->where('updated_by', $this->inspectorID)
                            ->delete();

                        DB::table('hf_defect')
                            ->where('hf_id', $hf_id)
                            ->where('ppfno', $ppfno)
                            ->where('updated_by', $this->inspectorID)
                            ->delete();

                        DB::table('hf_rework')
                            ->where('hf_id', $hf_id)
                            ->where('ppfno', $ppfno)
                            ->where('updated_by', $this->inspectorID)
                            ->delete();

                        DB::table('hf_small')
                            ->where('hf_id', $hf_id)
                            ->where('ppfno', $ppfno)
                            ->where('updated_by', $this->inspectorID)
                            ->delete();
                    }
                }
            }

            if (!empty($this->needToDeleteDefect)) {
                foreach ($this->dropdownForms as $formId => $form) {
                    if (isset($this->needToDeleteDefect[$formId])) {
                        $types = $this->needToDeleteDefect[$formId];
                        foreach ($types as $type) {
                            DB::table('hf_defect')
                                ->where('hf_id', $form['hf_id'])
                                ->where('defect', $type)
                                ->where('ppfno', $ppfno)
                                ->where('updated_by', $this->inspectorID)
                                ->delete();

                            DB::table('hf_small')
                                ->where('hf_id', $form['hf_id'])
                                ->where('large_defect', $type)
                                ->where('ppfno', $ppfno)
                                ->where('updated_by', $this->inspectorID)
                                ->delete();
                        }
                    }
                }
            }
            if (!empty($this->needToDeleteDefectSmall)) {
                foreach ($this->dropdownForms as $formId => $form) {
                    if (isset($this->needToDeleteDefectSmall[$formId])) {
                        $types = $this->needToDeleteDefectSmall[$formId];
                        foreach ($types as $type) {
                            DB::table('hf_small')
                                ->where('hf_id', $form['hf_id'])
                                ->where('large_defect', $type['largeDefect'])
                                ->where('small_defect', $type['type'])
                                ->where('updated_by', $this->inspectorID)
                                ->delete();
                        }
                    }
                }
            }

            if (!empty($this->needToDeleteRework)) {
                foreach ($this->dropdownForms as $formId => $form) {
                    if (isset($this->needToDeleteRework[$formId])) {
                        $items = $this->needToDeleteRework[$formId];
                        foreach ($items as $item) {
                            DB::table('hf_rework')
                                ->where('hf_id', $form['hf_id'])
                                ->where('rework_type', $item['type'])
                                ->where('HFNo', $item['hfno'])
                                ->where('updated_by', $this->inspectorID)
                                ->delete();
                        }
                    }
                }
            }

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
            $this->needToDeleteForm = [];
            $this->needToDeleteDefect = [];
            $this->needToDeleteDefectSmall = [];
            $this->needToDeleteRework = [];

            DB::commit();
            $this->loading = false;
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
            // dd($this->dropdownForms);
            foreach ($this->dropdownForms as $formId => $formData) {


                $hf_id = isset($formData['hf_id']) ? $formData['hf_id'] : null;
                $goodQty = $formData['GoodQty'] ?? 0;
                $totalGoodQty += $goodQty;

                // ✅ HF TABLE
                $hfRows[] = [
                    'hf_id'         => $hf_id,
                    'total_inspect' => $formData['total_inspect'] ?? null,
                    'updated_by'    => $this->inspectorID,
                    'ppfno'         => $this->ppf,
                    'inspect_REC'   => $formData['inspect_REC'],
                    'formId' => $formData['formId'] ?? null,
                    'created_at'    => $now,
                    'updated_date'  => $now,
                    'IsDoneRework'  =>  0,
                    'finishingProcedure' => $formData['finishingProcedure'] ?? null,
                    'ForRework' => !empty($formData['ForRework']) ? 1 : 0,
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
                        'formId' => $formData['formId'] ?? null
                    ];

                    // merge
                    $key = $type . '_' . ($formData['ForRework'] ? 1 : 0);

                    if (!isset($mergedDefects[$key])) {
                        $mergedDefects[$key] = [
                            'type' => $type,
                            'qty' => 0,
                            'ForRework' => $formData['ForRework'] ? 1 : 0
                        ];
                    }

                    $mergedDefects[$key]['qty'] += $qty;
                }

                // 🔵 SMALL DEFECTS
                foreach ($formData['smallDefects'] ?? [] as $large => $smalls) {
                    // Skip large defects that were deleted
                    if (isset($this->needToDeleteDefect[$formId]) && in_array($large, $this->needToDeleteDefect[$formId])) {
                        continue;
                    }

                    foreach ($smalls as $small) {
                        $type = $small['type'] ?? null;
                        $qty  = (float)($small['qty'] ?? 0);

                        if (!$type || $qty <= 0) continue;

                        // Skip individually deleted small defects
                        if (isset($this->needToDeleteDefectSmall[$formId])) {
                            foreach ($this->needToDeleteDefectSmall[$formId] as $deletedSmall) {
                                if ($deletedSmall['largeDefect'] === $large && $deletedSmall['type'] === $type) {
                                    continue 2; // skip this small defect
                                }
                            }
                        }

                        $smallRows[] = [
                            'hf_id'         => $hf_id,
                            'large_defect'  => $large,
                            'small_defect'  => $type,
                            'qty'           => $qty,
                            'updated_by'    => $this->inspectorID,
                            'ppfno'         => $this->ppf,
                            'inspect_REC'   => $formData['inspect_REC'],
                            'formId' => $formData['formId'] ?? null
                        ];

                        $key = $type . '_' . ($formData['ForRework'] ? 1 : 0);

                        // ✅ Merge ONLY if NOT ForRework
                        $key = $large . '_' . $type . '_' . ($formData['ForRework'] ? 1 : 0);

                        if (!isset($mergedSmallDefects[$key])) {
                            $mergedSmallDefects[$key] = [
                                'large'     => $large,
                                'type'      => $type,
                                'qty'       => 0,
                                'ForRework' => $formData['ForRework'] ? 1 : 0
                            ];
                        }

                        $mergedSmallDefects[$key]['qty'] += $qty;
                    }
                }

                // 🔵 REWORKS
                foreach ($formData['rework'] ?? [] as $rework) {

                    $type = $rework['type'] ?? null;
                    $qty  = (float)($rework['quan'] ?? 0);
                    $hfno = $rework['hfno'] ?? '';
                    $totalInsp = (string)($rework['totalinsp'] ?? 0);
                    $totalRework += $qty;

                    if (!$type || $qty <= 0) continue;

                    $reworkRows[] = [
                        'hf_id'       => $hf_id,
                        'hfno'        => $hfno,
                        'rework_type' => $type,
                        'qty'         => $qty,
                        'totalinsp'   => $totalInsp,
                        'updated_by'  => $this->inspectorID,
                        'ppfno'       => $this->ppf,
                        'inspect_REC' => $formData['inspect_REC'],
                        'formId' => $formData['formId'] ?? null
                    ];

                    $key = $hfno . '_' . $type;

                    if (!isset($mergedReworks[$key])) {
                        $mergedReworks[$key] = [
                            'hfno'      => $hfno,
                            'type'      => $type,
                            'totalinsp' => $rework['totalinsp'] ?? null,
                            'qty'       => 0,
                            'ForRework' => $formData['ForRework'] ? 1 : 0
                        ];
                    }

                    $mergedReworks[$key]['qty'] += $qty;
                }
            }
            DB::table('hf_forms')->upsert($hfRows, ['hf_id', 'ppfno', 'updated_by', 'formId'], ['total_inspect', 'GoodQty']);
            DB::table('hf_defect')->upsert($defectRows, ['hf_id', 'defect', 'ppfno', 'updated_by', 'formId'], ['qty']);
            DB::table('hf_small')->upsert($smallRows, ['hf_id', 'large_defect', 'small_defect', 'ppfno', 'updated_by', 'formId'], ['qty']);
            DB::table('hf_rework')->upsert($reworkRows, ['hfno', 'ppfno', 'updated_by', 'inspect_REC', 'rework_type', 'formId'], ['qty', 'totalinsp',]);

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
            foreach ($mergedDefects as $def) {

                $methodSave = ($def['ForRework'] == 0) ? 'iniInspect' : 'ReInspect';

                $defectInspRows[] = [
                    'insp_name' => $this->username,
                    'PPFNo'       => $this->ppf,
                    'Defect'      => $def['type'],
                    'Quantity'    => $def['qty'],
                    'DateEncode'  => $now,
                    'InspectorID' => $this->inspectorID,
                    'Process'     => $this->process,
                    'EncodeProcess' => $methodSave
                ];
            }

            DB::table('Inspector_Defect')->insert(
                $defectInspRows
            );

            // 🔵 MERGED SMALL DEFECTS
            $smallInspRows = [];
            foreach ($mergedSmallDefects as $s) {

                $encodeProcess = ($s['ForRework'] == 1)
                    ? 'ReRework'
                    : 'iniInspect';

                $smallInspRows[] = [
                    'InspectorID'   => $this->inspectorID,
                    'PPFNo'         => $this->ppf,
                    'LargeDefect'   => $s['large'],
                    'SmallDefect'   => $s['type'],
                    'Qty'           => $s['qty'],
                    'Process'       => $this->process,
                    'EncodeProcess' => $encodeProcess,
                ];
            }

            DB::table('Inspector_Small')->insert(
                $smallInspRows
            );

            // 🔵 MERGED REWORKS
            $reworkInspRows = [];
            foreach ($mergedReworks as $r) {
                $hfno = $r['hfno'];
                $reworkInspRows[] = [
                    'HFNo'         => $hfno,
                    'InspectorID'  => $this->inspectorID,
                    'insp_name' => $this->username,
                    'PPFNo'        => $this->ppf,
                    'Defect'       => $r['type'],
                    'Quantity'     => $r['qty'],
                    'DateEncode'   => $now,
                    'TotalInspQty' => $r['totalinsp'],
                    'Process'      => $this->process,
                ];
            }

            DB::table('Inspector_Rework')->insert($reworkInspRows);
            session()->flash('successAdd', 'Data inserted successfully!');
        } catch (\Exception $e) {
            Log::error('PR Encode Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ]);

            session()->flash('failed', 'Something went wrong!');
        }
    }
}
