<?php

namespace App\Repositories;

use App\Models\Worker;
use Illuminate\Support\Facades\Auth;

class UserRepository
{
    public function getUserData()
    {
        $userencoder = Auth::user()->社員CD;
        $inspectorID = Worker::where('社員CD', $userencoder)->value('作業員CD');

        return [
            'encoder' => (int) $userencoder,
            'inspectorID' => $inspectorID
        ];
    }
}
