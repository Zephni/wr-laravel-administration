<?php

namespace WebRegulate\LaravelAdministration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;
use WebRegulate\LaravelAdministration\Classes\NavigationItems\NavigationItem;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get current user and check if they are an admin
        $wrlaUserData = Auth::user()?->wrlaUserData;

        // Check if not logged in or not admin
        if ($wrlaUserData == null || ($wrlaUserData?->getPermission('admin') ?? false) !== true) {
            return redirect()->route('wrla.login')->with(
                'error',
                'You do not have permission to access this page.'
            );
        }

        // Load navigation items into static array
        WRLAHelper::loadNavigationItems();

        // Check that current route is both shown and enabled, otherwise redirect to the dashboard with error
        $isRouteAllowed = WRLAHelper::isCurrentRouteAllowed();

        if($isRouteAllowed !== true) {
            $message = is_string($isRouteAllowed)
                ? "Cannot access requested route: $isRouteAllowed"
                : 'The current route is not enabled or does not exist.';

            return redirect()->route('wrla.dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
