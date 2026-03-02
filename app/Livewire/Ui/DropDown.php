<?php

namespace App\Livewire\Ui;

use Livewire\Component;
use Livewire\Attributes\On;

class DropDown extends Component
{
    public $forms = [];
    public $defects = [];
    public $currentFormId = null;

    public function addNew()
    {
        $uniqueId = uniqid();
        $this->forms[$uniqueId] = [
            'hf_id' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
        ];
    }
    public function updatedForms()
    {
        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms,
        ]);
    }

    //    #[On('defects-updated')]
    // public function updateDefectsFromChild($data = [])
    // {
    //     $this->currentFormId = $data['formId'];

    //     $defectData = $data['defects'] ?? [];
    //     $smallDefectData = $data['smallDefects'] ?? [];
    //     $reworkData = $data['reworksData'] ?? [];

    //     if(!$defectData && !$smallDefectData && !$reworkData) {
    //         return; // No data to merge, exit early
    //     }

    //     $normalized = [];
    //     foreach ($this->defects as $def) {
    //         $type = $def['type'] ?? $def['newDefect'] ??'';
    //         $qty = (float)$def['qty'] ?? $def['newQuan'] ?? 0;

    //         if($type === '') {
    //             continue; // Skip if type is empty
    //         }


    //         if (isset($normalized[strtolower($type)])) {
    //             $normalized[strtolower($type)]['qty'] += $qty;
    //         } else {
    //             $normalized[strtolower($type)] = [
    //                 'type' => $def['type'],
    //                 'qty'  => (int)$qty
    //             ];
    //         }
    //     }


    //     // Merge large defects
    //     if (isset($data['defects'])) {
    //         $this->forms[$this->currentFormId]['defects'] = array_merge(
    //             $this->forms[$this->currentFormId]['defects'] ?? [],
    //             $data['defects']
    //         );
    //     }

    //     // Merge small defects
    //     if (isset($data['smallDefects'])) {
    //         // Ensure small defects are grouped by LargeDefect
    //         $existingSmall = $this->forms[$this->currentFormId]['smallDefects'] ?? [];
    //         $newSmall = $data['smallDefects'] ?? [];

    //         $this->forms[$this->currentFormId]['smallDefects'] = array_merge_recursive($existingSmall, $newSmall);
    //     }

    //     // Merge reworks
    //     if(isset($data['reworksData'])) {
    //         $this->forms[$this->currentFormId]['rework'] = array_merge(
    //             $this->forms[$this->currentFormId]['rework'] ?? [],
    //             [$data['reworksData']] // make sure it's an array
    //         );
    //     }

    //     // Bubble up
    //     $this->dispatch('dropdown-updated', [
    //         'forms' => $this->forms
    //     ]);
    // }


    // #[On('defects-updated')]
    // public function updateDefectsFromChild($data = [])
    // {
    //     $formId = $data['formId'] ?? null;
    //     if (!$formId) return;

    //     if (isset($data['defects'])) {
    //         $this->forms[$formId]['defects'] = array_merge($this->forms[$formId]['defects'] ?? [], $data['defects']);
    //     }

    //     if (isset($data['smallDefects'])) {
    //         $this->forms[$formId]['smallDefects'] = $data['smallDefects'];
    //     }

    //     if (isset($data['reworksData'])) {
    //         $this->forms[$formId]['rework'] = $data['reworksData'];
    //     }

    //     $this->dispatch('dropdown-updated', [
    //         'forms' => $this->forms
    //     ]);

    //     dd($this->forms[$formId]['defects']);
    // }

    #[On('defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $formId = $data['formId'] ?? null;
        if (!$formId) return;

        $this->forms[$formId]['defects'] = array_merge(
            $this->forms[$formId]['defects'] ?? [],
            $data['defects'] ?? []
        );

       foreach ($data['smallDefects'] ?? [] as $large => $smalls) {
        if (!isset($this->forms[$formId]['smallDefects'][$large])) {
            $this->forms[$formId]['smallDefects'][$large] = [];
        }

        foreach ($smalls as $small) {
            $exists = collect($this->forms[$formId]['smallDefects'][$large])
                ->contains(fn($s) => strtolower(trim($s['type'] ?? '')) === strtolower(trim($small['type'] ?? '')));
            if (!$exists && !empty($small['type'])) {
                $this->forms[$formId]['smallDefects'][$large][] = $small;
            }
        }
    }

        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms
        ]);
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }
    public function remove($id)
    {
        unset($this->forms[$id]);
        $this->forms = array_values($this->forms);
    }
    public function render()
    {
        return view('livewire.ui.drop-down');
    }
}
