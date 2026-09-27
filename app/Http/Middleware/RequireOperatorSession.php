<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireOperatorSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('vira_operator_authenticated', false)) {
            return redirect()->route('operator.login');
        }

        return $next($request);
    }
}
