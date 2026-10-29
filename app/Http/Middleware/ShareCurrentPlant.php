<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ShareCurrentPlant
{
    public function handle(Request $request, Closure $next)
    {
        View::share('plant', session('plant'));

        return $next($request);
    }
}