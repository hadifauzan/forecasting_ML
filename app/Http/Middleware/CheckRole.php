<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Safety check for user object
        if (!$user) {
            return redirect()->route('login');
        }

        // Debug log with safe property access
        Log::info('CheckRole Middleware', [
            'user_id' => $user->user_id ?? $user->id ?? 'unknown',
            'user_role_id' => $user->role_id ?? 'unknown',
            'user_email' => $user->email ?? 'unknown',
            'required_roles' => $roles,
            'url' => $request->url(),
            'has_spatie_methods' => method_exists($user, 'hasRole')
        ]);

        // Primary check: Database-based role checking
        if (method_exists($user, 'hasRole')) {
            try {
                foreach ($roles as $role) {
                    if ($user->hasRole($role)) {
                        Log::info('Access granted - Database role', [
                            'user_id' => $user->user_id ?? $user->id,
                            'user_role' => $role,
                            'role_from_db' => $user->masterRole->name_role ?? 'unknown'
                        ]);
                        return $next($request);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Database role check failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->user_id ?? $user->id
                ]);
                // Continue to fallback method
            }
        }

        // Fallback: Traditional role checking (for compatibility)
        foreach ($roles as $role) {
            $hasAccess = $this->checkTraditionalRole($user, $role);
            
            if ($hasAccess) {
                Log::info('Access granted - Traditional role', [
                    'user_id' => $user->user_id ?? $user->id,
                    'user_role_id' => $user->role_id ?? 'unknown',
                    'required_role' => $role
                ]);
                return $next($request);
            }
        }

        // Log unauthorized access
        Log::warning('Unauthorized access attempt', [
            'user_id' => $user->user_id ?? $user->id,
            'user_role_id' => $user->role_id ?? 'unknown',
            'user_email' => $user->email ?? 'unknown',
            'required_roles' => $roles,
            'url' => $request->url()
        ]);

        abort(403, 'Akses Ditolak. Halaman ini hanya dapat diakses oleh Administrator. Silakan login dengan akun admin untuk melanjutkan');
    }

    /**
     * Check traditional role (fallback method)
     */
    private function checkTraditionalRole($user, $role): bool
    {
        try {
            switch ($role) {
                case 'superadmin':
                    return ($user->role_id ?? 0) == 5;
                    
                case 'admin_content':
                    return ($user->role_id ?? 0) == 7;
                    
                case 'admin_partner':
                    return ($user->role_id ?? 0) == 8;
                    
                case 'admin_seller':
                    return ($user->role_id ?? 0) == 9;

                case 'admin_inventory':
                    return ($user->role_id ?? 0) == 10;

                case 'owner':
                    return ($user->role_id ?? 0) == 11;
                    
                case 'admin':
                    // Generic admin check - allow all admin types (5, 7, 8, 9, 10, 11)
                    return in_array(($user->role_id ?? 0), [5, 7, 8, 9, 10, 11]);
                    
                case 'user':
                    return ($user->role_id ?? 0) == 6;
                    
                case 'affiliator':
                    return ($user->role_id ?? 0) == 4;
                    
                default:
                    // Fallback: check by role name property
                    $userRoleName = strtolower($user->role ?? '');
                    return $userRoleName === strtolower($role);
            }
        } catch (\Exception $e) {
            Log::error('Traditional role check failed', [
                'error' => $e->getMessage(),
                'role' => $role,
                'user_id' => $user->user_id ?? $user->id ?? 'unknown'
            ]);
            return false;
        }
    }
}
