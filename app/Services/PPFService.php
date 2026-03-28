<?php

namespace App\Services;

use App\Models\CheckHF;
use App\Models\CheckPPF;
use App\Models\Operator\PRInsp;
use App\Repositories\DoneReworkRepository;
use App\Repositories\PPFRepository;
use Illuminate\Support\Facades\DB;

class PPFService
{
    protected $ppfRepo, $doneReworkRepo;

    public function __construct(PPFRepository $ppfRepo, DoneReworkRepository $doneReworkRepo)
    {
        $this->ppfRepo = $ppfRepo;
        $this->doneReworkRepo = $doneReworkRepo;
    }

    public function loadProcessRecord($ppf, $inspectorID, $systemname, $actiondash)
    {
        $ppfexisting = $this->ppfRepo->getAddDefect($ppf);
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

        $check = CheckPPF::where('流動NO', $ppf)->first();
        $hf = CheckHF::where('流動NO', $ppf)->first();

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

    public function calculateGoodQtyForm(array $form): array
    {
        $defectQty = collect($form['defects'] ?? [])->sum('qty');
        $reworkQty = collect($form['rework'] ?? [])->sum('quan');

        $totalNg = $defectQty + $reworkQty;

        $goodQty = ($form['total_inspect'] ?? 0) - $totalNg;

        return [
            'goodQty' => $goodQty,
            'defectNg' => $defectQty,
            'reworkNg' => $reworkQty,
        ];
    }

    public function FetchForRework()
    {
        return $this->ppfRepo->getTotalReworkPending();
    }

    public function saveDoneRework($data)
    {
        return DB::transaction(function () use ($data) {
            $hfId = $data['hf_id'];
             $this->doneReworkRepo->saveMainForm($data);
            $this->doneReworkRepo->saveDefects($hfId, $data['defects'] ?? [], $data['ppfno'], $data['encoder']);
            $this->doneReworkRepo->saveReworks($hfId, $data['reworks'] ?? [], $data['ppfno'], $data['encoder']);
            $this->doneReworkRepo->saveSmallDefects($hfId, $data['smalldefects'] ?? [], $data['ppfno'], $data['encoder']);
            $this->doneReworkRepo->updateFlag($data['ppfno']);
        });


    }

    public function fetchGoodQty($ppf){
        return $this->ppfRepo->FetchGoodQty($ppf);
    }
}
