<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireOperatorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $expected = (string) config('vira.operator_token');
        $actual = (string) $request->bearerToken();

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            abort(401, 'A valid VIRA operator token is required.');
        }

        return $next($request);
    }
}
