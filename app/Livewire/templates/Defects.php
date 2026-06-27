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
    public $dispatchPrefix;
    public $action;

    // --- Unified modal state (multi-defect staging) ---
    // Staged entries: [ ['type'=>'...','qty'=>0,'smallDefects'=>[...]], ... ]
    public array $stagedDefects = [];

    // Currently active large defect being configured in the modal
    public $modalSelectedLargeDefect = null;
    public $modalLargeQty = '';
    public array $modalSmallDefects = [];   // [['type' => '...', 'qty' => ''], ...]

    public $rules = [
        'newDefect' => 'required|string|max:255',
        'newQuan'   => 'required|numeric|min:1',
    ];
    public $messages = [
        'newDefect.required' => 'Please enter a defect type.',
        'newDefect.string'   => 'The defect type must be a valid text.',
        'newDefect.max'      => 'The defect type cannot exceed 255 characters.',
        'newQuan.required'   => 'Please enter a quantity.',
        'newQuan.numeric'    => 'The quantity must be a number.',
        'newQuan.min'        => 'The quantity must be at least 1.',
    ];

    public $listeners = [
        'FetchDefect'          => 'fetchDefect',
        'FetchDefectDashboard' => 'FetchDefectDashboard',
        'locked'               => 'locked',
        'ClearForm'            => 'ClearForm',
        'PPFFromOp'            => 'PPFFromOp',
    ];

    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm()
    {
        $this->defects      = [];
        $this->smallDefects = [];
        $this->TotalNg      = 0;
        $this->TotalSmallQuan = 0;
    }

    public function fetchDefect($data)
    {
        $this->defects = $data['defects'] ?? [];

        $collection = collect($data['smallDefects'] ?? [])
            ->mapWithKeys(function ($items, $large) {
                return [
                    $large => collect($items)->map(function ($item) use ($large) {
                        return [
                            'LargeDefect' => $large,
                            'type'        => $item['type'] ?? null,
                            'qty'         => $item['qty'] ?? '',
                        ];
                    })->toArray(),
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

        $group     = $this->smallDefects[$lastLarge] ?? [];
        $lastSmall = end($group);
        $this->newSmallDefect = $lastSmall['type'] ?? null;
        $this->newSmallQuan   = $lastSmall['qty']  ?? null;

        $this->TotalNg        = collect($this->defects)->sum('qty');
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum(fn($d) => (int)($d['qty'] ?? 0));
    }

    public function mount($systemname = null, $dispatchPrefix = null, $formId = null, $loadedDefects = [], $loadedSmallDefects = [])
    {
        $this->formId       = $formId;
        $this->Largedefects = ModelsDefects::select('LargeDefect')
            ->distinct()
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'ASC')
            ->get();
        $this->dispatchPrefix = $dispatchPrefix;
        $this->systemname     = $systemname;
        $this->SmallDefectsForModal = collect();
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
        $this->smallDefects   = $smallDefects ?? [];
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum(fn($d) => (int)($d['qty'] ?? 0));
    }

    // -------------------------------------------------------------------------
    // Unified modal helpers
    // -------------------------------------------------------------------------

    /**
     * Called when the user clicks a large defect card in the modal.
     * If the defect is already staged, loads it for editing.
     * If a different defect is active, auto-saves it first then switches.
     */
    public function selectLargeDefectInModal(string $largeDefect): void
    {
        // Auto-save current active entry before switching
        if ($this->modalSelectedLargeDefect !== null && $this->modalSelectedLargeDefect !== $largeDefect) {
            $this->stageCurrentEntry();
        }

        // Toggle off if same defect clicked again (and it wasn't just staged)
        if ($this->modalSelectedLargeDefect === $largeDefect) {
            $this->modalSelectedLargeDefect = null;
            $this->modalLargeQty            = '';
            $this->modalSmallDefects        = [];
            return;
        }

        $this->modalSelectedLargeDefect = $largeDefect;
        $this->modalLargeQty            = '';

        // Load small defects from DB
        $smalls = ModelsDefects::query()
            ->select('SmallDefect')
            ->distinct()
            ->whereNotNull('SmallDefect')
            ->where('LargeDefect', $largeDefect)
            ->orderBy('SmallDefect', 'asc')
            ->get();

        $this->modalSmallDefects = $smalls->map(fn($s) => [
            'type' => $s->SmallDefect,
            'qty'  => '',
        ])->values()->toArray();

        // Pre-fill from staged entry if it exists
        $stagedIdx = collect($this->stagedDefects)
            ->search(fn($e) => $e['type'] === $largeDefect);

        if ($stagedIdx !== false) {
            $staged = $this->stagedDefects[$stagedIdx];
            $this->modalLargeQty = $staged['qty'];
            foreach ($this->modalSmallDefects as &$ms) {
                $saved = collect($staged['smallDefects'])->firstWhere('type', $ms['type']);
                if ($saved) {
                    $ms['qty'] = $saved['qty'];
                }
            }
            return;
        }

        // Pre-fill from already-saved defects (re-edit flow)
        $existingLarge = collect($this->defects)->firstWhere('type', $largeDefect);
        if ($existingLarge) {
            $this->modalLargeQty = $existingLarge['qty'];
        }

        if (isset($this->smallDefects[$largeDefect])) {
            foreach ($this->modalSmallDefects as &$ms) {
                $saved = collect($this->smallDefects[$largeDefect])
                    ->firstWhere('type', $ms['type']);
                if ($saved) {
                    $ms['qty'] = $saved['qty'];
                }
            }
        }
    }

    /**
     * Stages the currently active large defect entry (validates qty first).
     * Returns false if validation fails.
     */

    //Stage all defect that exist in the table
    public function loadExistingDefectsToStage(): void
    {
        $this->stagedDefects = [];

        foreach ($this->defects as $defect) {

            $largeType = $defect['type'] ?? null;

            if (!$largeType) {
                continue;
            }

            $this->stagedDefects[] = [
                'type' => $largeType,
                'qty'  => (int) ($defect['qty'] ?? 0),
                'smallDefects' => collect($this->smallDefects[$largeType] ?? [])
                    ->map(function ($small) {
                        return [
                            'type' => $small['type'],
                            'qty'  => (int) ($small['qty'] ?? 0),
                        ];
                    })
                    ->values()
                    ->toArray(),
            ];
        }
    }
    private function stageCurrentEntry(): bool
    {
        if ($this->modalSelectedLargeDefect === null) {
            return true;
        }

        $largeQty = (int) $this->modalLargeQty;
        if ($largeQty < 1) {
            return false;
        }

        $smallsWithQty = collect($this->modalSmallDefects)
            ->filter(fn($s) => isset($s['qty']) && (int) $s['qty'] > 0)
            ->map(fn($s) => ['type' => $s['type'], 'qty' => (int) $s['qty']])
            ->values()
            ->toArray();

        $smallTotal = collect($smallsWithQty)->sum('qty');
        if ($smallTotal > $largeQty) {
            return false;
        }

        // Upsert in staged list
        $idx = collect($this->stagedDefects)
            ->search(fn($e) => $e['type'] === $this->modalSelectedLargeDefect);

        $entry = [
            'type'         => $this->modalSelectedLargeDefect,
            'qty'          => $largeQty,
            'smallDefects' => $smallsWithQty,
        ];

        if ($idx !== false) {
            $this->stagedDefects[$idx] = $entry;
        } else {
            $this->stagedDefects[] = $entry;
        }

        return true;
    }

    /**
     * Explicitly stage the active entry (called from blade "Add to List" button).
     */
    public function stageDefect(): void
    {
        $this->validate([
            'modalSelectedLargeDefect' => 'required|string',
            'modalLargeQty'            => 'required|numeric|min:1',
        ], [
            'modalSelectedLargeDefect.required' => 'Please select a defect type.',
            'modalLargeQty.required'            => 'Please enter a quantity.',
            'modalLargeQty.min'                 => 'Quantity must be at least 1.',
        ]);

        $largeQty = (int) $this->modalLargeQty;

        $smallsWithQty = collect($this->modalSmallDefects)
            ->filter(fn($s) => isset($s['qty']) && (int) $s['qty'] > 0)
            ->map(fn($s) => ['type' => $s['type'], 'qty' => (int) $s['qty']])
            ->values()
            ->toArray();

        $smallTotal = collect($smallsWithQty)->sum('qty');
        if ($smallTotal > $largeQty) {
            $this->addError('modalSmallDefects', "Total small qty ({$smallTotal}) cannot exceed large qty ({$largeQty}).");
            return;
        }

        $idx = collect($this->stagedDefects)
            ->search(fn($e) => $e['type'] === $this->modalSelectedLargeDefect);

        $entry = [
            'type'         => $this->modalSelectedLargeDefect,
            'qty'          => $largeQty,
            'smallDefects' => $smallsWithQty,
        ];

        if ($idx !== false) {
            $this->stagedDefects[$idx] = $entry;
        } else {
            $this->stagedDefects[] = $entry;
        }

        // Clear active selection so user can pick another
        $this->modalSelectedLargeDefect = null;
        $this->modalLargeQty            = '';
        $this->modalSmallDefects        = [];
        $this->resetErrorBag();
    }

    /**
     * Remove a defect from the staged list before confirming.
     */
    public function removeStagedDefect(string $type): void
    {
        $this->stagedDefects = collect($this->stagedDefects)
            ->reject(fn($e) => $e['type'] === $type)
            ->values()
            ->toArray();
    }

    /**
     * Recompute modalSmallDefects totals when large qty changes.
     */
    public function onModalLargeQtyChange(): void
    {
        // No server logic needed — blade handles the indicator.
    }

    /**
     * Reset all modal state (close / cancel).
     */
    public function resetModalState(): void
    {
        if (isset($this->stagedDefects)) {
            $this->dispatch('disregardStaged');
        }
    }

    #[On('confirmedDisregard')]
    public function resetStaged()
    {
        $this->modalSelectedLargeDefect = null;
        $this->modalLargeQty            = '';
        $this->modalSmallDefects        = [];
        $this->stagedDefects            = [];
        $this->resetErrorBag();
        $this->dispatch('close-add-defect');
    }

    /**
     * Unified save: commits ALL staged defects (and the currently active one if valid),
     * then dispatches a single update event.
     */
    public function addDefectFromModal(): void
    {
        // Auto-stage the active entry if it has a valid qty
        if ($this->modalSelectedLargeDefect !== null && (int) $this->modalLargeQty >= 1) {
            $this->stageCurrentEntry();
        }

        if (empty($this->stagedDefects)) {
            $this->validate([
                'modalSelectedLargeDefect' => 'required|string',
                'modalLargeQty'            => 'required|numeric|min:1',
            ], [
                'modalSelectedLargeDefect.required' => 'Please select and configure at least one defect.',
                'modalLargeQty.required'            => 'Please enter a quantity.',
                'modalLargeQty.min'                 => 'Quantity must be at least 1.',
            ]);
            return;
        }

        foreach ($this->stagedDefects as $entry) {
            $largeType = $entry['type'];
            $largeQty  = (int) $entry['qty'];

            // Verify exists in master list
            $existsInMaster = $this->Largedefects
                ->pluck('LargeDefect')
                ->map(fn($d) => strtolower(trim($d)))
                ->contains(strtolower(trim($largeType)));

            if (!$existsInMaster) {
                continue;
            }

            // Add or update large defect
            $existingIndex = collect($this->defects)
                ->search(fn($d) => strtolower(trim($d['type'])) === strtolower(trim($largeType)));

            if ($existingIndex !== false) {
                $this->defects[$existingIndex]['qty'] = $largeQty;
            } else {
                $this->defects[] = [
                    'type'     => trim($largeType),
                    'category' => 'large',
                    'qty'      => $largeQty,
                ];
            }

            // Merge small defects
            if (!isset($this->smallDefects[$largeType])) {
                $this->smallDefects[$largeType] = [];
            }

            foreach ($entry['smallDefects'] as $small) {
                $smallType = trim($small['type']);
                $smallQty  = (int) $small['qty'];
                $idx = collect($this->smallDefects[$largeType])
                    ->search(fn($s) => strtolower(trim($s['type'])) === strtolower($smallType));
                if ($idx !== false) {
                    $this->smallDefects[$largeType][$idx]['qty'] = $smallQty;
                } else {
                    $this->smallDefects[$largeType][] = [
                        'type' => $smallType,
                        'qty'  => $smallQty,
                    ];
                }
            }
        }

        $this->TotalNg        = collect($this->defects)->sum('qty');
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum(fn($d) => (int)($d['qty'] ?? 0));

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects'      => $this->defects,
            'smallDefects' => $this->smallDefects,
            'formId'       => $this->formId,
            'action'       => 'add',
        ]);

        $this->sendDefect();
        $this->dispatch('isDropdownUpdate', $this->formId);

        // Reset modal
        $this->resetStaged();
    }

    // -------------------------------------------------------------------------
    // Legacy helpers (kept for backward compatibility / edit flows)
    // -------------------------------------------------------------------------

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
        $category = $this->newCategory ?? 'large';

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

        $this->dispatch('isDropdownUpdate', $this->formId);
        $this->defects[] = [
            'type'     => trim($this->newDefect),
            'category' => $category,
            'qty'      => $this->newQuan,
        ];

        $this->TotalNg = collect($this->defects)->sum('qty');

        $this->newDefect   = '';
        $this->newQuan     = '';
        $this->newCategory = null;

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => $this->defects,
            'formId'  => $this->formId,
        ]);

        $this->sendDefect();
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
        $this->dispatch($this->dispatchPrefix . '.FetchNgDefectDropdown', [
            'formId'   => $this->formId,
            'defectNg' => $value,
        ]);
    }

    public function sendDispatch()
    {
        $this->dispatch('FromDefects');
        $this->dispatch('FromSmallDefects');
        $this->dispatch('sendNg', $this->TotalNg);
    }

    public function deleteDefectArray($type)
    {
        $this->defects = collect($this->defects)
            ->reject(fn($defects) => $defects['type'] === $type)
            ->values()
            ->toArray();

        if (isset($this->smallDefects[$type])) {
            unset($this->smallDefects[$type]);
        }

        $this->TotalNg        = collect($this->defects)->sum('qty');
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum('qty');

        $this->sendDefect();

        $this->defectData[] = [
            'type'   => $type,
            'action' => 'delete',
        ];

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => [
                [
                    'type' => $type,
                    'qty'  => 0,
                ],
            ],
            'action' => 'delete',
            'formId' => $this->formId,
        ]);

        $this->dispatch('NeedToDeleteDefect', ['formId' => $this->formId, 'type' => $type]);
        $this->sendDefect();
        $this->dispatch('isDropdownUpdate', $this->formId);
    }

    public function deleteDefectSmall($largeDefect, $type)
    {
        if (!isset($this->smallDefects[$largeDefect])) {
            return;
        }

        $this->smallDefects[$largeDefect] = collect($this->smallDefects[$largeDefect])
            ->reject(fn($defect) => trim($defect['type'] ?? '') === trim($type))
            ->values()
            ->toArray();

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects' => [
                $largeDefect => [
                    [
                        'type' => $type,
                        'qty'  => 0,
                    ],
                ],
            ],
            'formId' => $this->formId,
            'action' => 'delete',
        ]);

        $this->dispatch('NeedToDeleteSmall', ['formId' => $this->formId, 'type' => $type, 'largeDefect' => $largeDefect]);
        $this->dispatch('isDropdownUpdate', $this->formId);

        $this->newSmallDefect = '';
        $this->newSmallQuan   = '';
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
            ->pluck('SmallDefect')
            ->map(fn($d) => strtolower(trim($d)))
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
        $largeDefectQty    = collect($this->defects)->firstWhere('type', $this->selectedLargeDefect)['qty'] ?? '';

        if (((float)$currentSmallTotal + (float)$this->newSmallQuan) > (float)($largeDefectQty ?: 0)) {
            $this->addError('newSmallQuan', 'Total small defect quantity cannot exceed large defect quantity.');
            return;
        }

        $this->smallDefects[$this->selectedLargeDefect][] = [
            'type' => trim($this->newSmallDefect),
            'qty'  => (int) $this->newSmallQuan,
        ];

        $this->TotalSmallQuan += (int)$this->newSmallQuan;

        $this->smalldefectData[] = [
            'SelectedLargeDefect' => $this->selectedLargeDefect,
            'newSmallDefect'      => $this->newSmallDefect,
            'newSmallQuan'        => $this->newSmallQuan,
        ];

        $this->newSmallDefect = '';
        $this->newSmallQuan   = '';

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects'        => $this->smallDefects,
            'selectedLargeDefect' => $this->selectedLargeDefect,
            'formId'              => $this->formId,
        ]);
        $this->dispatch('isDropdownUpdate', $this->formId);
    }

    public function updateDefectArray()
    {
        foreach ($this->defects as &$defect) {
            if ($defect['type'] === $this->editingType) {
                $defect['qty'] = (float) $this->newQuan;
                break;
            }
        }

        if (isset($this->smallDefects[$this->editingType])) {
            $largeQty   = (float) $this->newQuan;
            $smallTotal = collect($this->smallDefects[$this->editingType])
                ->sum(fn($s) => (float) $s['qty']);

            if ($smallTotal > $largeQty) {
                $remaining = $largeQty;
                foreach ($this->smallDefects[$this->editingType] as &$small) {
                    if ($remaining <= 0) {
                        $small['qty'] = 0;
                        continue;
                    }
                    if ($small['qty'] > $remaining) {
                        $small['qty'] = $remaining;
                    }
                    $remaining -= $small['qty'];
                }
            }
        }

        $this->defectData[] = [
            'type' => trim($this->editingType),
            'qty'  => $this->newQuan,
        ];

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => [[
                'type' => trim($this->editingType),
                'qty'  => $this->newQuan,
            ]],
            'formId' => $this->formId,
            'action' => 'update',
        ]);

        $this->sendDefect();
        $this->dispatch('isDropdownUpdate', $this->formId);

        $this->editingType = null;
        $this->newQuan     = '';
    }

    public function startEdit($type)
    {
        $this->editingType = $type;
        $defects = collect($this->defects)->firstWhere('type', $type);
        if ($defects) {
            $this->newQuan = $defects['qty'];
        }
    }

    public function startEditSmall($large, $type)
    {
        $this->selectedLargeDefect = $large;
        $this->editingTypeSmall    = $type;

        $smalldefects = collect($this->smallDefects[$large])
            ->first(fn($s) => strtolower(trim($s['type'])) === strtolower(trim($type)));

        if ($smalldefects) {
            $this->newSmallQuan = $smalldefects['qty'];
        }
    }

    public function updateDefectSmallArray()
    {
        $large = $this->selectedLargeDefect;

        $largeQty = collect($this->defects)
            ->firstWhere('type', $large)['qty'] ?? 0;

        if (!isset($this->smallDefects[$large])) return;

        $otherTotal = collect($this->smallDefects[$large])
            ->reject(fn($d) => $d['type'] === $this->editingTypeSmall)
            ->sum(fn($d) => (float) $d['qty']);

        $remaining = $largeQty - $otherTotal;

        foreach ($this->smallDefects[$large] as &$defect) {
            if ($defect['type'] === $this->editingTypeSmall) {
                $defect['qty'] = min((float) $this->newSmallQuan, $remaining);
                break;
            }
        }

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects' => $this->smallDefects,
            'formId'       => $this->formId,
            'action'       => 'update',
        ]);
        $this->dispatch('isDropdownUpdate', $this->formId);

        $this->editingTypeSmall = null;
        $this->newSmallQuan     = '';
    }
}
