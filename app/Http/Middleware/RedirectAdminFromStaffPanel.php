<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectAdminFromStaffPanel
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role?->name === 'admin') {
            return redirect('/admin');
        }

        return $next($request);
    }
}
