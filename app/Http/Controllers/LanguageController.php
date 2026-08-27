<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['ar', 'en'], true), 404);

        $request->session()->put('dashboard_locale', $locale);
        Cookie::queue('dashboard_locale', $locale, 60 * 24 * 365);
        App::setLocale($locale);

        return redirect()->back();
    }
}
