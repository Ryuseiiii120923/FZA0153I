<?php

namespace App\Services\General;

use App\Models\CheckHF;
use App\Models\MoldingResult;
use App\Repositories\Defect\DefectRepository;
use App\Repositories\General\InitialDataRepository;
use App\Repositories\General\PPFRepository;
use App\Repositories\General\WorkerRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InitialDataService
{
    public function __construct(protected DefectRepository $defectRepository, protected InitialDataRepository $initialDataRepository, protected WorkerRepository $workerRepository, protected PPFRepository $ppfrepository) {}
    public function loadMainRecord(int $ppf)
    {
        $record = $this->initialDataRepository->fetchMainTable($ppf);
        if (!$record) {
            return null;
        }
        $data = [];
        $getexpct = $this->defectRepository->getExpected($ppf);
        $largeDefects = $this->defectRepository->getDefectforMain();

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

        $data['hfno1'] = $record->HFNo1;
        $data['hfno2'] = $record->HFNo2;
        $data['hfno3'] = $record->HFNo3;
        $data['hfno4'] = $record->HFNo4;
        $data['hfno5'] = $record->HFNo5;

        $data['inspection_date'] = Carbon::parse($record->InspectionDate)->format('Y-m-d');
        $data['updated_at'] = Carbon::parse($record->DateEncode)->format('Y-m-d h:i:s A');
        $defects = [];

        if (!empty($record->Defect) && $record->Quantity > 0) {
            $defects[] = [
                'type' => $record->Defect,
                'qty'  => $record->Quantity
            ];
        }

        $data['defects'] = $defects;
        $worker = $this->workerRepository->getWorkerName($record->Encoder);
        $data['username'] = $worker->名前 ?? '';
        $data['details'] = $record->Details;
        $data['auto'] = $record->AutoMachine;

        return $data;
    }

    public function checkIfinFinal(int $ppf)
    {
        $reinspects = $this->initialDataRepository->getReinspect($ppf);

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

    public function checkFifo(string $partNo, string $currentLot): array
    {

        $oldestLot = DB::table('成形実績')
            ->leftJoin('VICheck', '成形実績.流動NO', '=', 'VICheck.PPFNo')
            ->where('品番', $partNo)
            ->whereNull('QtyOut')
            ->where(function ($query) {
                $query->whereNull('完納検査')
                    ->orWhere('完納検査', '');
            })
            ->orderByRaw('SUBSTRING(成形ﾛｯﾄ, 3, 6)')
            ->value('成形ﾛｯﾄ');
        if (empty($oldestLot)) {
            return [
                'fifo' => true,
                'advance' => false,
                'oldestLot' => null,
            ];
        }

        if (substr($oldestLot, 2, 6) < substr($currentLot, 2, 6)) {

            return [
                'fifo' => false,
                'openDialog' => true,
                'advance' => true,
                'oldestLot' => $oldestLot,
            ];
        }
        return [
            'fifo' => true,
            'advance' => false,
            'oldestLot' => $oldestLot,
        ];
    }

    public function loadPPfData(int $ppf, string $action)
    {
        $isExistMain = $this->initialDataRepository->isPPFExist($ppf);
        $moldResult = MoldingResult::where('流動NO', $ppf)->first();
        $hf = CheckHF::where('流動NO', $ppf)->first();
        if ($isExistMain && $action == 'add') {
            return (['error' => 'This ppf is already confirm']);
        }

        if (!$moldResult) {
            return (['error' => 'PPF No does not encoded on Molding Result!']);
        }
        if (!$hf) {
            return (['error' => 'PPF No does not encoded on Hand Finishing Result!']);
        }

        if($this->initialDataRepository->isLockedByFinalInspection($ppf)){
            return(['error' => 'PPFNo was already encoded to Final Inspection Process.']);
        }
        
        $pcValue = DB::table('Seihin')->where('', $moldResult['品番']);
        $pcValue = $pcValue ?? 0;
        if ($pcValue != "0" && trim($moldResult['金型NO']) != "") {
            $postcure = DB::table('Postcure')->where('PPFNo', $ppf)->first();

            if ($postcure) {
                $pc = (int) $postcure->Good;
                if (!$pc) {
                    return ['error' => 'PPFNo is not registered on Postcure!'];
                }
            }
        }
         return ([
            'lotno'   => $moldResult ? preg_replace('/\s+/', '', $moldResult->成形ﾛｯﾄ) : '',
            'partno'  => $moldResult ? preg_replace('/\s+/', '', $moldResult->品番) : '',
            'matno'   => $moldResult ? preg_replace('/\s+/', '', $moldResult->材料名) : '',
            'moldno'  => $moldResult ? preg_replace('/\s+/', '', $moldResult->金型NO) : '',
            'pressno' => $moldResult ? preg_replace('/\s+/', '', $moldResult->PRESSNO) : '',
            'shift'   => $moldResult ? preg_replace('/\s+/', '', $moldResult->班) : '',
            'opt'     => $moldResult ? preg_replace('/\s+/', '', $moldResult->作業員CD) : '',
            'expct'   => $hf ? round($hf->合格数) : 0,
        ]);
    }

   
}
