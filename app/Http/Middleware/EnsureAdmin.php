<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
