<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OperatorAuthController extends Controller
{
    public function create(): View
    {
        return view('operator.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);
        $expected = (string) config('vira.operator_token');

        if ($expected === '' || ! hash_equals($expected, $data['token'])) {
            return back()->withErrors(['token' => 'The operator token is invalid.']);
        }

        $request->session()->regenerate();
        $request->session()->put('vira_operator_authenticated', true);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
