<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $shouldEnforce = (bool) config('app.enforce_roles', false);

        if (!$shouldEnforce || empty($roles)) {
            return $next($request);
        }

        $currentRole = strtolower((string) $request->header('X-User-Role', ''));

        if ($currentRole !== '' && in_array($currentRole, $roles, true)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Role not allowed for this operation.');
    }
}
