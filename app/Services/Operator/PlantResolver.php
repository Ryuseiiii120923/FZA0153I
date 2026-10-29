<?php

namespace App\Services\Operator;

use App\Models\EmpTrace;

class PlantResolver
{
    public function forUserId(int|string $userId): ?int
    {
        $trace = EmpTrace::whereRaw(
            "RIGHT(CAST(emp_Id AS VARCHAR(20)), 4) = ?",
            [$userId]
        )->first();

        if (!$trace) {
            return null;
        }

        return match ((string) $trace->teamId) {
            '4'     => 1,
            '7'     => 2,
            default => null,
        };
    }
}