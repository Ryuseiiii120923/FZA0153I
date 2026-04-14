<?php

namespace App\Services;

use App\Models\CheckHF;
use App\Models\CheckPPF;
use App\Models\Operator\PRInsp;
use App\Repositories\DefectRepository;
use App\Repositories\DoneReworkRepository;
use App\Repositories\PPFRepository;
use App\Repositories\WorkerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PPFService
{
    protected $ppfRepo, $doneReworkRepo, $defectRepo, $workerRepo;

    public function __construct(PPFRepository $ppfRepo, DoneReworkRepository $doneReworkRepo, DefectRepository $defectRepo, WorkerRepository $workerRepo)
    {
        $this->ppfRepo = $ppfRepo;
        $this->defectRepo = $defectRepo;
        $this->doneReworkRepo = $doneReworkRepo;
        $this->workerRepo = $workerRepo;
    }

    public function loadProcessRecord($ppf, $inspectorID, $systemname, $actiondash)
    {
        $ppfexisting = $this->defectRepo->fetchAddDefect($ppf);
        $ppfrecordExist = $this->ppfRepo->checkPPFExistForInspector($ppf, $inspectorID);
        $check = $this->ppfRepo->getCheckPPF($ppf);
        $hf = $this->ppfRepo->getHF($ppf);
        $totalinsp = $this->ppfRepo->getTotalInspectionPerInspector($ppf, $inspectorID);

        if ($systemname == 'ProcessRecord' && $ppfrecordExist && $actiondash != 'edit') {
            return ['error' => 'This PPF is already encoded. Kindly review the table below for details.'];
        }
        if (!$check) {
            return ['error' => 'PPF No does not exist in Molding Results.'];
        }
        if (!$hf) {
            return ['error' => 'PPF No does not exist in Hand Finishing.'];
        }

        $pcValue = $this->ppfRepo->getPCValue($check->品番);

        if ($pcValue != "0" && trim($check->金型NO) != "") {
            $postcure = $this->ppfRepo->getPosture($ppf);
            if ($postcure) {
                $pc = (int) $postcure->Good;
                if (!$pc) {
                    return ['error' => 'PPFNo is not registered on Postcure!'];
                }
            }
        }

        if ($ppfexisting) {
            return ['error' => 'Already Registered'];
        }

        $check = DB::table('成形実績')->where('流動NO', $ppf)->first();
        $hf = DB::table('計量１')->where('流動NO', $ppf)->first();

        if (!$check) {
            return ['error' => 'PPF not Wfound in molding'];
        }

        if (!$hf) {
            return ['error' => 'PPF not found in HF'];
        }

        return [
            'lotno' => preg_replace('/\s+/', '', $check->成形ﾛｯﾄ),
            'partno' => preg_replace('/\s+/', '', $check->品番),
            'matno' => preg_replace('/\s+/', '', $check->材料名),
            'moldno' => preg_replace('/\s+/', '', $check->金型NO),
            'pressno' => preg_replace('/\s+/', '', $check->PRESSNO),
            'shift' => preg_replace('/\s+/', '', $check->班),
            'opt' => preg_replace('/\s+/', '', $check->作業員CD),
            'expct' => round($hf->合格数),
            'totalInspection' => $totalinsp,
        ];
    }

    public function totalInspectedProgress($ppf, $expectedQuantity)
    {
        $totalInspected = $this->ppfRepo->getTotalInspected($ppf);
        return [
            'totalInspection' => $totalInspected,
            'progressInsp' => $totalInspected . "/" . $expectedQuantity
        ];
    }

    public function totalInspectedProgressFetch($ppf, $inspectorID, $expectedQuantity)
    {
        $totalInspected = $this->ppfRepo->getTotalInspectionPerInspector($ppf, $inspectorID);
        return [
            'totalInspection' => $totalInspected,
            'progressInsp' => $totalInspected . "/" . $expectedQuantity
        ];
    }

    public function checkIfinFinal($ppf)
    {
        $reinspects = $this->ppfRepo->getReinspect($ppf);

        if (!$reinspects || $reinspects->isEmpty()) {
            return ['errorExist' => null];
        }

        foreach ($reinspects as $row) {
            if ((string)$row->ReInspect === "0" || (string)$row->ReInspect === "" || $row->PPFNo === null) {
                return [
                    'errorExist' => 'Updating Denied! PPFNo was already encoded to Final Inspection Process.'
                ];
            }
        }
    }

    public function checkIfPPFExist($ppf)
    {
        return $this->ppfRepo->getPPF($ppf);
    }

    public function loadMainRecord($ppf)
    {
        $record = $this->defectRepo->fetchAddDefect($ppf);


        if (!$record) {
            return null;
        }

        $data = [];
        $getexpct = $this->defectRepo->getExpected($ppf);
        $largeDefects = $this->defectRepo->getDefectforMain();

        $data['expct'] = $getexpct->合格数 ?? 0;
        $data['largeDefect'] = $largeDefects;
        $data['ppf'] = $record->PPFNo;
        $data['partno'] = $record->PartNo;
        $data['lotno'] = $record->Lotno;
        $data['matno'] = $record->MatNo;
        $data['moldno'] = $record->MDNo;
        $data['pressno'] = $record->PressNo;
        $data['shift'] = $record->Shift;
        $data['opt'] = $record->Operator;

        // Quantities
        $data['goodqty'] = $record->Good;
        $data['excssqty'] = $record->ExcessQty;
        $data['lackqty'] = $record->LackingQty;
        $data['reworkqty'] = $record->ReworkQty;
        $data['sampleqty'] = $record->SampleQty;

        // Inspection
        $data['insp1'] = $record->InspNo1;
        $data['insp2'] = $record->InspNo2;
        $data['insp3'] = $record->InspNo3;
        $data['insp4'] = $record->InspNo4;
        $data['insp5'] = $record->InspNo5;

        // Dates
        $data['inspection_date'] = Carbon::parse($record->InspectionDate)->format('Y-m-d');
        $data['updated_at'] = Carbon::parse($record->DateEncode)->format('Y-m-d h:i:s A');

        // Defects
        $defects = [];

        if (!empty($record->Defect) && $record->Quantity > 0) {
            $defects[] = [
                'type' => $record->Defect,
                'qty'  => $record->Quantity
            ];
        }

        $data['defects'] = $defects;


        // Worker
        $worker = $this->workerRepo->getWorkerName($record->Encoder);
        $data['username'] = $worker->名前 ?? '';

        // Others
        $data['details'] = $record->Details;
        $data['auto'] = $record->AutoMachine;

        return $data;
    }
}
