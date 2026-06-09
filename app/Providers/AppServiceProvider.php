<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Helpers\NotificationHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share cart count and current user with all views
        View::composer(['layouts.ecommerce-header', 'layouts.ecommerce', 'layouts.topbar', 'layouts.app'], function ($view) {
            $cartCount = 0;
            $currentUser = null;
            $isAdmin = false;
            $isCustomer = false;
            $hasInventoryAccess = false;
            
            // Force check authentication state without cache
            try {
                // Check if customer guard is authenticated FIRST (priority untuk halaman shopping)
                if (Auth::guard('customer')->check()) {
                    $customerUser = Auth::guard('customer')->user();
                    if ($customerUser) {
                        $currentUser = $customerUser;
                        $isCustomer = true;
                        // Calculate cart count for customers
                        try {
                            $cartCount = Cart::where('user_id', $customerUser->customer_id)->sum('quantity') ?? 0;
                        } catch (\Exception $e) {
                            $cartCount = 0;
                        }
                    }
                }
                // Check if web guard is authenticated
                elseif (Auth::guard('web')->check()) {
                    $webUser = Auth::guard('web')->user();
                    if ($webUser) {
                        $currentUser = $webUser;
                        $isAdmin = isset($webUser->role) && in_array($webUser->role, ['admin', 'superadmin']);
                        $userRoleName = strtolower((string) ($webUser->role ?? ''));
                        $hasInventoryAccess = in_array((int) ($webUser->role_id ?? 0), [10, 11], true)
                            || in_array($userRoleName, ['admin_inventory', 'owner'], true);
                        // Calculate cart count for web users
                        try {
                            $cartCount = Cart::where('user_id', $webUser->user_id)->sum('quantity') ?? 0;
                        } catch (\Exception $e) {
                            $cartCount = 0;
                        }
                    }
                }
            } catch (\Exception $e) {
                // If any auth check fails, default to guest state
                $currentUser = null;
                $isAdmin = false;
                $isCustomer = false;
                $hasInventoryAccess = false;
                $cartCount = 0;
            }
            
            $view->with([
                'cartCount' => $cartCount,
                'currentUser' => $currentUser,
                'isAdmin' => $isAdmin,
                'isCustomer' => $isCustomer,
                'hasInventoryAccess' => $hasInventoryAccess
            ]);
        });

        // Share notification data with admin layout views
        View::composer(['layouts.admin.*'], function ($view) {
            try {
                $notificationData = NotificationHelper::getNotificationData();
                $view->with('notificationData', $notificationData);
            } catch (\Exception $e) {
                // Fallback jika terjadi error
                $view->with('notificationData', [
                    'pending_affiliates' => 0,
                    'pending_resellers' => 0,
                    'total_pending' => 0,
                    'has_notifications' => false
                ]);
            }
        });
    }
}
