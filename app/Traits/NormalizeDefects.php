<?php

namespace App\Traits;

trait NormalizeDefects
{
    public $defects = [];

    public function Defects($payload = [])
    {
        if (!$payload) return;

        // Shape 1: ['defects' => [...full array...], 'action' => '...']
        // Sent by Defects.php on add/update/delete/sync
        if (isset($payload['defects'])) {
            $normalized = [];
            foreach ($payload['defects'] as $def) {
                $type = $def['type'] ?? $def['newDefect'] ?? '';
                $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? 0);
                if ($type === '') continue;
                $key = strtolower($type);
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] += $qty;
                } else {
                    $normalized[$key] = ['type' => $type, 'qty' => $qty];
                }
            }
            $this->defects = array_values($normalized);
            return;
        }

        // Shape 2: ['defectData' => [...full array...]]
        // Sent by Gldashboard::LoadDefectsGL via $result['payload']
        if (isset($payload['defectData']) && is_array($payload['defectData'])) {
            $items = $payload['defectData'];
            // If it's a list of defects (numeric keys), bulk-replace
            if (isset($items[0]) || empty($items)) {
                $normalized = [];
                foreach ($items as $def) {
                    $type = $def['type'] ?? $def['newDefect'] ?? '';
                    $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? 0);
                    if ($type === '') continue;
                    $key = strtolower($type);
                    if (isset($normalized[$key])) {
                        $normalized[$key]['qty'] += $qty;
                    } else {
                        $normalized[$key] = ['type' => $type, 'qty' => $qty];
                    }
                }
                $this->defects = array_values($normalized);
                return;
            }

            // If it's a single-defect operation: ['newDefect' => '...', 'newQuan' => ...]
            $defectData = $items;
            $newDefect  = trim($defectData['newDefect'] ?? '');
            $newQuan    = (float)($defectData['newQuan'] ?? '');
            $action     = $defectData['action'] ?? 'add';
            if (!$newDefect) return;

            $normalized = [];
            foreach ($this->defects as $def) {
                $type = $def['type'] ?? $def['newDefect'] ?? '';
                $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? '');
                if ($type === '') continue;
                $key = strtolower($type);
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] += $qty;
                } else {
                    $normalized[$key] = ['type' => $type, 'qty' => (int) $qty];
                }
            }

            $key = strtolower($newDefect);

            if ($action === 'delete') {
                unset($normalized[$key]);
            } elseif ($action === 'update') {
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] = $newQuan;
                }
            } else {
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] += $newQuan;
                } else {
                    $normalized[$key] = ['type' => $newDefect, 'qty' => $newQuan];
                }
            }

            $this->defects = array_values($normalized);
        }
    }
}