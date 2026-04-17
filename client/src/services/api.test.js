import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import api from './api'
import { useAuthStore } from '@/store/auth'
import { pushMock } from '@/tests/setup'

describe('api.js (Interceptors)', () => {
  let authStore

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
    vi.clearAllMocks()
  })

  it('añade el header de Authorization si el token existe', async () => {
    authStore.token = 'fake-token'
    
    // Obtenemos el interceptor de request
    const interceptor = api.interceptors.request.handlers[0]
    const config = { headers: {} }
    
    const result = interceptor.fulfilled(config)
    
    expect(result.headers.Authorization).toBe('Bearer fake-token')
  })

  it('no añade header si no hay token', async () => {
    authStore.token = null
    const interceptor = api.interceptors.request.handlers[0]
    const config = { headers: {} }
    
    const result = interceptor.fulfilled(config)
    
    expect(result.headers.Authorization).toBeUndefined()
  })

  it('maneja el error 401 limpiando la sesión y redirigiendo', async () => {
    const interceptor = api.interceptors.response.handlers[0]
    const error = {
      response: { status: 401 }
    }
    
    authStore.clearSession = vi.fn()
    
    try {
      await interceptor.rejected(error)
    } catch (e) {
      // Es normal que lance el error después del interceptor
    }
    
    expect(authStore.clearSession).toHaveBeenCalled()
    expect(pushMock).toHaveBeenCalledWith({ name: 'login' })
  })

  it('ignora otros errores que no sean 401', async () => {
    const interceptor = api.interceptors.response.handlers[0]
    const error = {
      response: { status: 500 }
    }
    
    authStore.clearSession = vi.fn()
    
    try {
      await interceptor.rejected(error)
    } catch (e) {
      // ...
    }
    
    expect(authStore.clearSession).not.toHaveBeenCalled()
  })
})
