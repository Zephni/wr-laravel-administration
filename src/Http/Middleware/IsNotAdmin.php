<?php

namespace WebRegulate\LaravelAdministration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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
        $userData = WRLAHelper::getCurrentUserData();

        // If logged in and admin, redirect to dashboard
        if ($userData != null && $userData->getPermission('admin') == true) {
            return redirect()->route('wrla.dashboard');
        }

        return $next($request);
    }
}
