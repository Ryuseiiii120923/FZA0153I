<?php

namespace App\Repositories\Operator;

use App\Models\GL\EnrollOperator;
use App\Models\Worker;
use Illuminate\Support\Collection;

class EnrollOperatorRepository
{
    /**
     * Whether an Operator ID is already enrolled for the given GL.
     */
    public function isEnrolledInGl(string $operatorID, string $glID): bool
    {
        return EnrollOperator::where('OperatorID', $operatorID)
            ->where('GLID', $glID)
            ->exists();
    }

    /**
     * Whether an Operator ID is enrolled in any GL other than the given one.
     * Kept for parity with the (currently disabled) cross-GL duplicate check.
     */
    public function findEnrolledInOtherGl(string $operatorID, string $glID): ?EnrollOperator
    {
        return EnrollOperator::where('OperatorID', $operatorID)
            ->where('GLID', '!=', $glID)
            ->first();
    }

    /**
     * Look up a shop-floor worker (and their display name) by worker code.
     */
    public function findWorkerByCode(string $code): ?Worker
    {
        return Worker::with('employeeName')
            ->where('作業員CD', $code)
            ->first();
    }

    public function findByOperatorId(string $operatorID): ?EnrollOperator
    {
        return EnrollOperator::where('OperatorID', $operatorID)->first();
    }

    /**
     * Enrolled operators for a GL, optionally filtered by name/ID search.
     */
    public function listForGl(string $glID, string $search = ''): Collection
    {
        return EnrollOperator::select('OperatorID', 'OperatorName')
            ->where('GLID', $glID)
            ->when(
                $search !== '',
                fn ($query) => $query->where('OperatorName', 'like', "%{$search}%")
                    ->orWhere('OperatorID', 'like', "%{$search}%")
            )
            ->orderBy('OperatorName')
            ->get();
    }

    public function countForGl(string $glID): int
    {
        return EnrollOperator::where('GLID', $glID)->count();
    }

    public function create(array $attributes): EnrollOperator
    {
        return EnrollOperator::create($attributes);
    }

    public function deleteByOperatorId(string|int $operatorID): void
    {
        EnrollOperator::where('OperatorID', $operatorID)->delete();
    }
}