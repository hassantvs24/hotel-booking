<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin API is for the people who run the platform (admin) and the people who run a property (vendor).
 * A guest account, which anyone can create from the sign-up page, is neither: it must not read or change anything here.
 * (This is the same rule the admin login already applies; now every admin route enforces it too.)
 */
class EnsureAdminPanelUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && ($user->is_admin || $user->is_merchant), 403, 'This area is for the platform admin and property owners only.');

        return $next($request);
    }
}
