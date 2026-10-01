<?php

namespace App\Http\Middleware;

use App\Helpers\CommonHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    private const ROUTE_MODULES = [
        'roles.' => 'Roles',
        'categories.' => 'Categories',
        'tags.' => 'Tags',
        'allergies.' => 'Allergies',
        'cuisine-types.' => 'Cuisine Types',
        'customers.' => 'Customer Management',
        'chefs.' => 'Chefs',
        'chef.' => 'Chefs',
        'continuous_audits.' => 'Chefs',
        'kitchen_photos.' => 'Chefs',
        'kitchen.photo.' => 'Chefs',
        'food_dishes.' => 'Chefs',
        'orders.' => 'Orders',
        'coupons.' => 'Coupons',
        'homescreen.' => 'Home Screen',
        'pages.' => 'Pages',
        'admin.payouts.' => 'Payouts',
        'admin.notifications.' => 'Notifications',
        'admin.reports.' => 'Reports',
        'issues.' => 'Issues',
        'settings.' => 'Settings',
        'setting.' => 'Settings',
        'smtp.' => 'Settings',
        'users.' => 'Users',
        'food-items.' => 'Food Items',
        'preferences.' => 'Preferences',
        'mealtimes.' => 'MealTimes',
        'radius.' => 'Radius Setting',
        'restaurants.' => 'Restaurant Types',
    ];

    private const SHARED_ROUTES = [
        'admin.dashboard',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'password.change.form',
        'password.change',
    ];

    public function handle(Request $request, Closure $next, ?string $module = null, ?string $action = null): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if ((int) $user->is_admin === 1) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        if ($module === null && in_array($routeName, self::SHARED_ROUTES, true)) {
            return $next($request);
        }

        $module ??= $this->moduleForRoute($routeName);
        $action ??= $this->actionForRoute($routeName, $request->method());

        if ($module === null || !CommonHelper::getPermission($module, $action)) {
            abort(403, 'You do not have permission to access this feature.');
        }

        return $next($request);
    }

    private function moduleForRoute(string $routeName): ?string
    {
        foreach (self::ROUTE_MODULES as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                return $module;
            }
        }

        return null;
    }

    private function actionForRoute(string $routeName, string $method): string
    {
        if (str_starts_with($routeName, 'continuous_audits.') || str_starts_with($routeName, 'kitchen_photos.')) {
            return str_ends_with($routeName, '.destroy') ? 'delete' : 'edit';
        }

        $suffix = str($routeName)->afterLast('.')->toString();

        if (in_array($suffix, ['destroy', 'delete'], true)) {
            return 'delete';
        }

        if (in_array($suffix, ['create', 'store'], true)) {
            return 'create';
        }

        if (in_array($suffix, ['edit', 'update'], true) || !in_array($method, ['GET', 'HEAD'], true)) {
            return 'edit';
        }

        return 'list';
    }
}