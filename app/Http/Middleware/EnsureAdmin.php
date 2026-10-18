<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-admin only. Use `admin.only` for a whole area (users and roles) and `admin.write` for shared lists that
 * property owners may read but only the admin may change (countries, categories, review settings...).
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next, string $mode = 'all'): Response
    {
        $readOnly = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($mode === 'write' && $readOnly) {
            return $next($request);
        }

        abort_unless($request->user()?->is_admin, 403, 'Only the platform admin can do this.');

        return $next($request);
    }
}
