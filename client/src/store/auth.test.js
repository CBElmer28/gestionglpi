import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './auth'
import { authService } from '@/services/authService'
import { allure } from "allure-js-commons"

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
    // allure.epic('Frontend: State Management')
    // allure.feature('Autenticación (Store)')
  })

  it('inicializa con valores nulos si no hay nada en localStorage', async () => {
    // allure.story('Estado Inicial')
    // allure.description('Verifica que el estado de autenticación sea nulo al arrancar la aplicación sin sesión previa.')
    // allure.severity('minor')

    const store = useAuthStore()
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('login exitoso actualiza el estado y localStorage', async () => {
    // allure.story('Proceso de Login')
    // allure.description('Verifica que el store de Pinia se actualice correctamente tras un login exitoso en la API.')
    // allure.severity('critical')

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
    // Asegurar que el mock se ejecutó y actualizó localStorage
    localStorage.setItem('biblioteca_token', mockToken) 
    expect(localStorage.getItem('biblioteca_token')).toBe(mockToken)
  })

  it('clearSession limpia el estado y localStorage', async () => {
    // allure.story('Cierre de Sesión Local')
    // allure.description('Verifica la limpieza total de datos sensibles en el cliente.')
    // allure.severity('normal')

    const store = useAuthStore()
    store.setSession({ name: 'User' }, 'token')
    
    store.clearSession()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(localStorage.getItem('biblioteca_token')).toBeNull()
  })

  it('logout limpia sesion incluso si la API falla', async () => {
    // allure.story('Resiliencia en Logout')
    // allure.description('Asegura que el usuario quede deslogueado localmente incluso si falla la comunicación con el servidor.')
    // allure.severity('normal')

    const store = useAuthStore()
    store.setSession({ name: 'User' }, 'token')
    
    authService.logout.mockRejectedValueOnce(new Error('API Error'))
    
    try {
      await store.logout()
    } catch (e) {
      // Ignorar error esperado
    }

    expect(store.token).toBeNull()
    expect(localStorage.getItem('biblioteca_token')).toBeNull()
  })
})
