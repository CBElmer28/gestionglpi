import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('biblioteca_user')) || null)
  const token = ref(localStorage.getItem('biblioteca_token') || null)
  const permissions = ref(JSON.parse(localStorage.getItem('biblioteca_permissions')) || [])

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role?.slug === 'admin')
  const isLector = computed(() => user.value?.role?.slug === 'lector')

  // Helper para verificar permisos dinámicos
  const can = (p) => permissions.value.includes(p)

  function setSession(u, t, p = []) {
    user.value = u
    token.value = t
    permissions.value = p
    localStorage.setItem('biblioteca_user', JSON.stringify(u))
    localStorage.setItem('biblioteca_token', t)
    localStorage.setItem('biblioteca_permissions', JSON.stringify(p))
  }

  function clearSession() {
    user.value = null
    token.value = null
    permissions.value = []
    localStorage.removeItem('biblioteca_user')
    localStorage.removeItem('biblioteca_token')
    localStorage.removeItem('biblioteca_permissions')
  }

  async function login(credentials) {
    const { data } = await authService.login(credentials)
    setSession(data.user, data.token, data.user.permissions || [])
    return data.user
  }

  async function register(userData) {
    const { data } = await authService.register(userData)
    setSession(data.user, data.token, data.user.permissions || [])
    return data.user
  }

  async function logout() {
    try {
      await authService.logout()
    } finally {
      clearSession()
    }
  }

  async function fetchMe() {
    try {
      const { data } = await authService.me()
      setSession(data, token.value, data.permissions || [])
    } catch {
      clearSession()
    }
  }

  return {
    user, token, permissions,
    isAuthenticated, isAdmin, isLector, can,
    login, register, logout, fetchMe
  }
})
