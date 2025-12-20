<?php

namespace App\Livewire;

use App\Models\Worker as Insp;
use App\Models\WorkerName as InspName;
use Livewire\Component;

class CheckInsp extends Component
{
    public $insp1;
    public $insp2;
    public $insp3;
    public $insp4;
    public $insp5;
    public $name1;
    public $name2;
    public $name3;
    public $name4;
    public $name5;

    public $error1, $error2, $error3, $error4, $error5;
    public $duplicateIndex = [];
    public $locked = false;

    public $listeners = [
        'fetchInsp' => 'FetchInsp',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm'
    ];

    public function FetchInsp($data)
    {
        $this->insp1 = $data['insp1'] ?? null;
        $this->insp2 = $data['insp2'] ?? null;
        $this->insp3 = $data['insp3'] ?? null;
        $this->insp4 = $data['insp4'] ?? null;
        $this->insp5 = $data['insp5'] ?? null;
        $this->CheckInsp();
    }

    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm()
    {
        $this->insp1 = null;
        $this->insp2 = null;
        $this->insp3 = null;
        $this->insp4 = null;
        $this->insp5 = null;
    }
    // public function CheckInsp()
    // {
    //     $check1 = Insp::where('作業員CD', $this->insp1)->first();
    //     $check2 = Insp::where('作業員CD', $this->insp2)->first();
    //     $check3 = Insp::where('作業員CD', $this->insp3)->first();
    //     $check4 = Insp::where('作業員CD', $this->insp4)->first();
    //     $check5 = Insp::where('作業員CD', $this->insp5)->first();

    //     $inpectors = [
    //         1 => $this->insp1,
    //         2 => $this->insp2,
    //         3 => $this->insp3,
    //         4 => $this->insp4,
    //         5 => $this->insp5
    //     ];

    //     $filtered = array_filter($inpectors);
    //     $duplicates = array_diff_assoc($filtered, array_unique($filtered));

    //     $this->duplicateIndex = array_keys($duplicates);

    //     if (!empty($this->duplicateIndex)) {
    //         $this->addError('duplicate', 'Duplicate inspector code(s) found');
    //         return;
    //     } else {
    //         $this->resetErrorBag('duplicate');
    //     }
    //     $this->name1 = $this->name2 = $this->name3 = $this->name4 = $this->name5 = '';

    //     if (!empty($this->insp1)) {
    //         if ($check1) {
    //             $this->error1 = '';
    //             $checkname1 = InspName::where('社員CD', $check1->社員CD)->first();
    //             if ($checkname1) {
    //                 $this->name1 = $checkname1->名前 ?? '';
    //             }
    //         } else {
    //             $this->error1 = 'Inspector Not Found';
    //             $this->name1 = '';
    //         }
    //     } else {
    //         $this->name1 = '';
    //         $this->error1 = null;
    //     }

    //     if (!empty($this->insp2)) {
    //         if ($check2) {
    //             $this->error2 = '';
    //             $checkname2 = InspName::where('社員CD', $check2->社員CD)->first();
    //             $this->name2 = $checkname2->名前 ?? '';
    //         } else {
    //             $this->error2 = 'Inspector Not Found';
    //             $this->name2 = '';
    //         }
    //     } else {
    //         $this->name2 = '';
    //         $this->error2 = null;
    //     }

    //     if (!empty($this->insp3)) {
    //         if ($check3) {
    //             $this->error3 = '';
    //             $checkname3 = InspName::where('社員CD', $check3->社員CD)->first();
    //             $this->name3 = $checkname3->名前 ?? '';
    //         } else {
    //             $this->error3 = 'Inspector Not Found';
    //             $this->name3 = '';
    //         }
    //     } else {
    //         $this->name3 = '';
    //         $this->error3 = null;
    //     }

    //     if (!empty($this->insp4)) {
    //         if ($check4) {
    //             $this->error4 = '';
    //             $checkname4 = InspName::where('社員CD', $check4->社員CD)->first();
    //             $this->name4 = $checkname4->名前 ?? '';
    //         } else {
    //             $this->error4 = 'Inspector Not Found';
    //             $this->name4 = '';
    //         }
    //     } else {
    //         $this->name4 = '';
    //         $this->error4 = null;
    //     }

    //     if (!empty($this->insp5)) {
    //         if ($check5) {
    //             $this->error5 = '';
    //             $checkname5 = InspName::where('社員CD', $check5->社員CD)->first();
    //             $this->name5 = $checkname5->名前 ?? '';
    //         } else {
    //             $this->error5 = 'Inspector Not Found';
    //             $this->name5 = '';
    //         }
    //     } else {
    //         $this->name5 = '';
    //         $this->error5 = null;
    //     }
    //     $this->dispatch(
    //         'FromInsp',
    //         [
    //             'insp5' => $this->insp5,
    //             'insp1' => $this->insp1,
    //             'insp2' => $this->insp2,
    //             'insp3' => $this->insp3,
    //             'insp4' => $this->insp4
    //         ]
    //     );
    // }

    

    public function CheckInsp()
{
    $check1 = Insp::where('作業員CD', $this->insp1)->first();
    $check2 = Insp::where('作業員CD', $this->insp2)->first();
    $check3 = Insp::where('作業員CD', $this->insp3)->first();
    $check4 = Insp::where('作業員CD', $this->insp4)->first();
    $check5 = Insp::where('作業員CD', $this->insp5)->first();

    $inspectors = [
        1 => $this->insp1,
        2 => $this->insp2,
        3 => $this->insp3,
        4 => $this->insp4,
        5 => $this->insp5
    ];

    $filtered = array_filter($inspectors);
    $duplicates = array_diff_assoc($filtered, array_unique($filtered));

    $this->duplicateIndex = array_keys($duplicates);

    $hasError = false;

    if (!empty($this->duplicateIndex)) {
        $this->addError('duplicate', 'Duplicate inspector code(s) found');
        $hasError = true;
    } else {
        $this->resetErrorBag('duplicate');
    }

    $this->name1 = $this->name2 = $this->name3 = $this->name4 = $this->name5 = '';

    for ($i = 1; $i <= 5; $i++) {
        $insp = $this->{'insp' . $i};
        $check = ${'check' . $i} ?? null;
        if (!empty($insp)) {
            if ($check) {
                $this->{'error' . $i} = '';
                $checkname = InspName::where('社員CD', $check->社員CD)->first();
                $this->{'name' . $i} = $checkname->名前 ?? '';
            } else {
                $this->{'error' . $i} = 'Inspector Not Found';
                $this->{'name' . $i} = '';
                $hasError = true;
            }
        } else {
            $this->{'error' . $i} = null;
            $this->{'name' . $i} = '';
        }
    }

    // Emit validation result for the Add component
    $this->dispatch('inspectorsValidated', ['isValid' => !$hasError]);

    // Dispatch the inspector data
    $this->dispatch(
        'FromInsp',
        [
            'insp5' => $this->insp5,
            'insp1' => $this->insp1,
            'insp2' => $this->insp2,
            'insp3' => $this->insp3,
            'insp4' => $this->insp4
        ]
    );
}

    public function render()
    {
        return view('livewire.check-insp');
    }
}
