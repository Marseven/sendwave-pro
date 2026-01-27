import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// Utiliser l'URL relative en production, localhost en dev
const API_URL = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
  ? 'http://localhost:8888/sendwave-pro/public/api'
  : '/api'

export type UserRole = 'super_admin' | 'admin' | 'agent'
export type UserStatus = 'active' | 'suspended' | 'pending'

export interface User {
  id: number
  account_id?: number
  parent_id?: number
  name: string
  email: string
  phone?: string
  company?: string
  avatar?: string
  role: UserRole
  role_label: string
  custom_role_id?: number
  custom_role_name?: string
  permissions: string[]
  status: UserStatus
  is_super_admin: boolean
  is_admin: boolean
  is_agent: boolean
  can_manage_users: boolean
  can_create_agents: boolean
  email_notifications?: boolean
  weekly_reports?: boolean
  campaign_alerts?: boolean
  email_verified_at?: string
  created_at?: string
  updated_at?: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isAuthenticated = computed(() => !!token.value)

  // Computed for permission checks
  const isSuperAdmin = computed(() => user.value?.is_super_admin ?? false)
  const isAdmin = computed(() => user.value?.is_admin ?? false)
  const isAgent = computed(() => user.value?.is_agent ?? false)
  const userRole = computed(() => user.value?.role)
  const userPermissions = computed(() => user.value?.permissions ?? [])
  const canManageUsers = computed(() => user.value?.can_manage_users ?? false)
  const canCreateAgents = computed(() => user.value?.can_create_agents ?? false)

  // Configure axios interceptor
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  axios.interceptors.response.use(
    response => response,
    error => {
      if (error.response?.status === 401) {
        logout()
        window.location.href = '/login'
      }
      return Promise.reject(error)
    }
  )

  async function login(email: string, password: string): Promise<boolean> {
    try {
      const response = await axios.post(`${API_URL}/auth/login`, { email, password })

      token.value = response.data.access_token
      user.value = response.data.user

      localStorage.setItem('auth_token', token.value!)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

      return true
    } catch (error) {
      console.error('Login error:', error)
      return false
    }
  }

  async function logout() {
    try {
      await axios.post(`${API_URL}/auth/logout`)
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  async function loadUser() {
    try {
      const response = await axios.get(`${API_URL}/auth/me`)
      user.value = response.data
    } catch (error) {
      console.error('Load user error:', error)
      token.value = null
      user.value = null
    }
  }

  /**
   * Check if user has a specific permission
   */
  function hasPermission(permission: string): boolean {
    if (isSuperAdmin.value) {
      return true
    }
    return userPermissions.value.includes(permission)
  }

  /**
   * Check if user has any of the given permissions
   */
  function hasAnyPermission(permissions: string[]): boolean {
    if (isSuperAdmin.value) {
      return true
    }
    return permissions.some(p => userPermissions.value.includes(p))
  }

  /**
   * Check if user has all of the given permissions
   */
  function hasAllPermissions(permissions: string[]): boolean {
    if (isSuperAdmin.value) {
      return true
    }
    return permissions.every(p => userPermissions.value.includes(p))
  }

  return {
    user,
    token,
    isAuthenticated,
    isSuperAdmin,
    isAdmin,
    isAgent,
    userRole,
    userPermissions,
    canManageUsers,
    canCreateAgents,
    login,
    logout,
    loadUser,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions
  }
})
