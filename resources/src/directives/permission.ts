import type { Directive, DirectiveBinding, App } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * v-can directive - Show/hide elements based on permissions
 *
 * Usage:
 *   v-can="'send_sms'"                    - Check single permission
 *   v-can="['send_sms', 'view_history']"  - Check if user has ALL permissions
 *   v-can:any="['send_sms', 'view_history']" - Check if user has ANY permission
 *
 * Example:
 *   <button v-can="'send_sms'">Envoyer SMS</button>
 *   <div v-can="['manage_contacts', 'manage_groups']">...</div>
 *   <div v-can:any="['manage_settings', 'manage_api_keys']">...</div>
 */
export const vCan: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    checkPermission(el, binding)
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    checkPermission(el, binding)
  }
}

function checkPermission(el: HTMLElement, binding: DirectiveBinding) {
  const authStore = useAuthStore()
  const { value, arg } = binding

  let hasAccess = false

  if (Array.isArray(value)) {
    // Multiple permissions
    if (arg === 'any') {
      // User must have at least one of the permissions
      hasAccess = authStore.hasAnyPermission(value)
    } else {
      // User must have all permissions (default)
      hasAccess = authStore.hasAllPermissions(value)
    }
  } else if (typeof value === 'string') {
    // Single permission
    hasAccess = authStore.hasPermission(value)
  }

  if (!hasAccess) {
    el.style.display = 'none'
  } else {
    el.style.display = ''
  }
}

/**
 * v-role directive - Show/hide elements based on role
 *
 * Usage:
 *   v-role="'admin'"          - Check exact role
 *   v-role:min="'admin'"      - Check if role is at least admin level
 *
 * Example:
 *   <button v-role="'super_admin'">Gestion Utilisateurs</button>
 *   <div v-role:min="'admin'">Section Admin</div>
 */
export const vRole: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    checkRole(el, binding)
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    checkRole(el, binding)
  }
}

const ROLE_LEVELS: Record<string, number> = {
  'super_admin': 100,
  'admin': 50,
  'agent': 10,
}

function checkRole(el: HTMLElement, binding: DirectiveBinding) {
  const authStore = useAuthStore()
  const { value, arg } = binding

  let hasAccess = false

  if (arg === 'min') {
    // Check minimum role level
    const userLevel = ROLE_LEVELS[authStore.userRole ?? ''] ?? 0
    const requiredLevel = ROLE_LEVELS[value] ?? 0
    hasAccess = userLevel >= requiredLevel
  } else {
    // Check exact role
    hasAccess = authStore.userRole === value
  }

  if (!hasAccess) {
    el.style.display = 'none'
  } else {
    el.style.display = ''
  }
}

/**
 * Register all permission directives with the Vue app
 */
export function registerPermissionDirectives(app: App) {
  app.directive('can', vCan)
  app.directive('role', vRole)
}
