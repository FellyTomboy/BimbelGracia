<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => strtolower($role))
            ->contains($user->role->value ?? '');

        if (! $allowed) {
            abort(403);
        }

        return $next($request);
    }
}
