<?php

namespace App\Services\PDF;

use App\Models\HF\Defect;
use App\Models\HFRW\HFRWDefect;
use App\Models\HF\Rework;
use Illuminate\Support\Collection;

/**
 * Builds the column-definition collections that drive both the table
 * header and the per-row cell lookups in the PDF.
 *
 * Extracted from PdfController so it can be unit-tested and reused.
 */
class DefectColumnService
{
    /**
     * Returns a grouped collection keyed by large-category name.
     * Each value is a Collection of column descriptors:
     *   ['large_category' => string, 'small_category' => string|null]
     *
     * A placeholder entry (small_category = null) is inserted for large
     * categories that have no children, keeping header/body column counts
     * in sync.
     *
     * @return Collection<string, Collection<array{large_category:string, small_category:string|null}>>
     */
    public function buildGroupedDefects(string $ppf): Collection
    {
        $hfDefects   = Defect::with('children')->where('ppfno', $ppf)->get();
        $hfrwDefects = HFRWDefect::with('children')->where('ppfno', $ppf)->get();

        return $hfDefects
            ->merge($hfrwDefects)
            ->groupBy('defect')
            ->map(function (Collection $items, string $largeCategory): Collection {
                $smallDefects = $items
                    ->flatMap(fn($defect) => $defect->children->map(fn($child) => [
                        'large_category' => $largeCategory,
                        'small_category' => $child->small_defect,
                    ]))
                    ->unique('small_category')
                    ->values();

                if ($smallDefects->isEmpty()) {
                    return collect([[
                        'large_category' => $largeCategory,
                        'small_category' => null,
                    ]]);
                }

                return $smallDefects;
            });
    }

    /**
     * Returns a flat collection of unique rework-type descriptors:
     *   ['type' => string]
     *
     * @return Collection<array{type:string}>
     */
    public function buildGroupedReworks(string $ppf): Collection
    {
        return Rework::where('ppfno', $ppf)
            ->get()
            ->map(fn($rework) => ['type' => $rework->rework_type])
            ->unique('type')
            ->values();
    }
}
