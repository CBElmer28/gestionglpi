import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(sessionStorage.getItem('biblioteca_user')) || null)
  const token = ref(sessionStorage.getItem('biblioteca_token') || null)
  const permissions = ref(JSON.parse(sessionStorage.getItem('biblioteca_permissions')) || [])

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role?.slug === 'admin')
  const isLector = computed(() => user.value?.role?.slug === 'lector')

  // Helper para verificar permisos dinámicos
  const can = (p) => permissions.value.includes(p)

  // ── Temporizador de Inactividad ──────────────────────────────────────
  const INACTIVITY_TIMEOUT = 15 * 60 * 1000 // 15 minutos
  let inactivityTimer = null

  function resetInactivityTimer() {
    if (!isAuthenticated.value) return
    
    if (inactivityTimer) clearTimeout(inactivityTimer)
    
    inactivityTimer = setTimeout(() => {
      console.log('Sesión expirada por inactividad')
      logout()
    }, INACTIVITY_TIMEOUT)
  }

  function setSession(u, t, p = []) {
    user.value = u
    token.value = t
    permissions.value = p
    sessionStorage.setItem('biblioteca_user', JSON.stringify(u))
    sessionStorage.setItem('biblioteca_token', t)
    sessionStorage.setItem('biblioteca_permissions', JSON.stringify(p))
    
    resetInactivityTimer()
  }

  function clearSession() {
    user.value = null
    token.value = null
    permissions.value = []
    sessionStorage.removeItem('biblioteca_user')
    sessionStorage.removeItem('biblioteca_token')
    sessionStorage.removeItem('biblioteca_permissions')
    
    if (inactivityTimer) {
      clearTimeout(inactivityTimer)
      inactivityTimer = null
    }
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
      // Intento de notificación al servidor (sin await para que sea instantáneo localmente)
      authService.logout()
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

  // Inicializar temporizador si ya estamos autenticados al cargar el store
  if (isAuthenticated.value) {
    resetInactivityTimer()
  }

  return {
    user, token, permissions,
    isAuthenticated, isAdmin, isLector, can,
    login, register, logout, fetchMe, setSession, clearSession,
    resetInactivityTimer
  }
})
