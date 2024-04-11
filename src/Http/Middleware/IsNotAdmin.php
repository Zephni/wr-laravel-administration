<?php

namespace WebRegulate\LaravelAdministration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsNotAdmin
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

        // If logged in and admin, redirect to dashboard
        if ($user != null && $user->getPermission('admin') == true) {
            return redirect()->route('wrla.dashboard');
        }

        return $next($request);
    }
}
