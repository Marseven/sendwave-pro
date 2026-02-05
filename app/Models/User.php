<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'parent_id',
        'name',
        'email',
        'password',
        'role',
        'custom_role_id',
        'permissions',
        'status',
        'avatar',
        'phone',
        'company',
        'email_notifications',
        'weekly_reports',
        'campaign_alerts',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'email_notifications' => 'boolean',
            'weekly_reports' => 'boolean',
            'campaign_alerts' => 'boolean',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Account this user belongs to
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Parent user (who created this user)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Child users (users created by this user)
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Custom role assigned to this user
     */
    public function customRole(): BelongsTo
    {
        return $this->belongsTo(CustomRole::class, 'custom_role_id');
    }

    /**
     * Custom roles created by this user
     */
    public function createdRoles(): HasMany
    {
        return $this->hasMany(CustomRole::class, 'created_by');
    }

    /**
     * Relation with SubAccounts (users can have sub-accounts)
     */
    public function subAccounts(): HasMany
    {
        return $this->hasMany(SubAccount::class);
    }

    /**
     * Get the default SubAccount for this user's account
     */
    public function getDefaultSubAccount(): ?SubAccount
    {
        if (!$this->account_id) {
            return null;
        }

        return SubAccount::where('account_id', $this->account_id)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Relation with SMS messages
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * Relation with contacts
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Relation with groups
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Relation with campaigns
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Relation with templates
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    // =========================================================================
    // ROLE METHODS
    // =========================================================================

    /**
     * Get the user's role as enum
     */
    public function getRoleEnum(): ?UserRole
    {
        return UserRole::tryFrom($this->role);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string|UserRole $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        return $this->role === $roleValue;
    }

    /**
     * Check if user's role is at least as privileged as the given role
     */
    public function isAtLeast(string|UserRole $role): bool
    {
        $currentRole = $this->getRoleEnum();
        if (!$currentRole) {
            return false;
        }

        $targetRole = $role instanceof UserRole ? $role : UserRole::tryFrom($role);
        if (!$targetRole) {
            return false;
        }

        return $currentRole->isAtLeast($targetRole);
    }

    /**
     * Check if user is a SuperAdmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN);
    }

    /**
     * Check if user is an Admin or higher
     */
    public function isAdmin(): bool
    {
        return $this->isAtLeast(UserRole::ADMIN);
    }

    /**
     * Check if user is an Agent
     */
    public function isAgent(): bool
    {
        return $this->hasRole(UserRole::AGENT);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // =========================================================================
    // PERMISSION METHODS
    // =========================================================================

    /**
     * Get all user permissions
     */
    public function getAllPermissions(): array
    {
        // SuperAdmin has all permissions
        if ($this->isSuperAdmin()) {
            return Permission::values();
        }

        // If user has a custom role, use its permissions
        if ($this->custom_role_id && $this->customRole) {
            return $this->customRole->permissions ?? [];
        }

        return $this->permissions ?? [];
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string|Permission $permission): bool
    {
        // SuperAdmin bypasses all permission checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Suspended users have no permissions
        if ($this->isSuspended()) {
            return false;
        }

        $permissionValue = $permission instanceof Permission ? $permission->value : $permission;
        $permissions = $this->getAllPermissions();

        return in_array($permissionValue, $permissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Grant a permission to the user
     */
    public function grantPermission(string|Permission $permission): void
    {
        $permissionValue = $permission instanceof Permission ? $permission->value : $permission;
        $permissions = $this->permissions ?? [];

        if (!in_array($permissionValue, $permissions)) {
            $permissions[] = $permissionValue;
            $this->permissions = $permissions;
            $this->save();
        }
    }

    /**
     * Grant multiple permissions to the user
     */
    public function grantPermissions(array $permissions): void
    {
        $currentPermissions = $this->permissions ?? [];

        foreach ($permissions as $permission) {
            $permissionValue = $permission instanceof Permission ? $permission->value : $permission;
            if (!in_array($permissionValue, $currentPermissions)) {
                $currentPermissions[] = $permissionValue;
            }
        }

        $this->permissions = $currentPermissions;
        $this->save();
    }

    /**
     * Revoke a permission from the user
     */
    public function revokePermission(string|Permission $permission): void
    {
        $permissionValue = $permission instanceof Permission ? $permission->value : $permission;
        $permissions = $this->permissions ?? [];

        $this->permissions = array_values(array_filter(
            $permissions,
            fn($p) => $p !== $permissionValue
        ));
        $this->save();
    }

    /**
     * Revoke multiple permissions from the user
     */
    public function revokePermissions(array $permissions): void
    {
        $permissionsToRemove = array_map(
            fn($p) => $p instanceof Permission ? $p->value : $p,
            $permissions
        );

        $currentPermissions = $this->permissions ?? [];
        $this->permissions = array_values(array_filter(
            $currentPermissions,
            fn($p) => !in_array($p, $permissionsToRemove)
        ));
        $this->save();
    }

    /**
     * Sync permissions (replace all permissions)
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions = array_map(
            fn($p) => $p instanceof Permission ? $p->value : $p,
            $permissions
        );
        $this->save();
    }

    /**
     * Set role and apply default permissions
     */
    public function setRole(string|UserRole $role): void
    {
        $roleEnum = $role instanceof UserRole ? $role : UserRole::from($role);
        $this->role = $roleEnum->value;
        $this->permissions = $roleEnum->defaultPermissions();
        $this->custom_role_id = null; // Clear custom role when setting standard role
        $this->save();
    }

    /**
     * Assign a custom role to the user
     */
    public function assignCustomRole(CustomRole $customRole): void
    {
        $this->custom_role_id = $customRole->id;
        $this->permissions = $customRole->permissions;
        $this->save();
    }

    // =========================================================================
    // HIERARCHY METHODS
    // =========================================================================

    /**
     * Check if this user can manage the given user
     */
    public function canManage(User $user): bool
    {
        // SuperAdmin can manage everyone
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Cannot manage yourself
        if ($this->id === $user->id) {
            return false;
        }

        // Admin can manage their own children (agents they created)
        if ($this->isAdmin() && $user->parent_id === $this->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if this user can create users with the given role
     */
    public function canCreateUserWithRole(string|UserRole $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        // SuperAdmin can create any role
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Admin can only create Agents
        if ($this->isAdmin()) {
            return $roleValue === UserRole::AGENT->value;
        }

        // Agents cannot create users
        return false;
    }

    /**
     * Get all descendant users (children, grandchildren, etc.)
     */
    public function getDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();
        $children = $this->children;

        foreach ($children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get the root user (top of hierarchy)
     */
    public function getRootUser(): User
    {
        $user = $this;
        while ($user->parent_id !== null) {
            $user = $user->parent;
        }
        return $user;
    }

    /**
     * Check if this user is a descendant of the given user
     */
    public function isDescendantOf(User $user): bool
    {
        $parent = $this->parent;
        while ($parent !== null) {
            if ($parent->id === $user->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }

    /**
     * Get manageable users for this user
     */
    public function getManageableUsers(): \Illuminate\Database\Eloquent\Collection
    {
        // SuperAdmin can see all users
        if ($this->isSuperAdmin()) {
            return User::where('id', '!=', $this->id)->get();
        }

        // Admin can see their children
        if ($this->isAdmin()) {
            return $this->children;
        }

        // Agents cannot manage anyone
        return collect();
    }
}
