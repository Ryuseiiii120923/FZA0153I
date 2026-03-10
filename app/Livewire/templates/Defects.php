<?php

namespace App\Livewire\Templates;

use App\Models\Defects as ModelsDefects;
use App\Models\Operator\SmallDef;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;

class Defects extends Component
{
    public Collection $Largedefects;
    public Collection $SmallDefectsForModal;
    //public $SmallDefectsForModal = [];
    public $smallDefects = [];
    public $newSmallDefect = '';
    public $newSmallQuan = '';
    public $defects = [];
    public $newDefect = '';
    public $newQuan;
    public $TotalNg = 0;
    public $TotalSmallQuan = 0;
    public $selectedLargeDefect = null;
    public $smalldefectData;
    public $defectData;
    public $lastDefectType;
    public $lastDefectQty;
    public $editingType;
    public $editingTypeSmall;
    public $locked = false;
    public $systemname;
    public $newCategory;
    public $formId;


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
        'FetchDefect' => 'fetchdefect',
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
        $this->TotalSmallQuan = 0;
    }

    public function fetchDefect($data)
    {
        $this->defects = $data['defects'] ?? [];

        //         $newDefects = collect($data['defects'] ?? [])
        //             ->map(function ($item) {
        //                 return [
        //                     'type' => $item['type'] ?? null,
        //                     'qty'   => $item['qty'] ?? 0,
        //                 ];
        //             })
        //             ->toArray();

        //             // Merge with existing defects
        // $this->defects = array_merge($this->defects, $newDefects);

        // get the last newDefect
        // $last = end($this->defects);

        // $this->lastDefectType = $last['type'] ?? null;
        // $this->lastDefectQty  = $last['qty'] ?? null;
        // $this->smallDefects = $data['smallDefects'] ?? [];
        // dd($this->defects,$this->smallDefects);

        // $collection = collect($data['smallDefects'] ?? [])
        //     ->map(function ($item) {
        //         return [
        //             'LargeDefect' => $item['LargeDefect'] ?? null,
        //             'type'        => $item['type'] ?? null,
        //             'qty'         => $item['qty'] ?? 0,
        //         ];
        //     });

        $collection = collect($data['smallDefects'] ?? [])
            ->mapWithKeys(function ($items, $large) {
                return [
                    $large => collect($items)->map(function ($item) use ($large) {
                        return [
                            'LargeDefect' => $large,
                            'type'        => $item['type'] ?? null,
                            'qty'         => $item['qty'] ?? '',
                        ];
                    })->toArray()
                ];
            });

        $this->smallDefects = $collection->map(function ($group) {
            return collect($group)->map(function ($item) {
                return [
                    'type' => $item['type'],
                    'qty'  => $item['qty'],
                ];
            })->toArray();
        })->toArray();

        $lastLarge = array_key_last($this->smallDefects);
        $this->selectedLargeDefect = $lastLarge;

        $group = $this->smallDefects[$lastLarge] ?? [];
        $lastSmall = end($group);
        $this->newSmallDefect = $lastSmall['type'] ?? null;
        $this->newSmallQuan   = $lastSmall['qty'] ?? null;

        //dd($this->smallDefects);
        // Update totals
        $this->TotalNg = collect($this->defects)->sum('qty');
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum('qty');

        // Dispatch full arrays
        //$this->sendDispatch();
    }


    public function mount($systemname = null, $formId = null, $loadedDefects = [], $loadedSmallDefects = [])
    {
        $this->formId = $formId;
        $this->Largedefects = ModelsDefects::select('LargeDefect')
            ->distinct()
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();

        $this->systemname = $systemname;
        $this->setDefects($loadedDefects);
        $this->setSmallDefects($loadedSmallDefects);
    }
    public function setDefects($defects)
    {
        $this->defects = $defects ?? [];
        $this->TotalNg = collect($this->defects)->sum('qty');
    }

    public function setSmallDefects($smallDefects)
    {
        $this->smallDefects = $smallDefects ?? [];
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum('qty');
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
    public function addDefect()
    {
        $this->validate();

        $normalizedNewDefect = strtolower(trim($this->newDefect));
        $category = $this->newCategory ?? 'large'; // make sure you have a category selector in your form

        $existing = collect($this->defects)->contains(function ($defect) use ($normalizedNewDefect, $category) {
            return strtolower(trim($defect['type'])) === $normalizedNewDefect
                && strtolower(trim($defect['category'] ?? 'large')) === strtolower($category);
        });

        $existsInMaster = $this->Largedefects
            ->pluck('LargeDefect')
            ->map(fn($d) => strtolower(trim($d)))
            ->contains($normalizedNewDefect);

        if (!$existsInMaster) {
            $this->addError('newDefect', 'This defect type does not exist in the master list');
            return;
        }

        if ($existing) {
            $this->addError('newDefect', 'This defect type in this category already exists');
            return;
        }

        $this->defects[] = [
            'type' => trim($this->newDefect),
            'category' => $category,
            'qty' => $this->newQuan
        ];

        $this->TotalNg = collect($this->defects)->sum('qty');

        $this->newDefect = '';
        $this->newQuan = '';
        $this->newCategory = null; // reset category if you have one
        $this->sendDefect();

        $this->dispatch('defects-updated', [
            'defects' => $this->defects,
            'formId' => $this->formId
        ]);

        $this->dispatch('TriggerGoodNg');
    }

    public function deleteDefect($type)
    {
        $this->defects = collect($this->defects)
            ->reject(fn($defect) => $defect['type'] === $type)
            ->values()
            ->toArray();

        if (isset($this->smallDefects[$type])) {
            unset($this->smallDefects[$type]);

            $this->TotalNg = collect($this->defects)->sum('qty');
        }
        $this->dispatch('TriggerGoodNg');
    }


    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.templates.defects');
    }

    public function sendDefect()
    {
        $value = $this->TotalNg;
        $this->dispatch('sendNg', $value);
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

    public function deleteDefectArray($type)
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
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum('qty');

        // Send updated defects array to frontend
        $this->sendDefect();

        // Dispatch the updated defects array like in addDefect
        $this->defectData[] = [
            'type' => $type,
            'action'    => 'delete',
        ];


        $this->dispatch('defects-updated', [
            'defects' => [
                [
                    'type' => $type,
                    'qty' => 0
                ]
            ],
            'action' => 'delete',
            'formId' => $this->formId
        ]);

        // Trigger recalculation of good / ng quantities
        $this->dispatch('TriggerGoodNg');
    }

    public function deleteDefectSmall($type)
    {
        $largeDefect = $this->selectedLargeDefect ?? array_key_first($this->smallDefects);

        if (!isset($this->smallDefects[$largeDefect])) {
            return;
        }

        // Remove the small defect
        $this->smallDefects[$largeDefect] = collect($this->smallDefects[$largeDefect])
            ->reject(fn($defect) => trim($defect['type'] ?? '') === trim($type))
            ->values()
            ->toArray();

        // Dispatch updated structure
        $this->dispatch('defects-updated', [
            'smallDefects' => [
                $largeDefect => [
                    [
                        'type' => $type,
                        'qty'  => 0
                    ]
                ]
            ],
            'formId' => $this->formId,
            'action' => 'delete'
        ]);

        // Reset inputs
        $this->newSmallDefect = '';
        $this->newSmallQuan = '';
    }
    public function addSmallDefect()


    {
        $this->validate([
            'newSmallDefect' => 'required|string|max:255',
            'newSmallQuan'   => 'required|numeric|min:1',
        ], [
            'newSmallDefect.required' => 'Please enter a small defect type.',
            'newSmallQuan.required'   => 'Please enter a quantity.',
        ]);

        $this->SmallDefectsForModal = ModelsDefects::query()
            ->select('SmallDefect')
            ->distinct()
            ->whereNotNull('SmallDefect')
            ->where('LargeDefect', $this->selectedLargeDefect)
            ->orderBy('SmallDefect', 'asc')
            ->get();

        $normalizedNewSmallDefect = strtolower(trim($this->newSmallDefect));
        $existsInMaster = $this->SmallDefectsForModal
            ->pluck('SmallDefect')             // get all small defect names
            ->map(fn($d) => strtolower(trim($d))) // normalize
            ->contains($normalizedNewSmallDefect);

        if (!$existsInMaster) {
            $this->addError('newSmallDefect', 'This small defect does not exist in the master list');
            return;
        }
        $existing = collect($this->smallDefects[$this->selectedLargeDefect] ?? [])->contains(function ($defect) use ($normalizedNewSmallDefect) {
            return strtolower(trim($defect['type'])) === $normalizedNewSmallDefect;
        });

        if ($existing) {
            $this->addError('newSmallDefect', 'This small defect type already exists');
            return;
        }

        $currentSmallTotal = collect($this->smallDefects[$this->selectedLargeDefect] ?? [])->sum('qty');

        $largeDefectQty = collect($this->defects)->firstWhere('type', $this->selectedLargeDefect)['qty'] ?? '';

        if (($currentSmallTotal + $this->newSmallQuan) > $largeDefectQty) {
            $this->addError('newSmallQuan', 'Total small defect quantity cannot exceed large defect quantity.');
            return;
        }

        $this->smallDefects[$this->selectedLargeDefect][] = [
            'type' => trim($this->newSmallDefect),
            'qty'  => (int) $this->newSmallQuan,
        ];

        // Optionally update total
        $this->TotalSmallQuan += $this->newSmallQuan;

        $this->smalldefectData[] = [
            'SelectedLargeDefect' => $this->selectedLargeDefect,
            'newSmallDefect' => $this->newSmallDefect,
            'newSmallQuan'   => $this->newSmallQuan,
        ];

        // Reset input fields
        $this->newSmallDefect = '';
        $this->newSmallQuan = '';
        $smallDefectsForDispatch = $this->smallDefects;

        // Dispatch events if needed
        // $this->dispatch('FromSmallDefects', smalldefectData: $this->smalldefectData);
        $this->dispatch('defects-updated', [
            'smallDefects' => $smallDefectsForDispatch,
            'selectedLargeDefect' => $this->selectedLargeDefect,
            'formId' => $this->formId
        ]);

        $this->sendDefect(); // if you want to update TotalNg
    }

    public function updateDefectArray()
    {
        foreach ($this->defects as &$defect) {
            if ($defect['type'] === $this->editingType) {
                $defect['qty'] = $this->newQuan;

                break;
            }
        }


        $this->defectData[] = [
            'type' => trim($this->editingType),
            'qty'   => $this->newQuan,
        ];

        $this->dispatch('defects-updated', [
            'defects' => [[
                'type' => trim($this->editingType),
                'qty'   => $this->newQuan,
            ]],
            'formId' => $this->formId,
            'action' => 'update'
        ]);

        $this->editingType = null;
        $this->newQuan = '';
    }

    public function updateDefectSmallArray()
    {
        $large = $this->selectedLargeDefect;
        if (!isset($this->smallDefects[$large])) return;

        foreach ($this->smallDefects[$large] as &$defect) {
            if ($defect['type'] === $this->editingTypeSmall) {
                $defect['qty'] = (float) $this->newSmallQuan;
                break;
            }
        }

        // Dispatch the current smallDefects array directly
        $this->dispatch('defects-updated', [
            'smallDefects' => $this->smallDefects,
            'formId' => $this->formId,
            'action' => 'update'
        ]);

        $this->editingTypeSmall = null;
        $this->newSmallQuan = '';
    }


    public function startEdit($type)
    {
        $this->editingType = $type;

        // Find the rework by type
        $defects = collect($this->defects)->firstWhere('type', $type);

        if ($defects) {
            $this->newQuan = $defects['qty'];
        }
    }

    public function startEditSmall($type)
    {
        $this->editingTypeSmall = $type;

        // Find the rework by type
        $smalldefects = collect($this->smallDefects[$this->selectedLargeDefect])->firstWhere('type', $type);

        if ($smalldefects) {
            $this->newSmallQuan = $smalldefects['qty'];
        }
    }
}
