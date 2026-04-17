import { describe, it, expect, vi } from 'vitest'
import { authService } from './authService'
import api from './api'

// Mock del objeto api (axios)
vi.mock('./api', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  }
}))

describe('authService', () => {
  it('login llama al endpoint correcto con las credenciales', async () => {
    const credentials = { email: 'test@test.com', password: 'password123' }
    api.post.mockResolvedValueOnce({ data: { token: '123', user: {} } })

    await authService.login(credentials)

    expect(api.post).toHaveBeenCalledWith('/auth/login', credentials)
  })

  it('logout llama al endpoint de logout', async () => {
    api.post.mockResolvedValueOnce({})
    await authService.logout()
    expect(api.post).toHaveBeenCalledWith('/auth/logout')
  })

  it('me llama al endpoint /auth/me', async () => {
    api.get.mockResolvedValueOnce({ data: { name: 'Admin' } })
    await authService.me()
    expect(api.get).toHaveBeenCalledWith('/auth/me')
  })
})
