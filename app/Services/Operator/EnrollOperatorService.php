<?php

namespace App\Services\Operator;

use App\Models\GL\EnrollOperator;
use App\Repositories\Operator\EnrollOperatorRepository;
use Illuminate\Support\Collection;

class EnrollOperatorService
{
    public function __construct(
        protected EnrollOperatorRepository $repository,
    ) {
    }

    /**
     * Whether an Operator ID is already enrolled for the given GL.
     */
    public function isEnrolledInGl(string $operatorID, string $glID): bool
    {
        return $this->repository->isEnrolledInGl($operatorID, $glID);
    }

    /**
     * Look up a worker's display name by their Operator ID.
     *
     * @return array{name: string, found: bool}
     */
    public function findWorkerName(string $operatorID): array
    {
        $worker = $this->repository->findWorkerByCode($operatorID);

        return [
            'name'  => $worker?->employeeName?->名前 ?? '',
            'found' => $worker !== null,
        ];
    }

    public function findByOperatorId(string $operatorID): ?EnrollOperator
    {
        return $this->repository->findByOperatorId($operatorID);
    }

    public function search(string $glID, string $search = ''): Collection
    {
        return $this->repository->listForGl($glID, $search);
    }

    public function countForGl(string $glID): int
    {
        return $this->repository->countForGl($glID);
    }

    public function enroll(string $operatorName, string $operatorID, string $glID): EnrollOperator
    {
        return $this->repository->create([
            'OperatorName' => $operatorName,
            'OperatorID'   => $operatorID,
            'GLID'         => $glID,
            'ProgramID'    => 'VI',
        ]);
    }

    public function remove(string|int $operatorID): void
    {
        $this->repository->deleteByOperatorId($operatorID);
    }
}