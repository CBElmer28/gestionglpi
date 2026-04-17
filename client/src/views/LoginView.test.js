import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import LoginView from './LoginView.vue'
import { useAuthStore } from '../store/auth'
import { pushMock } from '../tests/setup'

describe('LoginView.vue', () => {
  let wrapper
  let authStore

  beforeEach(() => {
    wrapper = mount(LoginView, {
      global: {
        plugins: [
          createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
          })
        ]
      }
    })
    authStore = useAuthStore()
  })

  it('renderiza correctamente el formulario de login', () => {
    expect(wrapper.find('h2').text()).toContain('Iniciar Sesión')
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
  })

  it('alterna la visibilidad de la contraseña al pulsar el botón de ojo', async () => {
    const passwordInput = wrapper.find('#password')
    const toggleBtn = wrapper.find('.password-toggle')
    
    expect(passwordInput.attributes('type')).toBe('password')
    
    await toggleBtn.trigger('click')
    expect(passwordInput.attributes('type')).toBe('text')
    
    await toggleBtn.trigger('click')
    expect(passwordInput.attributes('type')).toBe('password')
  })

  it('intento de login exitoso redirige al dashboard', async () => {
    // Simular entrada de datos
    await wrapper.find('#email').setValue('admin@biblioteca.com')
    await wrapper.find('#password').setValue('admin123')
    
    // Mock de la acción login
    authStore.login = vi.fn().mockResolvedValue({ role: 'admin' })
    
    // Enviar el formulario
    await wrapper.find('#login-form').trigger('submit.prevent')
    
    expect(authStore.login).toHaveBeenCalledWith({
      email: 'admin@biblioteca.com',
      password: 'admin123'
    })
    
    expect(pushMock).toHaveBeenCalledWith('/dashboard')
  })

  it('maneja fallos de servidor correctamente', async () => {
    authStore.login = vi.fn().mockRejectedValue({
      response: { data: { message: 'Credenciales inválidas' } }
    })
    
    await wrapper.find('#email').setValue('wrong@test.com')
    await wrapper.find('#password').setValue('wrong')
    await wrapper.find('#login-form').trigger('submit.prevent')
    
    await wrapper.vm.$nextTick()
    
    const alert = wrapper.find('.login-error-alert')
    expect(alert.exists()).toBe(true)
    expect(alert.text()).toContain('Credenciales inválidas')
  })
})
