<?php

namespace App\Livewire\Glcomponents;

use App\Models\Defects as ModelsDefects;
use App\Models\SmallDef;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;

class Defects extends Component
{
    public Collection $Largedefects;
    public Collection $SmallDefectsForModal;
    //public $SmallDefectsForModal = [];
    public $smallDefects = [];
    public $currentInspectorId;
    public $newSmallDefect = '';
    public $newSmallQuan = '';
    public $defects = [];
    public $newDefect = '';
    public $newQuan;
    public $TotalNg = 0;
    public $lastTotalNg = 0;
    public $selectedLargeDefect = null;
    public $smalldefectData;
    public $defectData;
    public $lastDefectType;
    public $lastDefectQty;
    public $editingType;
    public $editingTypeSmall;
    public $locked = false;
    public $systemname;
    public $currentOperatorSmall;


    public $rules = [
        'newDefect' => 'required|string|max:255',
        'newQuan' => 'required|numeric|min:1',
    ];
    public $messages = [
        'newDefect.required' => 'Please enter a defect type.',
        'newDefect.string'   => 'The defect type must be a valid text.',
        'newDefect.max'      => 'The defect type cannot exceed 255 characters.',

        'newQuan.required' => 'Please enter a quantity.',
        'newQuan.numeric'  => 'The quantity must be a number.',
        'newQuan.min'      => 'The quantity must be at least 1.',
    ];

    public $listeners = [
        'FetchDefect' => 'fetchDefect',
        'FetchDefectDashboard' => 'FetchDefectDashboard',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm',
        'PPFFromOp' => 'PPFFromOp'
    ];
    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm()
    {
        $this->defects = [];
        $this->smallDefects = [];
        $this->TotalNg = 0;
    }

    // public function fetchDefect($data)
    // {
    //     $this->defects = $data['defects'] ?? [];
    //     $collection = collect($data['smallDefects'] ?? [])
    //         ->mapWithKeys(function ($items, $large) {
    //             return [
    //                 $large => collect($items)->map(function ($item) use ($large) {
    //                     return [
    //                         'LargeDefect' => $large,
    //                         'type'        => $item['type'] ?? null,
    //                         'qty'         => $item['qty'] ?? '',
    //                     ];
    //                 })->toArray()
    //             ];
    //         });

    //     $this->smallDefects = $collection->map(function ($group) {
    //         return collect($group)->map(function ($item) {
    //             return [
    //                 'type' => $item['type'],
    //                 'qty'  => $item['qty'],
    //             ];
    //         })->toArray();
    //     })->toArray();

    //     $lastLarge = array_key_last($this->smallDefects);
    //     $this->selectedLargeDefect = $lastLarge;

    //     $group = $this->smallDefects[$lastLarge] ?? [];
    //     $lastSmall = end($group);
    //     $this->newSmallDefect = $lastSmall['type'] ?? null;
    //     $this->newSmallQuan   = $lastSmall['qty'] ?? null;

    //     //dd($this->smallDefects);
    //     // Update totals
    //     $this->TotalNg = collect($this->defects)->sum('qty');

    //     // Dispatch full arrays
    //     //$this->sendDispatch();
    // }

    // public function fetchDefect($data)
    // {
    //     $this->defects = $data['defects'] ?? [];
    //     dd($data['smallDefects'] ?? []);

    //     $this->smallDefects = [];
    //     foreach ($data['smallDefects'] ?? [] as $largeDefect => $inspectors) {

    //         foreach ($inspectors as $inspectorId => $smallList) {

    //             // Only include small defects that belong to this inspector
    //             foreach ($smallList as $small) {

    //                 // Skip if this small defect is already recorded under another largeDefect for this inspector
    //                 $exists = collect($this->smallDefects)
    //                     ->flatten(2)
    //                     ->contains(
    //                         fn($d) => ($d['type'] ?? null) === ($small['type'] ?? null) && $d['operatorid'] === $inspectorId
    //                     );

    //                 if ($exists) continue;

    //                 $this->smallDefects[$largeDefect][$inspectorId][] = [
    //                     'type'        => $small['type'] ?? null,
    //                     'qty'         => (int)($small['qty'] ?? 0),
    //                     'operatorid'  => $inspectorId, // optional if you want to keep track
    //                 ];
    //             }
    //         }
    //     }

    //     // --------------------------
    //     // Fix last selection
    //     // --------------------------
    //     $lastLarge = array_key_last($this->smallDefects);
    //     $this->selectedLargeDefect = $lastLarge;

    //     $lastDefect = end($this->defects);

    //     if ($lastDefect) {
    //         $operatorId = $lastDefect['operatorid'] ?? null;
    //         $group = $this->smallDefects[$lastLarge][$operatorId] ?? [];
    //         $lastSmall = end($group);

    //         $this->newSmallDefect = $lastSmall['type'] ?? null;
    //         $this->newSmallQuan   = $lastSmall['qty'] ?? null;
    //     }

    //     // Update total NG
    //     $this->TotalNg = collect($this->defects)
    //         ->sum(fn($d) => (int)$d['qty']);
    // }


    public function fetchDefect($data)
{
    $this->defects = $data['defects'] ?? [];
    $this->smallDefects = [];
    // --------------------------
    // Build smallDefects array
    // --------------------------
    foreach ($data['smallDefects'] ?? [] as $largeDefect => $encodeGroups) {

        foreach ($encodeGroups as $encodeProcess => $inspectors) {

            // Normalize encodeProcess: map iniInspect -> ReInspect
            $processKey = strtolower(trim($encodeProcess));
            foreach ($inspectors as $inspectorId => $smallList) {


                $inspectorKey = (string)$inspectorId;

                foreach ($smallList as $small) {

                    $this->smallDefects[$largeDefect][$processKey][$inspectorKey][] = [
                        'type'        => $small['type'] ?? null,
                        'qty'         => (int)($small['qty'] ?? 0),
                        'operatorid'  => $inspectorId,
                    ];
                }
            }
        }
    }

    // --------------------------
    // Fix last selection
    // --------------------------
    $lastDefect = end($this->defects);
    $this->selectedLargeDefect = $lastDefect['type'] ?? null;

    if ($lastDefect) {
        $operatorId    = (string)($lastDefect['operatorid'] ?? null);
        $encodeProcess = strtolower(trim($lastDefect['encodeProcess'] ?? null));
        $largeDefect   = $lastDefect['type'] ?? null;
        // Safe lookup with fallback
        $group = $this->smallDefects[$largeDefect][$encodeProcess][$operatorId]
            ?? [];

        $lastSmall = end($group);

        $this->newSmallDefect = $lastSmall['type'] ?? null;
        $this->newSmallQuan   = $lastSmall['qty'] ?? null;
    }

    // --------------------------
    // Update total NG
    // --------------------------
    $this->TotalNg = collect($this->defects)
        ->sum(fn($d) => (int)$d['qty']);
    // dd($this->smallDefects,$this->defects, $lastDefect);
}


    public function mount($systemname = null)
    {
        $this->Largedefects = ModelsDefects::select('LargeDefect')
            ->distinct()
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();

        $this->systemname = $systemname;
    }


    public function loadSmallDefects($largeDefect)
    {
        $this->selectedLargeDefect = $largeDefect;

        $this->SmallDefectsForModal = ModelsDefects::query()
            ->select('SmallDefect')
            ->distinct()
            ->whereNotNull('SmallDefect')
            ->where('LargeDefect', $largeDefect)
            ->orderBy('SmallDefect', 'asc')
            ->get();
    }
    public function setLargeDefect($defect)
    {
        $this->selectedLargeDefect = $defect;
    }

    public function deleteDefect($operator, $type)
    {
        // Remove the defect matching both operator and type
        $this->defects = collect($this->defects)
            ->reject(fn($defect) => ($defect['type'] === $type) && ($defect['operatorid'] == $operator))
            ->values()
            ->toArray();

        // Remove small defects for this operator and type, if exist
        if (isset($this->smallDefects[$type][$operator])) {
            unset($this->smallDefects[$type][$operator]);

            // If no more operators left under this type, remove the type entirely
            if (empty($this->smallDefects[$type])) {
                unset($this->smallDefects[$type]);
            }
        }

        // Recalculate total NG
        $this->TotalNg = collect($this->defects)->sum('qty');

        $this->dispatch('TriggerGoodNg');
    }



    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.glcomponents.defects');
    }

    public function sendDefect()
    {
        $value = $this->TotalNg;
        $this->dispatch('sendNg', $value);
        $this->dispatch('GoodNg');
    }

    #[On('FromDoneRework')]
    public function fromDoneRework($data)
    {

        $diff =  $data - $this->lastTotalNg;
        $this->TotalNg += $diff;
        $this->lastTotalNg = $data;
        $this->sendDefect();
    }

    public function sendDispatch()
    {
        // $this->dispatch('FromDefects', [
        //     'newDefect' => $this->newDefect,
        //     'newQuan'   => $this->newQuan,
        // ]);

        // $this->dispatch('FromSmallDefects', [
        //     'SelectedLargeDefect' => $this->selectedLargeDefect,
        //     'newSmallDefect' => $this->newSmallDefect,
        //     'newSmallQuan'   => $this->newSmallQuan,
        // ]);
        // dd($this->selectedLargeDefect);

        $this->dispatch('FromDefects');
        $this->dispatch('FromSmallDefects');

        // dd(
        //     [
        // $this->smallDefects,
        // $this->newSmallQuan,
        // $this->selectedLargeDefect

        //     ]);

        $this->dispatch('sendNg', $this->TotalNg);
    }

    public function deleteDefectArray($operator, $type)
    {
        // Remove the defect from the main defects array
        $this->defects = collect($this->defects)
            ->reject(fn($defects) => $defects['type'] === $type)
            ->values()
            ->toArray();

        // Remove any associated small defects
        if (isset($this->smallDefects[$type])) {
            unset($this->smallDefects[$type]);
        }

        // Update totals
        $this->TotalNg = collect($this->defects)->sum('qty');

        // Send updated defects array to frontend
        $this->sendDefect();

        // Dispatch the updated defects array like in addDefect
        $this->defectData = [
            'newDefect' => $type,
            'action'    => 'delete',
        ];
        $this->dispatch('FromDefects', $this->defectData);

        // Trigger recalculation of good / ng quantities
        $this->dispatch('TriggerGoodNg');
    }

    public function deleteDefectSmall($operator, $type)
    {
        $large = $this->selectedLargeDefect;

        if (isset($this->smallDefects[$large][$operator][$type])) {
            unset($this->smallDefects[$large][$operator][$type]);

            // Clean up operator array if empty
            if (empty($this->smallDefects[$large][$operator])) {
                unset($this->smallDefects[$large][$operator]);
            }

            $this->smalldefectData = [
                'SelectedLargeDefect' => $large,
                'Operator'            => $operator,
                'newSmallDefect'      => $type,
                'action'              => 'delete',
            ];

            $this->dispatch('FromSmallDefects', smalldefectData: $this->smalldefectData);

            $this->newSmallDefect = '';
            $this->newSmallQuan = '';
            $this->currentOperatorSmall = null;
        }
    }

    public function updateDefectArray()
    {
        foreach ($this->defects as &$defect) {
            if ($defect['type'] === $this->editingType) {
                $defect['qty'] = $this->newQuan;

                break;
            }
        }

        $this->defectData = [
            'newDefect' => $this->editingType,
            'newQuan'   => $this->newQuan,
            'action'    => 'update',
        ];
        $this->dispatch('FromDefects', $this->defectData);

        $this->editingType = null;
        $this->newQuan = '';
    }

    public function updateDefectSmallArray()
    {
        $large = $this->selectedLargeDefect;
        $operator = $this->currentOperatorSmall;

        if (!isset($this->smallDefects[$large][$operator])) {
            return;
        }

        // Find small defect by type
        foreach ($this->smallDefects[$large][$operator] as &$defect) {
            if ($defect['type'] === $this->editingTypeSmall) {
                $defect['qty'] = $this->newSmallQuan;
                break;
            }
        }

        // Dispatch updated data
        $this->smalldefectData = [
            'SelectedLargeDefect' => $large,
            'Operator'            => $operator,
            'type'                => $this->editingTypeSmall,
            'qty'                 => $this->newSmallQuan,
            'action'              => 'update',
        ];

        $this->dispatch('FromSmallDefects', smalldefectData: $this->smalldefectData);

        // Reset editing fields
        $this->editingTypeSmall = null;
        $this->newSmallQuan = '';
        $this->currentOperatorSmall = null;
    }



    public function startEdit($operator, $type)
    {
        $this->editingType = $type;
        $this->currentInspectorId = $operator;

        // Find the defect that matches both type and operator
        $defect = collect($this->defects)
            ->first(fn($d) => ($d['type'] === $type) && ($d['operatorid'] ?? null) == $operator);

        // If found, populate the edit field
        $this->newQuan = $defect['qty'] ?? 0; // default to 0 if not set
    }



    // public function startEditSmall($large, $operator, $type)
    // {
    //     $this->selectedLargeDefect = $large; // now correct
    //     $this->currentOperatorSmall = $operator;
    //     $this->editingTypeSmall = $type;

    //     // Use collection to find the small defect by type inside the operator's array
    //     if (isset($this->smallDefects[$large][$operator])) {
    //         $smalldefect = collect($this->smallDefects[$large][$operator])
    //             ->firstWhere('type', $type);

    //         if ($smalldefect) {
    //             $this->newSmallQuan = $smalldefect['qty'];
    //         }
    //     }
    // }

    public function startEditSmall($largeDefect, $operator, $smallType)
    {
        $this->selectedLargeDefect = $largeDefect;
        $this->currentOperatorSmall = $operator;
        $this->editingTypeSmall = $smallType;

        $smalldefect = $this->smallDefects[$largeDefect][$operator][$smallType] ?? null;
        if ($smalldefect) {
            $this->newSmallQuan = $smalldefect['qty'];
        }
    }
}
