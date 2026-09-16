<?php
// app/Http/Middleware/CheckRegionModal.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CheckRegion
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('region')) {
            View::share('showRegionModal', true);
        } else {
            View::share('showRegionModal', false);
        }
        return $next($request);
    }
}
