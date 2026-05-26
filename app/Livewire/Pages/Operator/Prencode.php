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
use App\Services\PR\SubmitPrencodeService;
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
    public bool $locked = false;
    public array $isDropdownUpdate = [];


    protected function prencodeService()
    {
        return app(PrencodeService::class);
    }
    protected function submitPrencodeService()
    {
        return app(SubmitPrencodeService::class);
    }

    public $listeners = [
        'FromCheckppf' => 'Checkppf',
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


    public function Checkppf($data)
    {
        $this->ppf = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->partno = $data['partno'];
        $this->matno = $data['matno'];
    }


    //To Fetch Defect
    #[On('LoadDefectsPren')]
    public function LoadDefectsPren($ppf)
    {
        $result = $this->prencodeService()->loadDefects($ppf, $this->inspectorID);

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
        $result = $this->prencodeService()->loadReworks($ppf, $this->inspectorID);

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
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $UserName = WorkerName::select('名前')->Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
        $this->inspectorID = Worker::where('社員CD', $this->encoder)
            ->value('作業員CD');
        // $this->process = session('process');
        // if ($this->process === 'VI') {
        //     $this->dispatch('ProcessVI');
        // } elseif ($this->process === 'MD') {
        //     $this->dispatch('ProcessMD');
        // } else {
        //     $this->dispatch('ProcessHF');
        // }
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
                    $hf_id = $this->needToDeleteForm['hf_id'] ?? null;
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

    #[On('isDropdownUpdate')]
    public function isDropdownUpdate($formId)
    {
        if ($formId !== null) {
            $this->isDropdownUpdate[$formId] = true;
        }
    }

    public function submitPrencode()
    {
        if (empty($this->ppf) || $this->ppf === "0") {
            session()->flash('failed', 'Please Enter PPF!');
            return;
        }
        try {
            $this->submitPrencodeService()->submit(
                [
                    'ppfno' => $this->ppf,
                    'username' => $this->username,
                    'updated_by' => $this->inspectorID,
                    'total_inspect' => $this->totalInspection,
                    'form' => $this->dropdownForms,
                    'needToDeleteForm' => $this->needToDeleteForm,
                    'needToDeleteDefect' => $this->needToDeleteDefect,
                    'needToDeleteDefectSmall' => $this->needToDeleteDefectSmall,
                    'isDropdownUpdate' => $this->isDropdownUpdate,
                ]
            );
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
