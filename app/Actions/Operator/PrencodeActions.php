<?php

namespace App\Actions\Operator;

use App\Models\Forms\Defect;
use App\Models\Forms\HF;
use App\Models\Forms\Rework;
use App\Models\Forms\SmallDefect;
use App\Models\Inspector\DefectInsp;
use App\Models\Inspector\PRInsp;
use App\Models\Inspector\ReworkInsp;
use App\Models\Inspector\SmallInsp;
use App\Services\General\SubmitPrencodeService;
use App\Services\Helpers\DraftService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrencodeActions
{
    public function __construct(
        protected SubmitPrencodeService $service,
        protected DraftService $draft,
    ) {}

    /**
     * @param  array<string, array>  $forms  Keyed by formId, as stored in the draft.
     */
    public function create(int $encoder, string $operatorID, array $forms, int $ppf): ?bool
    {
        return $this->save($encoder, $operatorID, $forms, $ppf);
    }

    public function update(int $encoder, string $operatorID, array $forms, int $ppf): ?bool
    {
        try {
            DB::transaction(function () use ($operatorID, $ppf, $forms) {
                $this->processDeletions($operatorID, $ppf, $forms);
            });
        } catch (\Throwable $e) {
            Log::error('PrencodeActions::processDeletions failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return null;
        }
        return $this->save($encoder, $operatorID, $forms, $ppf);
    }

    public function delete(string $operatorID, int $ppf): ?bool
    {
        try {
            DB::transaction(function () use ($ppf, $operatorID) {
                DefectInsp::where('inspectorId', $operatorID)->where('operation', 'VI')->where('ppfno', $ppf)->delete();
                ReworkInsp::where('inspectorId', $operatorID)->where('operation', 'VI')->where('ppfno', $ppf)->delete();
                SmallInsp::where('inspectorId', $operatorID)->where('operation', 'VI')->where('ppfno', $ppf)->delete();
                PRInsp::where('inspectorId', $operatorID)->where('operation', 'VI')->where('ppfno', $ppf)->delete();
                Defect::where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
                Rework::where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
                SmallDefect::where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
                HF::where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('PrencodeActions::delete failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return null;
        }
    }

    protected function save(int $encoder, string $operatorID, array $forms, int $ppf): ?bool
    {
        $totalInspection = 0;
        foreach ($forms as $formData) {
            $totalInspection += (int) ($formData['total_inspect'] ?? 0);
        }

        try {
            $this->service->submit([
                'ppfno'                   => $ppf,
                'updated_by'              => $encoder,
                'inspectorId'             => $operatorID,
                'total_inspect'           => $totalInspection,
                'form'                    => $forms,
                'needToDeleteForm'        => [],
                'needToDeleteDefect'      => [],
                'needToDeleteDefectSmall' => [],
                'isDropdownUpdate'        => [],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('PrencodeActions::save failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return null;
        }
    }

    protected function processDeletions(string $operatorID, int $ppf, array $forms)
    {
        $draft = $this->draft->load($operatorID);

        foreach ($draft['needToDeleteForm'] ?? [] as $formId => $formData) {
            $hfId = $formData['hf_id'] ?? null;

            if (!$hfId) continue;

            HF::where('hfid', $hfId)->where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
            Defect::where('hfid', $hfId)->where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
            Rework::where('hfid', $hfId)->where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
            SmallDefect::where('hfid', $hfId)->where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
        }

        foreach ($forms as $formId => $formData) {
            $hfId = $formData['hf_id'] ?? null;
            if (!$hfId) continue;

            $formDraft = $this->draft->load($formId);

            foreach ($formDraft['needToDeleteDefect'] ?? [] as $type) {
                Defect::where('hfid', $hfId)->where('defect', $type)
                    ->where('ppfno', $ppf)->where('inspId', $operatorID)->delete();

                SmallDefect::where('hfid', $hfId)->where('largeDefect', $type)
                    ->where('ppfno', $ppf)->where('inspId', $operatorID)->delete();
            }

            foreach ($formDraft['needToDeleteDefectSmall'] ?? [] as $small) {
                SmallDefect::where('hfid', $hfId)
                    ->where('largeDefect', $small['largeDefect'] ?? null)
                    ->where('smallDefect', $small['type'] ?? null)
                    ->where('inspId', $operatorID)
                    ->delete();
            }

            foreach ($formDraft['needToDeleteRework'] ?? [] as $rework) {
                Rework::where('hfid', $hfId)
                    ->where('rework_type', $rework['type'] ?? null)
                    ->where('inspId', $operatorID)
                    ->delete();
            }
        }
    }
}
