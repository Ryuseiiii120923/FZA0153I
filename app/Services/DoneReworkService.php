<?php

namespace App\Services;

use App\Repositories\DoneReworkRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoneReworkService
{
    protected $doneReworkRepo;
    public $needdeleteForm = [], $needdeleteDefect = [], $needdeleteSmall = [];

    public function __construct(DoneReworkRepository $doneReworkRepo)
    {
        $this->doneReworkRepo = $doneReworkRepo;
    }

    public function editDonerework($data,$deleteSmall=[],$deleteDefect=[],$deleteForm=[])
    {
        $this->needdeleteSmall = $deleteSmall;
        $this->needdeleteDefect = $deleteDefect;
        $this->needdeleteForm = $deleteForm;
        $ppfno = $data['ppfno'];
        try {
            if (!empty($this->needdeleteForm)) {
                foreach ($this->needdeleteForm as $form) {
                        $this->doneReworkRepo->deleteForm($form['formId'], $ppfno);
                }
            }
            if (!empty($this->needdeleteDefect)) {
                foreach ($this->needdeleteDefect as $defect) {
                    $type = $defect['type'];
                    $formId = $defect['formId'];

                    if ($formId) {
                        $this->doneReworkRepo->deleteLargeDefect($ppfno, $type, $formId);
                    }
                }
            }

            if (!empty($this->needdeleteSmall)) {
                foreach ($this->needdeleteSmall as $small) {
                    $large = $small['largeDefect'];
                    $type = $small['type'];
                    $formId = $small['formId'];
                    $this->doneReworkRepo->deleteSmallDefect($ppfno, $large, $type, $formId);
                }
            }
            $this->saveDoneRework($data);
        } catch (\Exception $e) {
            Log::error('Edit PR Encode Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ]);
        }
    }

    public function saveDoneRework($data)
    {
        return DB::transaction(function () use ($data) {

            foreach ($data['forms'] as $form) {

                $hfId = $form['hf_id'];

                $this->doneReworkRepo->saveMainForm([
                    'hf_id' => $hfId,
                    'total_inspect' => $form['total_inspect'],
                    'encoder' => $data['encoder'],
                    'ppfno' => $data['ppfno'],
                    'goodQty' => $form['GoodQty'] ?? 0,
                    'inspect_REC' => $form['inspect_REC']
                ]);

                $this->doneReworkRepo->saveDefects(
                    $hfId,
                    $form['defects'] ?? [],
                    $data['ppfno'],
                    $data['encoder'],
                    $form['inspect_REC']
                );

                $this->doneReworkRepo->saveSmallDefects(
                    $hfId,
                    $form['smallDefects'] ?? [],
                    $data['ppfno'],
                    $data['encoder'],
                    $form['inspect_REC']
                );
            }

            // ✅ update flag once per PPF
            $this->doneReworkRepo->updateFlag($data['ppfno']);
        });
    }
}
