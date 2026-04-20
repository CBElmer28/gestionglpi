import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { navigationGuard } from './index'
import { useAuthStore } from '@/store/auth'

describe('router/index.js (navigationGuard)', () => {
  let authStore

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
  })

  it('redirige a login si la ruta es privada y no hay token', () => {
    const to = { meta: { public: false }, fullPath: '/dashboard' }
    authStore.token = null
    
    const result = navigationGuard(to)
    
    expect(result).toEqual({ name: 'login', query: { redirect: '/dashboard' } })
  })

  it('permite acceso si la ruta es pública', () => {
    const to = { meta: { public: true } }
    authStore.token = null
    
    const result = navigationGuard(to)
    
    expect(result).toBeUndefined()
  })

  it('redirige a dashboard si intenta ir a login estando autenticado', () => {
    const to = { name: 'login', meta: { public: true } }
    authStore.token = 'valid-token'
    
    const result = navigationGuard(to)
    
    expect(result).toEqual({ name: 'dashboard' })
  })

  it('bloquea rutas si el usuario no tiene el permiso requerido', () => {
    const to = { meta: { permission: 'users.manage' } }
    authStore.permissions = []
    authStore.token = 'valid-token'
    
    const result = navigationGuard(to)
    
    expect(result).toEqual({ name: 'not-found' })
  })

  it('permite acceso si el usuario tiene el permiso requerido', () => {
    const to = { meta: { permission: 'users.manage' } }
    authStore.permissions = ['users.manage']
    authStore.token = 'valid-token'
    
    const result = navigationGuard(to)
    
    expect(result).toBeUndefined()
  })
})
