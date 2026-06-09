<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\MasterCustomers;

class AuthController extends Controller
{
    /**
     * Menampilkan form login
     */
    public function showLoginForm()
    {
        // Check if user is already logged in
        if (Auth::check()) {
            $user = Auth::user();
            // If admin (role_id 5, 7, 8, 9), redirect to admin dashboard
            if (in_array($user->role_id, [5, 7, 8, 9])) {
                return redirect()->route('admin.dashboard');
            }
            // If admin inventory or owner (role_id 10, 11), redirect to admin inventory dashboard
            if (in_array($user->role_id, [10, 11])) {
                return redirect()->route('admin.inventory.dashboard');
            }
            // Otherwise redirect to shopping
            return redirect()->route('shopping');
        }
        
        // Check if customer is already logged in
        if (Auth::guard('customer')->check()) {
            return redirect()->route('shopping');
        }
        
        return view('auth.login');
    }

    /**
     * Handle permintaan login - support untuk User dan Customer
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'Email atau nomor handphone wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $identifier = $request->email; // Could be email or phone
        $password = $request->password;
        $remember = $request->has('remember');

        // First, try to login as Admin/User (master_users table)
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // If it's an email, try admin login first
            $credentials = ['email' => $identifier, 'password' => $password];
            
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                $user = Auth::user();

                // Check if email is verified (for affiliates with role_id 4)
                if ($user->role_id == 4 && !$user->email_verified_at) {
                    Auth::logout();
                    return back()->withErrors([
                        'email' => 'Email Anda belum diverifikasi. Silakan cek email untuk kode OTP.',
                    ])->withInput($request->only('email'));
                }

                // Check if user status is not Aktif (for affiliates)
                if ($user->role_id == 4 && $user->status !== 'Aktif') {
                    Auth::logout();
                    $statusMessage = match($user->status) {
                        'Pending' => 'Akun Anda masih menunggu konfirmasi dari admin.',
                        'Nonaktif' => 'Akun Anda telah dinonaktifkan. Silakan hubungi admin.',
                        default => 'Status akun Anda tidak memungkinkan untuk login.'
                    };
                    return back()->withErrors([
                        'email' => $statusMessage,
                    ])->withInput($request->only('email'));
                }

                // Check if user must change password (activated affiliates)
                if ($user->must_change_password) {
                    return redirect()->route('change-password')
                        ->with('warning', 'Untuk keamanan akun Anda, silakan ubah password terlebih dahulu.');
                }

                // Check if user is any type of admin (role_id 5, 7, 8, 9)
                if (in_array($user->role_id, [5, 7, 8, 9])) {
                    return redirect()->route('admin.dashboard');
                }
                // Admin Inventory, Owner (role_id 10, 11)
                if (in_array($user->role_id, [10, 11])) {
                    return redirect()->route('admin.inventory.dashboard');
                }
                return redirect()->route('shopping');
            }
        }

        // If admin login fails or it's a phone number, try customer login
        $customer = MasterCustomers::findByEmailOrPhone($identifier);
        
        if ($customer && Hash::check($password, $customer->password)) {
            // Check if customer is a reseller, agent, or reseller baby spa (type 2, 3, or 4) and validate status
            if (in_array($customer->customer_type_id, [2, 3, 4])) { // Agent (2), Reseller (3), or Reseller Baby Spa (4)
                // Check reseller/agent/reseller baby spa status - only allow login if status is "Aktif"
                if ($customer->status !== 'Aktif') {
                    $statusMessage = match($customer->status) {
                        'Pending' => 'Akun Anda masih menunggu konfirmasi dari admin. Silakan hubungi admin untuk aktivasi akun.',
                        'Nonaktif' => 'Akun Anda telah dinonaktifkan. Silakan hubungi admin untuk mengaktifkan kembali.',
                        default => 'Status akun Anda tidak memungkinkan untuk login. Silakan hubungi admin.'
                    };
                    
                    return back()->withErrors([
                        'email' => $statusMessage,
                    ])->withInput($request->only('email'));
                }
                
                // If status is Aktif, proceed with login
                Auth::guard('customer')->login($customer, $remember);
                $request->session()->regenerate();
                
                // Store customer type in session for pricing logic
                $request->session()->put('is_reseller', true);
                $request->session()->put('customer_type_id', $customer->customer_type_id);
                
                // Set label based on customer type
                $customerTypeLabel = match($customer->customer_type_id) {
                    2 => 'Agent',
                    3 => 'Reseller',
                    4 => 'Reseller Baby Spa',
                    default => 'Partner'
                };
                
                return redirect()->route('shopping')->with('success', 'Selamat datang, ' . $customer->name_customer . ' (' . $customerTypeLabel . ')');
            } else {
                // Regular customer - no status check needed
                Auth::guard('customer')->login($customer, $remember);
                $request->session()->regenerate();
                
                // Regular customer
                $request->session()->put('is_reseller', false);
                $request->session()->put('customer_type_id', 1);
                
                return redirect()->route('shopping')->with('success', 'Selamat datang, ' . $customer->name_customer);
            }
        }

        // If both attempts fail
        return back()->withErrors([
            'email' => 'Email/nomor handphone atau password salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle permintaan logout - support untuk User dan Customer
     */
    public function logout(Request $request)
    {
        // Get session ID before logout for debugging
        $sessionId = $request->session()->getId();
        
        // Logout from both guards explicitly
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }
        
        if (Auth::guard('customer')->check()) {
            Auth::guard('customer')->logout();
        }
        
        // Force logout from all guards
        Auth::logout();
        
        // Clear reseller and customer-specific session data
        $request->session()->forget('is_reseller');
        $request->session()->forget('customer_type_id');
        $request->session()->forget('cart');
        
        // Invalidate the session and regenerate token to prevent session fixation
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Additional flush to ensure all session data is cleared
        $request->session()->flush();
        
        // Create redirect response to home page
        $response = redirect()->route('home')->with('success', 'Anda berhasil logout!');
        
        // Clear all authentication-related cookies
        $response->withCookie(cookie()->forget('remember_web'));
        $response->withCookie(cookie()->forget('remember_customer'));
        
        // Get the session cookie name from config
        $sessionCookieName = config('session.cookie');
        if ($sessionCookieName) {
            $response->withCookie(cookie()->forget($sessionCookieName));
        }
        
        return $response;
    }

    /**
     * Menampilkan form register
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Handle permintaan register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string', 
                'email',
                'max:255',
                'unique:master_users,email',
                'unique:master_customers,email_customer'
            ],
            'password' => 'required|string|min:8|confirmed',
            'phone' => [
                'required',
                'string',
                'max:20',
                'unique:master_customers,phone_customer'
            ],
            'address' => 'required|string|max:500',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.unique' => 'Nomor telepon sudah terdaftar.',
            'address.required' => 'Alamat wajib diisi.',
        ]);

        // Use database transaction to ensure data consistency
        DB::beginTransaction();
        
        try {
            // Create user record in master_users table
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => 6, // Default role for new registrations (user role)
                'company_id' => 3, // Default company ID for public users
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            // If user has role_id = 6, also create master_customer record
            if ($user->role_id == 6) {
                MasterCustomers::create([
                    'company_id' => 3, // Same as user's company_id
                    'customer_type_id' => 1, // Regular customer type
                    'email_customer' => $request->email,
                    'name_customer' => $request->name,
                    'phone_customer' => $request->phone,
                    'address_customer' => $request->address,
                    'password' => Hash::make($request->password), // Same password as user
                    'status' => 'Aktif', // Set as active customer
                    'point' => '0', // Initialize points to 0
                ]);
            }

            DB::commit();

            // Auto login after registration
            Auth::login($user);

            return redirect()->route('shopping')->with('success', 'Registrasi berhasil! Selamat berbelanja di Gentle Living.');

        } catch (\Exception $e) {
            DB::rollback();
            
            // Log the error for debugging
            Log::error('Registration failed: ' . $e->getMessage());
            
            return back()->withErrors([
                'email' => 'Terjadi kesalahan saat registrasi. Silakan coba lagi.'
            ])->withInput($request->only('name', 'email', 'phone', 'address'));
        }
    }

    /**
     * Menampilkan form ganti password
     */
    public function showChangePasswordForm()
    {
        $user = Auth::user();
        $mustChange = $user && $user->must_change_password;
        
        return view('auth.change-password', compact('mustChange'));
    }

    /**
     * Handle permintaan ganti password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.'
            ]);
        }

        // Update password (password ada di fillable, bisa pakai update)
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);
        
        // Reset flag must_change_password (ada di guarded, harus manual)
        $user->must_change_password = false;
        $user->save();

        // Determine redirect based on user role
        if (in_array($user->role_id, [5, 7, 8, 9])) {
            return redirect()->route('admin.dashboard')->with('success', 'Password berhasil diubah!');
        } elseif (in_array($user->role_id, [10, 11])) {
            return redirect()->route('admin.inventory.dashboard')->with('success', 'Password berhasil diubah!');
        } else {
            return redirect()->route('shopping')->with('success', 'Password berhasil diubah!');
        }
    }
}
