<?php

namespace App\Livewire\Templates;

use App\Models\Worker as Insp;
use App\Models\WorkerName as InspName;
use Livewire\Component;

class CheckInsp extends Component
{
    public array $inspectors = [1 => '', 2 => '', 3 => '', 4 => '', 5 => ''];
    public array $names      = [1 => '', 2 => '', 3 => '', 4 => '', 5 => ''];
    public array $errors     = [1 => null, 2 => null, 3 => null, 4 => null, 5 => null];
    public $duplicateIndex = [];
    public $locked = false;

    public $listeners = [
        'fetchInsp' => 'FetchInsp',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm'
    ];

    public function FetchInsp($data)
    {
        for($i = 0; $i < 5; $i++) {
            $this->inspectors[$i + 1] = $data['insp' . ($i + 1)] ?? null;
        }
        $this->CheckInsp();
    }

    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->{'inspectors' . $i} = null;
        }
    }



    public function CheckInsp()
    {
        $inspectors = [
            1 => $this->inspectors[1],
            2 => $this->inspectors[2],
            3 => $this->inspectors[3],
            4 => $this->inspectors[4],
            5 => $this->inspectors[5],
        ];

        $hasError = false;

        /** ----------------------------
         *  DUPLICATE CHECK
         *  ---------------------------- */
        $filtered = array_filter($inspectors);
        $duplicates = array_diff_assoc($filtered, array_unique($filtered));

        $this->duplicateIndex = array_keys($duplicates);

        if (!empty($this->duplicateIndex)) {
            $this->addError('duplicate', 'Duplicate inspector code(s) found');
            $hasError = true;
        } else {
            $this->resetErrorBag('duplicate');
        }

        /** ----------------------------
         *  RESET NAMES & ERRORS
         *  ---------------------------- */
        for ($i = 1; $i <= 5; $i++) {
            $this->{'name' . $i} = '';
            $this->{'error' . $i} = null;
        }

        /** ----------------------------
         *  INSPECTOR VALIDATION
         *  ---------------------------- */
        for ($i = 1; $i <= 5; $i++) {
            $insp = $inspectors[$i];

            if (empty($insp)) {
                continue;
            }

            $check = Insp::where('作業員CD', $insp)->first();

            if (!$check) {
                $this->{'error' . $i} = 'Inspector Not Found';
                $hasError = true;
                continue;
            }

            $checkname = InspName::where('社員CD', $check->社員CD)->first();
            $this->{'name' . $i} = $checkname->名前 ?? '';
        }

        /** ----------------------------
         *  EMIT RESULT
         *  ---------------------------- */
        $this->dispatch('inspectorsValidated', [
            'isValid' => !$hasError
        ]);

        if (!$hasError) {
            $this->dispatch('FromInsp', [
                'insp1' => $this->inspectors[1],
                'insp2' => $this->inspectors[2],
                'insp3' => $this->inspectors[3],
                'insp4' => $this->inspectors[4],
                'insp5' => $this->inspectors[5],
            ]);
        }
    }

    public function render()
    {
        return view('livewire.check-insp');
    }
}
