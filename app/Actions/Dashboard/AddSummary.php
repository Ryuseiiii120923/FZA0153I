<?php

namespace App\Actions\Dashboard;

use App\Actions\Notifications\SendViConfirmationEmailsAction;
use App\Models\Forms\HF;
use App\Models\HFRW\HFRWForms;
use App\Models\Rework;
use App\Repositories\Dashboard\SummaryRepository;
use App\Services\Helpers\DraftService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AddSummary
{
    public function __construct(
        private SummaryRepository $repo,
        private DraftService $draftService,
    ) {}

    public function AddtoDB(int $ppf, string $submitMethod, string $pro): array
    {
        if ($validationError = $this->validation($ppf)) {
            return $validationError;
        }

        $draft        = $this->draftService->load((string) $ppf);
        $defects      = $draft['defects']['defects'] ?? [];
        $smallDefects = $draft['defects']['smallDefects'] ?? [];
        $reworkRows   = $draft['rework']['reworkss'] ?? [];
        $computed     = $draft['computedFields'] ?? [];
        $footer       = $draft['footerDetails'] ?? [];
        $ppfLookUp    = $draft['ppfLookup'] ?? [];
        $fifoAuth     = $draft['fifo-auth'] ?? [];

        $inspectorId = $draft['inspectorIds']
            ?? collect($defects)->where('Process', '100% VI')->pluck('operatorid')->unique()->values()->all();
        $hfnoId = collect($reworkRows)->pluck('operatorid')->unique()->values()->all();

        $encoder = (int) ($header['encoder'] ?? Auth::user()->社員CD ?? 0);
        $now     = Carbon::now()->format('Y-m-d h:i:s A');

        $rework = $this->aggregate(
            $reworkRows,
            fn ($r) => ($r['hfno'] ?? '') . '|' . ($r['type'] ?? ''),
            'quan'
        );
        $defectTotals = $this->aggregate($defects, fn ($d) => $d['type'] ?? '', 'qty');
        $small = [];
        foreach ($smallDefects as $largeDefect => $smalls) {
            foreach ($this->aggregate($smalls, fn ($s) => $s['type'] ?? '', 'qty') as $s) {
                $small[] = ['PPFNo' => $ppf, 'LargeDefect' => $largeDefect, 'SmallDefect' => $s['type'] ?? '', 'Qty' => $s['qty'] ?? 0];
            }
        }

        $reworkOut = [];
        foreach ($rework as $r) {
            if (empty($r['type'])) continue;
            $reworkOut[] = [
                'PPFNo' => $ppf, 'HFNo' => $r['hfno'] ?? '', 'Defect' => $r['type'],
                'Quantity' => $r['quan'] ?? 0, 'TotalInspQty' => $r['totalinsp'] ?? 0,
            ];
        }

        $base = [
            'PPFNo'          => (float) $ppf,
            'PartNo'         => $ppfLookUp['partno'] ?? '',
            'Lotno'          => $ppfLookUp['lotno'] ?? '',
            'MatNo'          => $ppfLookUp['matno'] ?? '',
            'MDNo'           => $ppfLookUp['moldno'] ?? '',
            'PressNo'        => $ppfLookUp['pressno'] ?? '',
            'Shift'          => $ppfLookUp['shift'] ?? '',
            'Operator'       => $ppfLookUp['opt'] ?? '',
            'Total'          => $ppfLookUp['expct'] ?? 0,
            'Good'           => $computed['goodQty'] ?? 0,
            'Details'        => $footer['details'] ?? '',
            'InspectionDate' => !empty($footer['InspectDates'])
                ? Carbon::parse($footer['InspectDates'])->format('Y-m-d')
                : null,
            'DateEncode'     => $now,
            'Encoder'        => $encoder,
            'ExcessQty'      => $computed['excssQty'] ?? 0,
            'LackingQty'     => $computed['lackQty'] ?? 0,
            'ReworkQty'      => $computed['reworkQty'] ?? 0,
            'SampleQty'      => $computed['sampleQty'] ?? 0,
            'AutoMachine'    => $footer['auto'] ?? '',
        ];
        foreach (range(1, 5) as $i) {
            $base["HFNo$i"]   = $hfnoId[$i - 1] ?? '';
            $base["InspNo$i"] = $inspectorId[$i - 1] ?? '';
        }

        $defectRows = [];
        foreach ($defectTotals as $d) {
            $type = $d['type'] ?? null;
            $qty  = (float) ($d['qty'] ?? 0);
            if (!$type || $qty <= 0) continue;
            $defectRows[] = array_merge($base, ['Defect' => $type, 'Quantity' => $qty, 'MDate' => ' ']);
        }
        if (empty($defectRows)) {
            $defectRows[] = array_merge($base, ['Defect' => '', 'Quantity' => null, 'MDate' => '']);
        }

        try {
            DB::transaction(function () use ($ppf, $reworkOut, $computed, $footer, $encoder, $now, $defectRows, $small) {
                $this->repo->syncRework($ppf, $reworkOut);

                if ($this->repo->getViCheck($ppf)) {
                    $this->repo->updateViCheck($ppf, [
                        'QtyOut'         => $computed['goodQty']    ?? 0,
                        'NGQty'          => $computed['ngRatioQty'] ?? 0,
                        'Excess'         => $computed['excssQty']   ?? 0,
                        'Lacking'        => $computed['lackQty']    ?? 0,
                        'Rework'         => $computed['reworkQty']  ?? 0,
                        'Sample'         => $computed['sampleQty']  ?? 0,
                        'EncoderOut'     => $encoder,
                        'Plant'          => $footer['plant'] ?? '',
                        'InspectionDate' => $footer['InspectDates'] ?? '',
                        'Dateout'        => $now,
                    ]);
                }

                $this->repo->syncDefects($ppf, $defectRows);
                $this->repo->upsertInspectionGroup($ppf, $header['operation'] ?? 'VI', $footer['inspection_group'] ?? '');
                $this->repo->syncSmallDefects($ppf, $small);
                $this->repo->runDefectAddRoutine($ppf);
            });
        } catch (Throwable $e) {
            Log::error('AddtoDB Error', [
                'message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(),
                'ppf' => $ppf, 'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => 'An unexpected error occurred while saving. Please contact the administrator.'];
        }
        $this->draftService->clear((string) $ppf);

        session()->flash('success', strtolower($submitMethod) === 'edittodb'
            ? 'Data updated successfully!'
            : 'Data inserted successfully!');

        try {
            app(SendViConfirmationEmailsAction::class)->execute([
                'pro'             => $pro,
                'ppf'             => $ppf,
                'part'            => $ppfLookUp['partno'] ?? '',
                'lot'             => $ppfLookUp['lotno'] ?? '',
                'material'        => $ppfLookUp['matno'] ?? '',
                'hf'              => $ppfLookUp['expct'] ?? 0,
                'good'            => $computed['goodQty'] ?? 0,
                'ng'              => $computed['ngRatioQty'] ?? 0,
                'excess'          => $computed['excssQty'] ?? 0,
                'lacking'         => $computed['lackQty'] ?? 0,
                'rework'          => $computed['reworkQty'] ?? 0,
                'sample'          => $computed['sampleQty'] ?? 0,
                'confirmedByCode' => $ppfLookUp['encoder'] ?? null,
                'blnAdvance'      => $fifoAuth['fifoAdvance'] ?? false,
                'confirmUserCode' => $fifoAuth['fifoConfirmUserCode'] ?? null,
                'confirmRemark'   => $fifoAuth['fifoConfirmRemark'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('AddtoDB confirmation email failed', ['ppf' => $ppf, 'message' => $e->getMessage()]);
        }

        return ['success' => true];
    }

    private function aggregate(array $rows, callable $key, string $qtyField): array
    {
        $out = [];
        foreach ($rows as $row) {
            $k = $key($row);
            if (isset($out[$k])) {
                $out[$k][$qtyField] = (float) ($out[$k][$qtyField] ?? 0) + (float) ($row[$qtyField] ?? 0);
            } else {
                $out[$k] = $row;
            }
        }

        return array_values($out);
    }

    public function validation(int $ppf): ?array
    {
        $hasRework = HF::where('ppfno', $ppf)
            ->where('finishingProcedure', 'Hand Finishing')
            ->whereHas('reworks', fn ($q) => $q->where('proceedToWork', 0))
            ->exists();

        if ($hasRework) {
            if (!HFRWForms::where('ppfno', $ppf)->exists()) {
                return ['error' => 'There are pending reworks in HF for this PPF. Please resolve them before Saving.'];
            }
            if (!Rework::where('ppfno', $ppf)->where('flgDone', 1)->exists()) {
                return ['error' => 'This PPF rework is not yet marked as done. Please resolve them before Saving.'];
            }
            if (!HF::where('ppfno', $ppf)->where('forRework', 1)->exists()) {
                return ['error' => 'This PPF rework is not yet encode in Operator. Please resolve them before Saving.'];
            }
        }

        return null;
    }
}