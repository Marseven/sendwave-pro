import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import '@/nprogress-custom.css'

// Permission constants (must match backend)
const Permission = {
  MANAGE_USERS: 'manage_users',
  MANAGE_SETTINGS: 'manage_settings',
  MANAGE_API_KEYS: 'manage_api_keys',
  MANAGE_WEBHOOKS: 'manage_webhooks',
  MANAGE_SUB_ACCOUNTS: 'manage_sub_accounts',
  VIEW_AUDIT_LOGS: 'view_audit_logs',
  SEND_SMS: 'send_sms',
  VIEW_HISTORY: 'view_history',
  MANAGE_CONTACTS: 'manage_contacts',
  MANAGE_GROUPS: 'manage_groups',
  CREATE_CAMPAIGNS: 'create_campaigns',
  VIEW_ANALYTICS: 'view_analytics',
  MANAGE_TEMPLATES: 'manage_templates',
  EXPORT_DATA: 'export_data',
}

// Extend route meta type
declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    permission?: string
    permissions?: string[]
    anyPermission?: string[]
    minRole?: 'super_admin' | 'admin' | 'agent'
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true, permission: Permission.VIEW_ANALYTICS }
  },
  {
    path: '/send-message',
    name: 'SendMessage',
    component: () => import('@/views/SendMessage.vue'),
    meta: { requiresAuth: true, permission: Permission.SEND_SMS }
  },
  {
    path: '/send-sms',
    name: 'SendSms',
    component: () => import('@/views/SendSms.vue'),
    meta: { requiresAuth: true, permission: Permission.SEND_SMS }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/Profile.vue'),
    meta: { requiresAuth: true } // Accessible to all authenticated users
  },
  {
    path: '/contacts',
    name: 'Contacts',
    component: () => import('@/views/Contacts.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_CONTACTS }
  },
  {
    path: '/database',
    name: 'Database',
    component: () => import('@/views/Database.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_CONTACTS }
  },
  {
    path: '/contact-groups',
    name: 'ContactGroups',
    component: () => import('@/views/ContactGroups.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_GROUPS }
  },
  {
    path: '/templates',
    name: 'Templates',
    component: () => import('@/views/Templates.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_TEMPLATES }
  },
  {
    path: '/transactional',
    name: 'Transactional',
    component: () => import('@/views/Transactional.vue'),
    meta: { requiresAuth: true, permission: Permission.SEND_SMS }
  },
  {
    path: '/accounts',
    name: 'Accounts',
    component: () => import('@/views/Accounts.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_SUB_ACCOUNTS }
  },
  {
    path: '/api',
    name: 'ApiConfiguration',
    component: () => import('@/views/ApiConfiguration.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_API_KEYS }
  },
  {
    path: '/api-keys',
    name: 'ApiIntegrations',
    component: () => import('@/views/ApiIntegrations.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_API_KEYS }
  },
  {
    path: '/webhooks',
    name: 'Webhooks',
    component: () => import('@/views/Webhooks.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_WEBHOOKS }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/views/Settings.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_SETTINGS }
  },
  {
    path: '/campaign/create',
    name: 'CampaignCreate',
    component: () => import('@/views/CampaignCreate.vue'),
    meta: { requiresAuth: true, permission: Permission.CREATE_CAMPAIGNS }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('@/views/Reports.vue'),
    meta: { requiresAuth: true, permission: Permission.VIEW_ANALYTICS }
  },
  {
    path: '/calendar',
    name: 'Calendar',
    component: () => import('@/views/Calendar.vue'),
    meta: { requiresAuth: true, permission: Permission.CREATE_CAMPAIGNS }
  },
  {
    path: '/campaigns/history',
    name: 'CampaignHistory',
    component: () => import('@/views/CampaignHistory.vue'),
    meta: { requiresAuth: true, permission: Permission.VIEW_HISTORY }
  },
  {
    path: '/messages/history',
    name: 'MessageHistory',
    component: () => import('@/views/MessageHistory.vue'),
    meta: { requiresAuth: true, permission: Permission.VIEW_HISTORY }
  },
  {
    path: '/blacklist',
    name: 'Blacklist',
    component: () => import('@/views/Blacklist.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_SETTINGS }
  },
  {
    path: '/sms-config',
    name: 'SmsConfig',
    component: () => import('@/views/SmsConfig.vue'),
    meta: { requiresAuth: true, permission: Permission.MANAGE_SETTINGS }
  },
  {
    path: '/audit-logs',
    name: 'AuditLogs',
    component: () => import('@/views/AuditLogs.vue'),
    meta: { requiresAuth: true, permission: Permission.VIEW_AUDIT_LOGS }
  },
  {
    path: '/access-denied',
    name: 'AccessDenied',
    component: () => import('@/views/AccessDenied.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory('/'),
  routes
})

// Configure NProgress
NProgress.configure({
  showSpinner: false,
  trickleSpeed: 200,
  minimum: 0.3
})

// Role hierarchy levels
const ROLE_LEVELS: Record<string, number> = {
  'super_admin': 100,
  'admin': 50,
  'agent': 10,
}

// Navigation guard
router.beforeEach(async (to, from, next) => {
  // Start progress bar
  NProgress.start()

  const authStore = useAuthStore()

  // Check authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }

  // Redirect to dashboard if already logged in
  if (to.path === '/login' && authStore.isAuthenticated) {
    next('/dashboard')
    return
  }

  // Load user if authenticated but user data not loaded
  if (authStore.isAuthenticated && !authStore.user) {
    await authStore.loadUser()
  }

  // Check permissions for protected routes
  if (to.meta.requiresAuth && authStore.user) {
    // SuperAdmin bypasses all permission checks
    if (authStore.isSuperAdmin) {
      next()
      return
    }

    // Check single permission
    if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
      next('/access-denied')
      return
    }

    // Check all permissions required
    if (to.meta.permissions && !authStore.hasAllPermissions(to.meta.permissions)) {
      next('/access-denied')
      return
    }

    // Check any permission required
    if (to.meta.anyPermission && !authStore.hasAnyPermission(to.meta.anyPermission)) {
      next('/access-denied')
      return
    }

    // Check minimum role level
    if (to.meta.minRole) {
      const userLevel = ROLE_LEVELS[authStore.userRole ?? ''] ?? 0
      const requiredLevel = ROLE_LEVELS[to.meta.minRole] ?? 0
      if (userLevel < requiredLevel) {
        next('/access-denied')
        return
      }
    }
  }

  next()
})

router.afterEach(() => {
  // Complete progress bar
  NProgress.done()
})

export default router
