<?php

namespace App\Services;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\CheckPPF;
use App\Models\Operator\DefectInsp;
use App\Models\Operator\PRInsp;
use App\Models\Operator\ReworkInsp;
use App\Repositories\PrencodeRepository;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;

class PrencodeService
{
    protected $prencodeRepo;
    public function __construct(PrencodeRepository $prencodeRepo)
    {
        $this->prencodeRepo = $prencodeRepo;
    }


    public function loadDefects(string $ppf, int $inspectorID): array
    {
        $data = $this->prencodeRepo->fetchDefects($ppf, $inspectorID);

        if ($data->isEmpty()) {
            return [
                'defects' => [],
                'smallDefects' => [],
                'lastdef' => null,
                'lastqty' => null,
            ];
        }

        $defects = $data->map(fn($item) => [
            'type' => $item->Defect,
            'qty'  => (int) $item->Quantity,
        ])
            ->filter(fn($d) => $d['qty'] > 0)
            ->values()
            ->toArray();

        $last = collect($defects)->last();

        $small = $this->prencodeRepo->fetchSmallDefects($ppf, $inspectorID);

        $smallDefects = collect($small)
            ->groupBy('LargeDefect')
            ->map(fn($items) => $items->map(fn($s) => [
                'SelectedLargeDefect' => $s->LargeDefect,
                'type' => $s->SmallDefect,
                'qty'  => $s->Qty,
            ])->toArray())
            ->toArray();

        return [
            'defects' => $defects,
            'smallDefects' => $smallDefects,
            'lastdef' => $last['type'] ?? null,
            'lastqty' => $last['qty'] ?? null,
        ];
    }

    public function loadReworks(string $ppf, string $inspectorID)
    {
        $data = $this->prencodeRepo->fetchReworks($ppf, $inspectorID);
        if ($data->isEmpty()) {
            return [
                'reworks' => [],
                'totalNgRework' => 0
            ];
        }

        $reworks = $data->map(function ($item) {
            return [
                'hfno'      => $item->HFNo,
                'totalinsp' => $item->TotalInsp,
                'type'      => strtoupper(trim($item->Type)),
                'quan'      => (int) $item->Quantity
            ];
        })->values()->toArray();

        return [
            'reworks' => $reworks,
            'totalNgRework' =>  collect($reworks)->sum('quan')
        ];
    }

    public function loadData(string $ppf, int $inspectorID, string $systemName, string $actiondash)
    {
        $isExistMain = AddDefect::where('PPFNo', $ppf)->exists();
        $ppfrecord = DefectInsp::where('InspectorID', $inspectorID)
            ->where('PPFNo', $ppf)
            ->exists()
            ||
            ReworkInsp::where('InspectorID', $inspectorID)
            ->where('PPFNo', $ppf)
            ->exists()
            || PRInsp::where('InspectorID', $inspectorID)
            ->where('PPFNo', $ppf)
            ->exists();
        $check = CheckPPF::where('流動NO', $ppf)->first();
        $hf = CheckHF::where('流動NO', $ppf)->first();
        $totalinsp = PRInsp::where('PPFNo', $ppf)->where('InspectorID', $inspectorID)->first();

        if ($actiondash != 'edit' && $actiondash != 'view') {
            if ($systemName === 'ProcessRecord') {
                if ($ppfrecord) {
                    return (['error' => 'This PPF is already encoded. Kindly review the table below for details.']);
                }
            }
        }
   
        if(!$check){
            return(['error' => 'PPF No does not encoded on Molding Result!']);
        }
        if(!$hf){
            return(['error' => 'PPF No does not encoded on Hand Finishing Result!']);
        }

          $pcValue = DB::table('Seihin')->where('', $check['品番']);
        $pcValue = $pcValue ?? 0;
        if ($pcValue != "0" && trim($check['金型NO']) != "") {
            $postcure = DB::table('Postcure')->where('PPFNo', $ppf)->first();

            if ($postcure) {
                $pc = (int) $postcure->Good;
                if (!$pc) {
                    return ['error' => 'PPFNo is not registered on Postcure!'];
                }
            }
        }

        if($actiondash != 'edit' && $actiondash != 'view'){
            if ($systemName === 'ProcessRecord') {
                if ($ppfrecord) {
                    return (['error' => 'This PPF is already encoded. Kindly review the table below for details.']);
                }
            }
        }

        if($systemName === 'ProcessRecord' && $isExistMain && $actiondash == 'add'){
            return(['error' => 'This PPF already confirm. Please coordinate to your GL']);
        }elseif($systemName === 'GLDashboard' && $isExistMain && $actiondash == 'add'){
            return(['error' => 'This PPF is already confirm']);
        }

        return([
            'lotno'   => $check ? preg_replace('/\s+/', '', $check->成形ﾛｯﾄ) : '',
            'partno'  => $check ? preg_replace('/\s+/', '', $check->品番) : '',
            'matno'   => $check ? preg_replace('/\s+/', '', $check->材料名) : '',
            'moldno'  => $check ? preg_replace('/\s+/', '', $check->金型NO) : '',
            'pressno' => $check ? preg_replace('/\s+/', '', $check->PRESSNO) : '',
            'shift'   => $check ? preg_replace('/\s+/', '', $check->班) : '',
            'opt'     => $check ? preg_replace('/\s+/', '', $check->作業員CD) : '',
            'expct'   => $hf ? round($hf->合格数) : 0,
            'totalInspection' => $totalinsp ? $totalinsp->total_inspect : 0
        ]);

    }
}
