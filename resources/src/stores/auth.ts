import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || '/api'

export interface User {
  id: number
  name: string
  email: string
  role: string
  avatar?: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isAuthenticated = computed(() => !!token.value)

  // Configure axios interceptor
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  axios.interceptors.response.use(
    response => response,
    error => {
      if (error.response?.status === 401) {
        logout()
        window.location.href = '/sendwave-pro/public/login'
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

  return {
    user,
    token,
    isAuthenticated,
    login,
    logout,
    loadUser
  }
})
