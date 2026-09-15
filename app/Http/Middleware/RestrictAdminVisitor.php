<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictAdminVisitor
{
    /**
     * Runs after the `admin` gate, which already let admin visitors through.
     * A visitor may browse every admin page except user records, and may
     * never submit a write request; both cases abort with a message
     * explaining the restriction rather than the generic "Forbidden".
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user?->isAdminVisitor()) {
            return $next($request);
        }

        if ($request->is('admin/users*')) {
            abort(403, 'Admin visitors cannot view user information.');
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403, 'Admin visitors have read-only access and cannot make changes.');
        }

        return $next($request);
    }
}
