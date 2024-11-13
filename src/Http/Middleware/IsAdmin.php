<?php

namespace WebRegulate\LaravelAdministration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
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
        // Get current user
        $user = \WebRegulate\LaravelAdministration\Models\User::current();

        // Check if not logged in or not admin
        if ($user == null || $user->getPermission('admin') == false) {
            return redirect()->route('wrla.login')->with(
                'error',
                'You do not have permission to access this page.'
            );
        }

        // Check that current route is both shown and enabled, otherwise redirect to the dashboard with error
        $isRouteAllowed = WRLAHelper::isCurrentRouteAllowed();
        if($isRouteAllowed !== true) {
            $message = is_string($isRouteAllowed)
                ? "Cannot access requested route: $isRouteAllowed"
                : 'The current route is not enabled or does not exist.';
                
            return redirect()->route('wrla.dashboard')->with('error', $message);
        }

        // Handle WRLASettings
        if(class_exists('\App\WRLA\WRLASettings')) {
            // Set navigation items (if App\WRLA\WRLASettings exists)
            NavigationItem::$navigationItems = \App\WRLA\WRLASettings::buildNavigation() ?? [];
        }

        return $next($request);
    }
}
