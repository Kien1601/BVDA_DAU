<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Allow the request only if the user has one of the given roles.
     *
     * Usage: ->middleware('role:staff,admin')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
        {
            $user = $request->user();

            if (! $user || ! in_array($user->role->value, $roles, true)) {
                abort(403);
            }

            return $next($request);
        }
}
