<?php

namespace App\Services\Helpers;

Class CalculateGoodQty
{
    public function calculateForm(array $form): array
    {
        $defectQty = collect($form['defects'] ?? [])->sum('qty');
        $reworkQty = collect($form['rework'] ?? [])->sum('quan');

        $totalNg = $defectQty + $reworkQty;

        $goodQty = ($form['total_inspect'] ?? 0) - $totalNg;

        return [
            'goodQty' => $goodQty,
            'defectNg' => $defectQty,
            'reworkNg' => $reworkQty,
        ];
    }

    public function calculatePR($totalInspect, $defects, $reworks){
        $defectQty = collect($defects)->sum('qty');
        $reworkQty = collect($reworks)->sum('quan');

        $totalNg = $defectQty + $reworkQty;

        return $totalInspect - $totalNg;
    }
}