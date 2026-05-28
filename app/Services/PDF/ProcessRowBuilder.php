<?php

namespace App\Services\PDF;

use App\Models\HF\Defect;
use App\Models\HF\Rework;
use App\Models\HFRW\HFRWDefect;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Converts raw HF / HFRW Eloquent records into the normalised row
 * array shape consumed by the PDF Blade view.
 *
 * Each returned row has the same keys regardless of source, making the
 * view completely source-agnostic.
 */
class ProcessRowBuilder
{
    /**
     * @param  Collection $hfForms   HF::get() result
     * @param  Collection $hfrwForms HFRWForms::get() result
     * @param  string     $ppf       Flow / PPF number
     * @param  string     $nqrCriteria  Pre-fetched NQR criteria value
     * @return array<int, array>
     */
    public function build(
        Collection $hfForms,
        Collection $hfrwForms,
        string $ppf,
        string $nqrCriteria,
    ): array {
        $allForms = $this->mergeSortedForms($hfForms, $hfrwForms);

        $rows = [];
        foreach ($allForms as $form) {
            $isHfrw = $form->_source === 'HFRW';
            $rows[] = $this->buildRow($form, $ppf, $nqrCriteria, $isHfrw);
        }

        return $rows;
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function mergeSortedForms(Collection $hfForms, Collection $hfrwForms): Collection
    {
        $normalize = fn($record, string $source) => (object) array_merge(
            $record->toArray(),
            [
                '_source'    => $source,
                'created_at' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
            ]
        );

        return collect()
            ->merge($hfForms->map(fn($r)   => $normalize($r, 'VI')))
            ->merge($hfrwForms->map(fn($r) => $normalize($r, 'HFRW')))
            ->sortBy('created_at')
            ->values();
    }

    private function buildRow(object $form, string $ppf, string $nqrCriteria, bool $isHfrw): array
    {
        $defects = $this->resolveDefects($form, $ppf, $isHfrw);
        $reworks = $this->resolveReworks($form, $ppf, $isHfrw);
        $date    = Carbon::parse($form->created_at);

        return [
            'hf_id'               => $form->hf_id,
            'updated_by'          => $form->updated_by,
            'mm'                  => $date->format('m'),
            'dd'                  => $date->format('d'),
            'shift'               => '1',
            'total_quantity'      => $form->total_inspect,
            'process'             => $form->Process ?? '',
            'defects'             => $defects->toArray(),
            'reworks'             => $reworks->values()->toArray(),
            'total_good_qty'      => $form->GoodQty,
            'total_ng_qty'        => $form->TotalNg,
            'ng_percent'          => $this->calcNgPercent($form->TotalNg, $form->GoodQty),
            'nqr_criteria'        => $nqrCriteria,
            'nqr_judgement'       => $isHfrw ? '' : 'O',
            'handfinisher_no'     => $form->hf_id,
            'visual_inspector_no' => $form->updated_by,
            'remarks'             => $form->remarks ?? '',
            'operation'           => $form->Operation ?? ($isHfrw ? 'VI' : ''),
            'source'              => $form->_source,
        ];
    }

    private function resolveDefects(object $form, string $ppf, bool $isHfrw): Collection
    {
        /** @var class-string<Defect|HFRWDefect> $model */
        $model = $isHfrw ? HFRWDefect::class : Defect::class;

        return $model::with(['children' => fn($q) => $q
                ->where('hf_id', $form->hf_id)
                ->where('updated_by', $form->updated_by),
            ])
            ->where('hf_id', $form->hf_id)
            ->where('updated_by', $form->updated_by)
            ->where('ppfno', $ppf)
            ->get()
            ->flatMap(function ($defect) use ($form) {
                // Always emit a sentinel large-category entry (small_category = null)
                // so the large-column cell can be populated even with no children.
                $sentinel = [[
                    'hf_id'          => $form->hf_id,
                    'updated_by'     => $form->updated_by,
                    'large_category' => $defect->defect,
                    'small_category' => null,
                    'small_qty'      => 0,
                    'large_qty'      => (int) $defect->qty,
                ]];

                $children = $defect->children
                    ->map(fn($child) => [
                        'hf_id'          => $form->hf_id,
                        'updated_by'     => $form->updated_by,
                        'large_category' => $defect->defect,
                        'small_category' => $child->small_defect,
                        'small_qty'      => (int) $child->qty,
                        'large_qty'      => (int) $defect->qty,
                    ])
                    ->toArray();

                return array_merge($sentinel, $children);
            })
            ->values();
    }

    private function resolveReworks(object $form, string $ppf, bool $isHfrw): Collection
    {
        if ($isHfrw) {
            return collect();
        }

        return Rework::where('hf_id', $form->hf_id)
            ->where('updated_by', $form->updated_by)
            ->where('ppfno', $ppf)
            ->get()
            ->map(fn($rework) => [
                'type' => $rework->rework_type,
                'qty'  => (int) $rework->qty,
            ]);
    }

    private function calcNgPercent(int|float $totalNg, int|float $goodQty): string
    {
        $denominator = $totalNg + $goodQty;

        return $denominator > 0
            ? number_format(($totalNg / $denominator) * 100, 2)
            : '0.00';
    }
}
