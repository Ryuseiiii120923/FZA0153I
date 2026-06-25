<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HandlesFormItems
{
    protected function updateItemList(
        array &$target,
        array $items,
        string $keyField = 'type',
        string $qtyField = 'qty',
        string $action = 'add'
    ) {
        // 1️⃣ Normalize existing target list (merge duplicates)
        $normalized = [];

        foreach ($target as $t) {
            $keyValue = strtolower(trim($t[$keyField] ?? ''));
            $qty = (float)($t[$qtyField] ?? 0);

            if ($keyValue === '') continue;

            if (isset($normalized[$keyValue])) {
                $normalized[$keyValue][$qtyField] += $qty;
            } else {
                $normalized[$keyValue] = [
                    $keyField => $t[$keyField],
                    $qtyField => $qty
                ];
            }
        }

        // 2️⃣ Apply changes from new items
        foreach ($items as $item) {
            $keyValue = strtolower(trim($item[$keyField] ?? ''));
            $qty = (float)($item[$qtyField] ?? 0);

            if ($keyValue === '') continue;

            switch ($action) {

                case 'delete':
                    unset($normalized[$keyValue]);
                    break;

                case 'update':
                    if (isset($normalized[$keyValue])) {
                        $normalized[$keyValue][$qtyField] = $qty;
                    }
                    break;

                case 'add':
                default:
                    if (isset($normalized[$keyValue])) {
                        $normalized[$keyValue][$qtyField] += $qty;
                    } else {
                        $normalized[$keyValue] = [
                            $keyField => $item[$keyField],
                            $qtyField => $qty
                        ];
                    }
                    break;
            }
        }

        // 3️⃣ Reindex and return clean array
        $target = array_values($normalized);
    }

    protected function updateSmallDefects(array &$target, array $smallDefects, string $action = 'add')
    {
        foreach ($smallDefects as $large => $smalls) {
            if (!isset($target[$large])) $target[$large] = [];
            $this->updateItemList($target[$large], $smalls, 'type', $action);
        }
    }

    protected function updateReworks(array &$target, array $reworks, string $action = 'add')
    {
        foreach ($reworks as $r) {
            $keyValue = ($r['hfno'] ?? '') . '|' . ($r['type'] ?? '');
            switch ($action) {
                case 'add':
                    $exists = collect($target)->contains(fn($t) => ($t['hfno'] ?? '') . '|' . ($t['type'] ?? '') === $keyValue);
                    if (!$exists) $target[] = $r;
                    break;

                case 'update':
                    foreach ($target as $i => $t) {
                        if (($t['hfno'] ?? '') . '|' . ($t['type'] ?? '') === $keyValue) {
                            $target[$i] = array_merge($t, $r);
                            break;
                        }
                    }
                    break;

                case 'delete':
                    $target = array_values(array_filter($target, fn($t) => ($t['hfno'] ?? '') . '|' . ($t['type'] ?? '') !== $keyValue));
                    break;
            }
        }
    }
}
