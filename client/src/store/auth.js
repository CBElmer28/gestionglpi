import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('biblioteca_token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('biblioteca_user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isBibliotecario = computed(() => ['admin', 'bibliotecario'].includes(user.value?.role))

  function setSession(t, u) {
    token.value = t
    user.value  = u
    localStorage.setItem('biblioteca_token', t)
    localStorage.setItem('biblioteca_user', JSON.stringify(u))
  }

  function clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem('biblioteca_token')
    localStorage.removeItem('biblioteca_user')
  }

  async function login(credentials) {
    const { data } = await authService.login(credentials)
    setSession(data.token, data.user)
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
    const { data } = await authService.me()
    user.value = data
    localStorage.setItem('biblioteca_user', JSON.stringify(data))
  }

  return {
    token, user,
    isAuthenticated, isAdmin, isBibliotecario,
    setSession, clearSession, login, logout, fetchMe,
  }
})
