<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user && $user->is_active, 403, 'Konts nav aktīvs.');
        abort_unless(in_array($user->role, $roles, true), 403, 'Jums nav tiesību atvērt šo sadaļu.');
        return $next($request);
    }
}
