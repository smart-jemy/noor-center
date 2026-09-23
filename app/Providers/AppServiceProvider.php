<?php

namespace App\Providers;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ===== الصلاحيات المركزية (مطابقة للأصل) =====
        Gate::define('staff', fn ($user) => Permissions::staff($user));
        Gate::define('request-editor', fn ($user) => Permissions::requestEditor($user));
        Gate::define('admin-only', fn ($user) => Permissions::admin($user));
        Gate::define('dept-manager', fn ($user) => Permissions::departmentManager($user));
        Gate::define('admin-or-dept-manager', fn ($user) => Permissions::adminOrDeptManager($user));
        Gate::define('technician-only', fn ($user) => Permissions::technician($user));
        Gate::define('edit-request', fn ($user, ?ServiceRequest $request = null) => $request
            ? Permissions::canEditRequest($user->role, $request->status)
            : Permissions::requestEditor($user));

        // إعدادات الموقع + شارة الإشعارات متاحة في كل الصفحات
        View::composer('*', function ($view) {
            $view->with('siteSettings', Setting::current());

            $unreadBadge = 0;
            $navStats = ['unassignedPending' => 0, 'pendingReviews' => 0];

            if ($user = auth()->user()) {
                $unreadBadge = $user->unreadNotificationsCount();

                if ($user->isAdmin()) {
                    $navStats['unassignedPending'] = ServiceRequest::where('status', 'PENDING')->whereNull('assigned_technician_id')->count();
                    $navStats['pendingReviews'] = Review::where('status', 'PENDING')->count();
                }
            }

            $view->with('unreadBadge', $unreadBadge)->with('navStats', $navStats);
        });

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
