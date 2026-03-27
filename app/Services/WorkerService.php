<?php

namespace App\Services;

use App\Repositories\WorkerRepository;
use Illuminate\Support\Carbon;

class WorkerService
{

    protected $workerRepo;

    public function __construct(WorkerRepository $workerRepository)
    {
        $this->workerRepo = $workerRepository;
    }

    public function validateWorker($formId, $forms, $field)
    {
        if (!isset($forms[$formId])) {
            return ['error' => 'Invalid form'];
        }

        $currentId = $forms[$formId][$field] ?? null;
        $currentDate = now()->format('Y-m-d');

        if (empty($currentId)) {
            return [
                'error' => ucfirst(str_replace('_', ' ', $field)) . ' cannot be empty'
            ];
        }

        $searchValue = strlen($currentId) === 2 ? ' ' . $currentId : $currentId;

        $worker = $this->workerRepo->checkExistWorkerVI($searchValue);

        if (!$worker) {
            return ['error' => 'This Operator does not exist'];
        }

        $name = $this->workerRepo->getWorkerName($worker->社員CD);

        foreach ($forms as $id => $form) {
            if ($id === $formId) continue;

            $otherDate = isset($form['created_at'])
                ? Carbon::parse($form['created_at'])->format('Y-m-d')
                : null;

            if (($form[$field] ?? null) === $currentId && $currentDate === $otherDate) {
                return ['error' => 'This Operator is already used in another form with the same date'];
            }
        }

        return [
            'success' => true,
            'worker_name' => $name?->名前
        ];
    }
}
