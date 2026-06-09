<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    // Specify the table name
    protected $table = 'master_users';

    // Specify the primary key
    protected $primaryKey = 'user_id';

    // Specify the key type if not integer
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'province',
        'profession',
        'birth_date',
        'gender',
        'instagram_account',
        'tiktok_account',
        'shopee_account',
        'other_source',
        'source_info',
        'notes',
        'email_verified_at',
        'profile_picture',
    ];

    /**
     * The attributes that aren't mass assignable.
     * Sensitive fields that should not be mass assigned
     */
    protected $guarded = [
        'user_id',
        'role_id',
        'company_id',
        'status',
        'must_change_password',
        'remember_token',
        'jwt_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'jwt_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    // Accessor for role name
    public function getRoleAttribute()
    {
        return $this->masterRole ? $this->masterRole->name_role : null;
    }

    // Check if user is admin/superadmin
    public function isAdmin()
    {
        return $this->role_id == 1 || in_array(strtolower($this->role ?? ''), ['admin', 'superadmin']);
    }

    // Check if user is regular user
    public function isUser()
    {
        return $this->role_id == 6 || strtolower($this->role ?? '') === 'user';
    }

    // Get role name based on role_id
    public function getRoleName()
    {
        if ($this->role_id == 1) {
            return 'admin';
        } elseif ($this->role_id == 6) {
            return 'user';
        }
        return $this->role ?? 'unknown';
    }

    // Relationship to MasterRole
    public function masterRole()
    {
        return $this->belongsTo(MasterRole::class, 'role_id', 'role_id');
    }

    // Relationship to MasterCompany  
    public function masterCompany()
    {
        return $this->belongsTo(MasterCompany::class, 'company_id', 'company_id');
    }

    // Relationship to MasterCustomers (for role_id = 6 users)
    public function masterCustomer()
    {
        return $this->hasOne(MasterCustomers::class, 'email_customer', 'email');
    }

    // Relationship to UserOtp
    public function otps()
    {
        return $this->hasMany(UserOtp::class, 'user_id', 'user_id');
    }

    // Get latest valid OTP
    public function latestValidOtp()
    {
        return $this->hasOne(UserOtp::class, 'user_id', 'user_id')
                    ->valid()
                    ->latest();
    }

    // Role helper methods for admin types
    public function isSuperAdmin()
    {
        return $this->hasRole('superadmin');
    }

    public function isAdminKonten()
    {
        return $this->hasRole('admin_content');
    }

    public function isAdminPartner()
    {
        return $this->hasRole('admin_partner');
    }

    public function isAdminSeller()
    {
        return $this->hasRole('admin_seller');
    }

    public function getAdminType()
    {
        return $this->getRoleNameFromRoleId();
    }

    public function getAdminBadgeClass()
    {
        switch ($this->getAdminType()) {
            case 'superadmin':
                return 'badge-success';
            case 'admin_content':
                return 'badge-info';
            case 'admin_partner':
                return 'badge-warning';
            case 'admin_seller':
                return 'badge-primary';
            default:
                return 'badge-secondary';
        }
    }

    public function getAdminDisplayName()
    {
        switch ($this->getAdminType()) {
            case 'superadmin':
                return 'Super Admin';
            case 'admin_content':
                return 'Content Admin';
            case 'admin_partner':
                return 'Partner Admin';
            case 'admin_seller':
                return 'Seller Admin';
            case 'admin_inventory':
                return 'Inventory Admin';
            case 'owner':
                return 'Owner';
            default:
                return 'Admin';
        }
    }

    /**
     * Database-based role checking (replaces Spatie Permission)
     */
    public function hasRole($roleName)
    {
        // Check for generic 'admin' role - should match all admin types
        if ($roleName === 'admin') {
            return in_array($this->role_id, [5, 7, 8, 9, 10, 11]);
        }

        $roleName = strtolower((string) $roleName);

        // Prefer database role name when relation is available
        if ($this->masterRole && isset($this->masterRole->name_role)) {
            return strtolower((string) $this->masterRole->name_role) === $roleName;
        }

        // Fallback to role_id mapping without recursion
        return strtolower((string) $this->getRoleNameFromRoleId()) === $roleName;
    }

    /**
     * Map role_id to stable role name when relation data is unavailable.
     */
    private function getRoleNameFromRoleId(): ?string
    {
        return match ((int) $this->role_id) {
            5 => 'superadmin',
            7 => 'admin_content',
            8 => 'admin_partner',
            9 => 'admin_seller',
            10 => 'admin_inventory',
            11 => 'owner',
            default => null,
        };
    }

    /**
     * Get role names (for compatibility with Spatie Permission)
     */
    public function getRoleNames()
    {
        if ($this->masterRole) {
            return collect([$this->masterRole->name_role]);
        }
        
        return collect([$this->getAdminType()]);
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }
}
