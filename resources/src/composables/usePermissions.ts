import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

// Permission enum values (must match backend Permission enum)
export const Permission = {
  // Admin-level permissions
  MANAGE_USERS: 'manage_users',
  MANAGE_SETTINGS: 'manage_settings',
  MANAGE_API_KEYS: 'manage_api_keys',
  MANAGE_WEBHOOKS: 'manage_webhooks',
  MANAGE_SUB_ACCOUNTS: 'manage_sub_accounts',
  VIEW_AUDIT_LOGS: 'view_audit_logs',
  // Regular permissions
  SEND_SMS: 'send_sms',
  VIEW_HISTORY: 'view_history',
  MANAGE_CONTACTS: 'manage_contacts',
  MANAGE_GROUPS: 'manage_groups',
  CREATE_CAMPAIGNS: 'create_campaigns',
  VIEW_ANALYTICS: 'view_analytics',
  MANAGE_TEMPLATES: 'manage_templates',
  EXPORT_DATA: 'export_data',
} as const

export type PermissionType = typeof Permission[keyof typeof Permission]

// Role enum values (must match backend UserRole enum)
export const UserRole = {
  SUPER_ADMIN: 'super_admin',
  ADMIN: 'admin',
  AGENT: 'agent',
} as const

export type UserRoleType = typeof UserRole[keyof typeof UserRole]

// Role hierarchy levels
const ROLE_LEVELS: Record<UserRoleType, number> = {
  [UserRole.SUPER_ADMIN]: 100,
  [UserRole.ADMIN]: 50,
  [UserRole.AGENT]: 10,
}

export function usePermissions() {
  const authStore = useAuthStore()

  // Computed properties for current user
  const userRole = computed(() => authStore.user?.role as UserRoleType | undefined)
  const userPermissions = computed(() => authStore.user?.permissions ?? [])
  const isSuperAdmin = computed(() => authStore.user?.is_super_admin ?? false)
  const isAdmin = computed(() => authStore.user?.is_admin ?? false)

  /**
   * Check if user has a specific permission
   */
  function hasPermission(permission: PermissionType | string): boolean {
    // SuperAdmin has all permissions
    if (isSuperAdmin.value) {
      return true
    }
    return userPermissions.value.includes(permission)
  }

  /**
   * Check if user has any of the given permissions
   */
  function hasAnyPermission(permissions: (PermissionType | string)[]): boolean {
    if (isSuperAdmin.value) {
      return true
    }
    return permissions.some(p => userPermissions.value.includes(p))
  }

  /**
   * Check if user has all of the given permissions
   */
  function hasAllPermissions(permissions: (PermissionType | string)[]): boolean {
    if (isSuperAdmin.value) {
      return true
    }
    return permissions.every(p => userPermissions.value.includes(p))
  }

  /**
   * Check if user has a specific role
   */
  function hasRole(role: UserRoleType | string): boolean {
    return userRole.value === role
  }

  /**
   * Check if user's role is at least as privileged as the given role
   */
  function isAtLeast(role: UserRoleType | string): boolean {
    if (!userRole.value) {
      return false
    }
    const currentLevel = ROLE_LEVELS[userRole.value] ?? 0
    const targetLevel = ROLE_LEVELS[role as UserRoleType] ?? 0
    return currentLevel >= targetLevel
  }

  /**
   * Check if user can access a route/feature with given requirements
   */
  function canAccess(requirements: {
    permission?: PermissionType | string
    permissions?: (PermissionType | string)[]
    anyPermission?: (PermissionType | string)[]
    role?: UserRoleType | string
    minRole?: UserRoleType | string
  }): boolean {
    // SuperAdmin can access everything
    if (isSuperAdmin.value) {
      return true
    }

    // Check single permission
    if (requirements.permission && !hasPermission(requirements.permission)) {
      return false
    }

    // Check all permissions
    if (requirements.permissions && !hasAllPermissions(requirements.permissions)) {
      return false
    }

    // Check any permission
    if (requirements.anyPermission && !hasAnyPermission(requirements.anyPermission)) {
      return false
    }

    // Check exact role
    if (requirements.role && !hasRole(requirements.role)) {
      return false
    }

    // Check minimum role
    if (requirements.minRole && !isAtLeast(requirements.minRole)) {
      return false
    }

    return true
  }

  return {
    // Computed
    userRole,
    userPermissions,
    isSuperAdmin,
    isAdmin,
    // Methods
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    isAtLeast,
    canAccess,
    // Constants
    Permission,
    UserRole,
  }
}
