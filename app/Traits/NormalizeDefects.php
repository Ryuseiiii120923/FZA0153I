<?php

namespace App\Traits;

trait NormalizeDefects
{
    public $smalldefects = [];
    public $defects = [];
    public function Defects($payload = [])
    {
        if (!$payload) return;

        $defectData = $payload['defectData'] ?? $payload;

        $newDefect = trim($defectData['newDefect'] ?? '');
        $newQuan   = (float)($defectData['newQuan'] ?? '');
        $action    = $defectData['action'] ?? 'add';

        if (!$newDefect) return;

        $normalized = [];
        foreach ($this->defects as $def) {
            $type = $def['type'] ?? $def['newDefect'] ?? '';
            $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? '');

            if ($type === '') continue;

            if (isset($normalized[strtolower($type)])) {
                $normalized[strtolower($type)]['qty'] += $qty;
            } else {
                $normalized[strtolower($type)] = [
                    'type' => $type,
                    'qty'  => (int) $qty
                ];
            }
        }


        $key = strtolower($newDefect);

        if ($action === 'delete') {
            unset($normalized[$key]);
            $this->defects = array_values($normalized);
            return;
        }


        if ($action === 'update') {

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] = $newQuan;
            }
        } else {

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $newQuan;
            } else {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }

        $this->defects = array_values($normalized);
    }
}