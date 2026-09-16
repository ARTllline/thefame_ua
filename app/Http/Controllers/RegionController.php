<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;


class RegionController extends Controller
{

    private function changeLocale($locale)
    {
        $availableLocales = ['ru', 'uk', 'en'];

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale');
        }

        session(['locale' => $locale]);

        App::setLocale($locale);

    }

}
