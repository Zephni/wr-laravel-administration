<?php

namespace WebRegulate\LaravelAdministration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get current user
        $user = \WebRegulate\LaravelAdministration\Models\User::current();

        // Check if not logged in or not admin
        if ($user == null || $user->getPermission('admin') == false) {
            return redirect()->route('wrla.login');
        }

        return $next($request);
    }
}
