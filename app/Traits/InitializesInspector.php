<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\WorkerName;
use Illuminate\Support\Facades\Auth as UserAuth;

trait InitializesInspector
{
    public $encoder, $username;
    
    public function initializeInspector()
    {

        $userencoder = UserAuth::user()->社員CD;

        $this->encoder = (int) $userencoder;

        $userName = WorkerName::select('名前')
            ->where('社員CD', $this->encoder)
            ->first();

        $this->username = $userName->名前 ?? '';
    }
}