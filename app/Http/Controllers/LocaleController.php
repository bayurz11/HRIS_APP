<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $supportedLocales = array_keys(config('haris.locales', []));

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', $supportedLocales)],
        ]);

        $request->session()->put('locale', $validated['locale']);

        return back();
    }
}
