import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory('/sendwave-pro/public'),
  routes: [
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
      meta: { requiresAuth: true }
    },
    {
      path: '/send-message',
      name: 'SendMessage',
      component: () => import('@/views/SendMessage.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'Profile',
      component: () => import('@/views/Profile.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/contacts',
      name: 'Contacts',
      component: () => import('@/views/Contacts.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/templates',
      name: 'Templates',
      component: () => import('@/views/Templates.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/accounts',
      name: 'Accounts',
      component: () => import('@/views/Accounts.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/api',
      name: 'ApiConfiguration',
      component: () => import('@/views/ApiConfiguration.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/api-keys',
      name: 'ApiIntegrations',
      component: () => import('@/views/ApiIntegrations.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/settings',
      name: 'Settings',
      component: () => import('@/views/Settings.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/campaign/create',
      name: 'CampaignCreate',
      component: () => import('@/views/CampaignCreate.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/reports',
      name: 'Reports',
      component: () => import('@/views/Reports.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/calendar',
      name: 'Calendar',
      component: () => import('@/views/Calendar.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/campaigns/history',
      name: 'CampaignHistory',
      component: () => import('@/views/CampaignHistory.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/messages/history',
      name: 'MessageHistory',
      component: () => import('@/views/MessageHistory.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('@/views/NotFound.vue')
    }
  ]
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
