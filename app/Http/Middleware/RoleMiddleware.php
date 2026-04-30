<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware Role-Based Access Control
 * Contoh: RoleMiddleware:admin,trainer
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->current_user ?? $request->user();

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        return $next($request);
    }
}
