<?php

namespace App\Traits;

trait NormalizeSmallDefects
{
    public $smalldefects = [];
    
    public function SmallDefects(array $data): void
    {
        $large  = $data['SelectedLargeDefect'];
        $type   = $data['type']   ?? $data['newSmallDefect'];
        $qty    = $data['qty']    ?? $data['newSmallQuan'];
        $action = $data['action'] ?? 'add';

        $this->smalldefects[$large] ??= [];

        // Build normalized map
        $map = collect($this->smalldefects[$large])
            ->keyBy(fn($s) => strtolower($s['type'] ?? ''))
            ->toArray();

        $key = strtolower($type);

        if ($action === 'delete') {
            unset($map[$key]);

        } elseif ($action === 'update') {
            if (isset($map[$key])) {
                $map[$key]['qty'] = $qty;
            }

        } else {
            // 'add' or any other action
            if (isset($map[$key])) {
                $map[$key]['qty'] += $qty;
            } else {
                $map[$key] = ['type' => $type, 'qty' => $qty];
            }
        }

        $this->smalldefects[$large] = array_values($map);
    }
}