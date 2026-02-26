<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\DefectInsp;
use App\Models\Defects;
use App\Models\ReworkInsp;
use App\Models\SmallInsp;
use App\Models\ViCheck;
use App\Models\WorkerName;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Url;

class Gldashboard extends Component
{
    public $wireAction;
    public $submitMethod = null;
    public $currentAction = null;
    public $ppf;
    public $actiondash;

    public $lotno;
    public $partno;
    public $matno;

    public $encoder, $username;
    public $lastdef;
    public $lastqty;
    public $expct;
    public $goodqty;
    public $ngratioqty;
    public $totalQty;
    public $Largedefects;
    public $moldno;
    public $pressno;
    public $shift;
    public $opt;
    public $details;
    public $InspectDates;
    public $UpdateDate;
    public $excssqty;
    public $lackqty;
    public $reworkqty;
    public $sampleqty;
    public $isAdd = false;

    public $defects = [];
    public $smalldefects = [];
    public $rework = [];
    public $plant;

    public $locked = false;

    public $auto;
    public bool $inspectorsDispatched = false;

    public $totalngrework;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $autoAdd  = false;

    protected $listeners = [
        'FromCheckppf' => 'Checkppf',
        'FromDefects' => 'Defects',
        'FromSmallDefects' => 'SmallDefects',
        'FromReworks' => 'Reworks',
        //'ClearForm' => 'ClearForm',
        'LoadDefectsPren' => 'LoadDefectsPren',
        'LoadReworksPren' => 'LoadReworksPren',
        'LoadReworksGL' => 'LoadReworksGL',
        'LoadDefectsGL' => 'LoadDefectsGL'
    ];
    #[On('dash-ppf')]
    public function action($data)
    {
        $this->actiondash = $data['actiondash'];
    }

    // public function mount()
    // {
    //     if ($this->actiondash === 'Add') {
    //         // $this->setAction('Add', true); // same as clicking Add
    //         //  $this->autoAdd = true;
    //         $this->dispatch('ppfcheck');
    //     }
    // }

    #[On('actionTable')]
    public function actioninTable($data)
    {
        if ($data['actiondash'] === 'Add') {
            $this->setAction('Add', true);
            //  $this->dispatch('ProgDis'); //deactivate the auto progress in check ppf
            //     $this->dispatch('addbutton');
            //     $this->dispatch('EditAction', 'Add');
            //     $this->dispatch('locked', false);
            //     // $this->dispatch('ppfcheck');
            //     // $this->dispatch('dash-ppf-check', $this->ppf);
            //     //  $this->dispatch('post-ppf', ['ppf' => $this->ppf]);
            //     //$this->dispatch('fromppf', $this->ppf);
            //     $this->ClearForm();
        }
        $this->ppf = $data['ppf'];

        $this->dispatch('dash-ppfGL', ['ppf' => (int)$this->ppf, 'actiondash' => $data['actiondash']]);
    }


    #[On('FromCheckppf')]
    public function FromCheckppf($data)
    {
        $this->expct = $data['expct'];
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




    public function ClearForm()
    {
        $this->dispatch('ClearForm');
    }
    public function setAction($action, $auto = false)
    {
        $this->currentAction = $action;

        $this->submitMethod = match ($action) {
            'Add' => 'addToDb',
            'Edit' => 'editToDb',
            'Delete' => 'deleteToDb',
            'View' => 'viewToDb'
        };

        switch ($action) {
            case 'Add':
                $this->dispatch('ProgDis'); //deactivate the auto progress in check ppf
                $this->dispatch('addbutton');
                $this->dispatch('EditAction', 'Add');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Edit':
                $this->dispatch('ProgDis');
                $this->dispatch('editbutton');
                $this->dispatch('EditAction', 'Edit');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Delete':
                $this->dispatch('ProgDis');
                $this->dispatch('deletebutton');
                $this->dispatch('EditAction', 'Delete');
                $this->ClearForm();
                break;

            case 'View':
                $this->dispatch('ProgDis');
                $this->dispatch('viewbutton');
                $this->dispatch('EditAction', 'View');
                $this->ClearForm();
                break;
        }

        $this->dispatch('Action', $this->submitMethod);
    }

    #[On('FetchDataGL')]
    public function FetchDatas($data)
    {
        $ppf = $this->resolvePpf($data);

        $checkppf = AddDefect::where('PPFNo', $ppf)->get()->first();
        if (!($checkppf)) {
            session()->flash('failed', 'Record not found');
            return;
        }
        $this->dispatch('LoadMainRecord', $ppf);
        $this->LoadDefectsGL($ppf);
        $this->dispatch('LoadPlantGL', $ppf);
        $this->LoadReworksGL($ppf);
        $this->dispatch('CalculateQuantities');
        $this->dispatch('dispatchUpdates');
        $this->dispatch('totalInspectedProgress');
    }


    private function resolvePpf($data)
    {
        return request()->input('ppf', $data);
    }


    public function LoadDefectsGL($ppf)
    {
        $encoders = DefectInsp::where('PPFNo', $ppf)
            ->whereNotNull('InspectorID')
            ->distinct()
            ->pluck('InspectorID');

        $this->defects = [];
        $this->smalldefects = [];

        foreach ($encoders as $encoder) {

            $defectsPerEncoder = DefectInsp::select('Defect')
                ->where('PPFNo', $ppf)
                ->where('InspectorID', $encoder)
                ->distinct()
                ->pluck('Defect');

            foreach ($defectsPerEncoder as $defectName) {

                // Add large defects (summed per encoder)
                $totalQty = DefectInsp::where('PPFNo', $ppf)
                    ->where('InspectorID', $encoder)
                    ->where('Defect', $defectName)
                    ->sum('Quantity');

                if ((int)$totalQty <= 0) continue;
                $latestDate = DefectInsp::where('PPFNo', $ppf)
                    ->where('InspectorID', $encoder)
                    ->where('Defect', $defectName)
                    ->max('DateEncode');
                $operatorname = DefectInsp::where('InspectorID', $encoder)
                    ->value('insp_name');

                $this->defects[] = [
                    'operatorid' => $encoder,
                    'operatorname' => $operatorname,
                    'type'       => $defectName,
                    'qty'        => (int)$totalQty,
                    'dateEncode' => $latestDate,
                ];

                // Load small defects ONLY ONCE
                $smallDefectsGrouped = SmallInsp::selectRaw('SmallDefect, SUM(Qty) as total_qty')
                    ->where('PPFNo', $ppf)
                    ->where('InspectorID', $encoder)
                    ->where('LargeDefect', $defectName)
                    ->groupBy('SmallDefect')
                    ->get();

                foreach ($smallDefectsGrouped as $s) {

                    $this->smalldefects[$defectName][$encoder][] = [
                        'type' => $s->SmallDefect,
                        'qty'  => (int) $s->total_qty,
                    ];
                }
            }
        }

        if (count($this->defects) > 0) {
            $this->dispatch('DefectFromUpdate', [
                'defects'      => $this->defects,
                'smallDefects' => $this->smalldefects,
            ]);

            $normalized = [];
            foreach ($this->defects as $d) {
                $type = strtolower(trim($d['type']));
                if ($type === '') continue;

                if (isset($normalized[$type])) {
                    $normalized[$type]['qty'] += $d['qty'];
                } else {
                    $normalized[$type] = [
                        'type' => $d['type'],
                        'qty'  => $d['qty'],
                    ];
                }
            }

            $defectPayload = array_map(fn($d) => [
                'newDefect' => $d['type'],
                'newQuan'   => $d['qty'],
                'action'    => '',
            ], array_values($normalized));

            $this->dispatch('FromDefects', [
                'defectData' => $defectPayload
            ]);

            $this->totalQty = collect($this->defects)->sum('qty');
            $this->dispatch('sendNg', $this->totalQty);

            $inspectors = collect($this->defects)
                ->pluck('operatorid')
                ->unique()
                ->values();

            if (!$this->inspectorsDispatched) {
                foreach ($inspectors as $id) {
                    $this->dispatch('InspectorUpdate', $id);
                }
                $this->inspectorsDispatched = true;
            }

            $last = end($this->defects);
            $this->lastdef = $last['type'] ?? null;
            $this->lastqty = $last['qty'] ?? null;
        }
    }


    // public function LoadDefectsGL($ppf, $encoder)
    // {
    //     $defect = DefectInsp::select('InspectorID', 'Defect', 'Quantity', 'DateEncode')
    //         ->where('PPFNo', $ppf)
    //         ->where('inspectorID', $encoder)
    //         ->get();

    //     if ($defect) {
    //         // Main defect list
    //         $this->defects = $defect->map(function ($item) {
    //             return [
    //                 'operatorid' => $item->InspectorID,
    //                 'type'       => $item->Defect,
    //                 'qty'        => (int)$item->Quantity,
    //                 'dateEncode' => $item->DateEncode
    //             ];
    //         })->filter(fn($d) => $d['qty'] > 0)
    //             ->values()
    //             ->toArray();

    //         $last = end($this->defects);
    //         $this->lastdef = $last['type'] ?? null;
    //         $this->lastqty = $last['qty'] ?? null;

    //         // Load small defects
    //         foreach ($defect as $item) {
    //             $large = $item->Defect;

    //             $smallDef = SmallInsp::select('LargeDefect', 'SmallDefect', 'Qty')
    //                 ->where('LargeDefect', $large)
    //                 ->where('inspectorID', $encoder)
    //                 ->where('PPFNo', $ppf)
    //                 ->get();

    //             $this->smalldefects[$large] = $smallDef->map(function ($s) {
    //                 return [
    //                     'SelectedLargeDefect' => $s->LargeDefect,
    //                     'type'                => $s->SmallDefect,
    //                     'qty'                 => $s->Qty
    //                 ];
    //             })->toArray();
    //         }

    //         if ($this->defects) {
    //             // Dispatch defects and small defects
    //             $this->dispatch('DefectFromUpdate', [
    //                 'defects'      => $this->defects,
    //                 'smallDefects' => $this->smalldefects,
    //             ]);

    //             // Normalize defects: sum quantities of the same type
    //             $normalized = [];
    //             foreach ($this->defects as $d) {
    //                 $type = trim($d['type']);
    //                 $qty  = (int)$d['qty'];

    //                 if ($type === '') continue;

    //                 $key = strtolower($type);

    //                 if (isset($normalized[$key])) {
    //                     $normalized[$key]['qty'] += $qty;
    //                 } else {
    //                     $normalized[$key] = [
    //                         'type' => $type,
    //                         'qty'  => $qty
    //                     ];
    //                 }
    //             }

    //             // Prepare payload for FromDefects
    //             $defectPayload = array_map(fn($d) => [
    //                 'newDefect' => $d['type'],
    //                 'newQuan'   => $d['qty'],
    //                 'action'    => ''
    //             ], array_values($normalized));
    //             // Dispatch normalized defects
    //             $this->dispatch('FromDefects', [
    //                 'defectData' => $defectPayload
    //             ]);
    //         }

    //         // Dispatch total quantity
    //         $this->totalQty = collect($this->defects)->sum('qty');
    //         $this->dispatch('sendNg', $this->totalQty);

    //         // Dispatch unique inspectors
    //         $inspectors = collect($this->defects)
    //             ->pluck('operatorid')
    //             ->filter(fn($id) => !empty($id))
    //             ->unique()
    //             ->values();

    //         if (!$this->inspectorsDispatched) {
    //             foreach ($inspectors as $id) {
    //                 $this->dispatch('InspectorUpdate', $id);
    //             }
    //             $this->inspectorsDispatched = true;
    //         }
    //     }
    // }

    public function LoadReworksGL($ppf)
    {
        $reworkss = ReworkInsp::select('InspectorID','insp_name', 'HFNo', 'TotalInspQty', 'Defect', 'Quantity', 'DateEncode')->where('PPFNo', $ppf)->get();

        if ($reworkss) {
            $this->rework = $reworkss->map(function ($item) {
                return [
                    'operatorid' => $item->InspectorID,
                    'hfno' => $item->HFNo,
                    'operatorname' => $item->insp_name,
                    'totalinsp' => $item->TotalInspQty,
                    'type' => $item->Defect,
                    'quan' => $item->Quantity,
                    'dateEncode' => $item->DateEncode
                ];
            });

            if ($this->rework) {
                $this->dispatch('ReworkFromUpdate', [
                    'reworks' => $this->rework
                ]);

                foreach ($this->rework as $r) {
                    $this->dispatch('FromReworks', [
                        'reworksData' => [
                            'hfno'      => $r['hfno'],
                            'type'      => $r['type'],
                            'quan'      => $r['quan'],
                            'totalinsp' => $r['totalinsp'],
                            'action'    => 'initial'
                        ]
                    ]);
                }
            }
        }


        $this->totalngrework = collect($this->rework)
            ->sum(fn($x) => (int) $x['quan']);

        $this->dispatch('GoodNg');
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
        // if ($this->autoAdd) {
        //     // Make sure to provide a PPF if it is null
        //     $ppf = $this->ppf ?? $this->defaultPPF();

        //     // Dispatch to the other component
        //     $this->dispatch('fromppf', $ppf);

        //     // Reset the flag so we don’t dispatch again
        //     $this->autoAdd = false;
        // }
        return view('livewire.templates.gldashboard');
    }
}
