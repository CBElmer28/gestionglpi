import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './auth'
import { authService } from '@/services/authService'

// Mock de authService
vi.mock('@/services/authService', () => ({
  authService: {
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn()
  }
}))

describe('authStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('inicializa con valores nulos si no hay nada en localStorage', () => {
    const store = useAuthStore()
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('login exitoso actualiza el estado y localStorage', async () => {
    const store = useAuthStore()
    const mockUser = { id: 1, name: 'Admin', role: { slug: 'admin' } }
    const mockToken = 'secret-token'
    
    authService.login.mockResolvedValueOnce({ 
      data: { token: mockToken, user: mockUser } 
    })

    await store.login({ email: 'test@test.com', password: 'password' })

    expect(store.token).toBe(mockToken)
    expect(store.user).toEqual(mockUser)
    expect(store.isAuthenticated).toBe(true)
    expect(store.isAdmin).toBe(true)
    expect(localStorage.getItem('biblioteca_token')).toBe(mockToken)
  })

  it('clearSession limpia el estado y localStorage', () => {
    const store = useAuthStore()
    store.setSession({ name: 'User' }, 'token')
    
    store.clearSession()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(localStorage.getItem('biblioteca_token')).toBeNull()
  })

  it('logout limpia sesion incluso si la API falla', async () => {
    const store = useAuthStore()
    store.setSession({ name: 'User' }, 'token')
    
    authService.logout.mockRejectedValueOnce(new Error('API Error'))
    
    // El error se lanza después del finally, por lo que debemos capturarlo
    try {
      await store.logout()
    } catch (e) {
      // Ignorar error esperado
    }

    expect(store.token).toBeNull()
    expect(localStorage.getItem('biblioteca_token')).toBeNull()
  })

  it('fetchMe actualiza el usuario en el estado y localStorage', async () => {
    const store = useAuthStore()
    const mockUser = { id: 1, name: 'Updated User' }
    authService.me.mockResolvedValueOnce({ data: mockUser })

    await store.fetchMe()

    expect(store.user).toEqual(mockUser)
    expect(localStorage.getItem('biblioteca_user')).toContain('Updated User')
  })
})
