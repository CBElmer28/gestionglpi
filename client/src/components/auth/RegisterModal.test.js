import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import RegisterModal from './RegisterModal.vue'
import { useAuthStore } from '@/store/auth'
import { pushMock } from '@/tests/setup'

// SweetAlert2 no debe disparar modales reales en tests
vi.mock('sweetalert2', () => ({
  default: {
    fire: vi.fn().mockResolvedValue({ isConfirmed: true })
  }
}))

/**
 * Monta el componente con la pinia de testing y la prop isOpen.
 */
function mountComponent(isOpen = true) {
  const wrapper = mount(RegisterModal, {
    props: { isOpen },
    global: {
      plugins: [
        createTestingPinia({
          createSpy: vi.fn,
          stubActions: false,
        })
      ],
      stubs: {
        // FontAwesome no está disponible en el entorno de test
        FontAwesomeIcon: { template: '<span />' }
      }
    }
  })
  const auth = useAuthStore()
  return { wrapper, auth }
}

describe('RegisterModal.vue', () => {

  // ─────────────────────────────────────────────────
  // BRANCH 1: v-if="isOpen" — visibilidad del modal
  // ─────────────────────────────────────────────────
  describe('Branch: visibilidad del modal (isOpen)', () => {
    it('NO renderiza el modal cuando isOpen=false', () => {
      const { wrapper } = mountComponent(false)
      expect(wrapper.find('.modal-backdrop').exists()).toBe(false)
    })

    it('SÍ renderiza el modal cuando isOpen=true', () => {
      const { wrapper } = mountComponent(true)
      expect(wrapper.find('.modal-backdrop').exists()).toBe(true)
      expect(wrapper.find('h3').text()).toBe('Registro de Lector')
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCH 2: cierre del modal
  // ─────────────────────────────────────────────────
  describe('Branch: cierre del modal', () => {
    it('emite "close" al hacer click en el botón Cancelar', async () => {
      const { wrapper } = mountComponent()
      await wrapper.find('.btn-ghost').trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('emite "close" al hacer click en el botón X (modal-close)', async () => {
      const { wrapper } = mountComponent()
      await wrapper.find('.modal-close').trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('limpia el formulario al cerrar', async () => {
      const { wrapper } = mountComponent()
      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('María')
      await wrapper.find('.btn-ghost').trigger('click')
      // Después de close(), el name reactivo vuelve a ''
      expect(wrapper.vm.form?.name ?? '').toBe('')
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCH 3: showPassword toggle
  // ─────────────────────────────────────────────────
  describe('Branch: mostrar / ocultar contraseñas (showPassword)', () => {
    it('los inputs de password empiezan en tipo "password"', () => {
      const { wrapper } = mountComponent()
      const passwordInputs = wrapper.findAll('input[placeholder="Min 6"], input[placeholder="Repite"]')
      passwordInputs.forEach(input => {
        expect(input.attributes('type')).toBe('password')
      })
    })

    it('al marcar "Mostrar contraseñas" los inputs cambian a tipo "text"', async () => {
      const { wrapper } = mountComponent()
      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setValue(true)
      await wrapper.vm.$nextTick()
      const passwordInputs = wrapper.findAll('input[placeholder="Min 6"], input[placeholder="Repite"]')
      passwordInputs.forEach(input => {
        expect(input.attributes('type')).toBe('text')
      })
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCH 4: validateName()
  // ─────────────────────────────────────────────────
  describe('Branch: validateName()', () => {
    it('muestra error si el nombre tiene menos de 3 caracteres', async () => {
      const { wrapper } = mountComponent()
      const nameInput = wrapper.find('input[placeholder="Juan Pérez"]')
      await nameInput.setValue('Al')
      await nameInput.trigger('input')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('.form-error').exists()).toBe(true)
      expect(wrapper.find('.form-error').text()).toContain('corto')
    })

    it('NO muestra error cuando el nombre tiene 3+ caracteres', async () => {
      const { wrapper } = mountComponent()
      const nameInput = wrapper.find('input[placeholder="Juan Pérez"]')
      await nameInput.setValue('Juan')
      await nameInput.trigger('input')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('.form-error').exists()).toBe(false)
    })

    it('filtra caracteres no permitidos del nombre', async () => {
      const { wrapper } = mountComponent()
      const nameInput = wrapper.find('input[placeholder="Juan Pérez"]')
      await nameInput.setValue('Juan123!!')
      await nameInput.trigger('input')
      await wrapper.vm.$nextTick()
      // Solo deben quedar letras y espacios
      expect(wrapper.vm.form.name).toMatch(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]*$/)
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCHES 5-8: guards de validación en handleRegister()
  // ─────────────────────────────────────────────────
  describe('Branch: validaciones del formulario al enviar (handleRegister)', () => {
    it('Guard 1 — bloquea el envío si el nombre es muy corto', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn()

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Al')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(auth.register).not.toHaveBeenCalled()
      expect(wrapper.find('.form-error').text()).toContain('inválido')
    })

    it('Guard 2 — bloquea el envío si el email es inválido', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn()

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('correo-invalido')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(auth.register).not.toHaveBeenCalled()
      expect(wrapper.find('.form-error').text()).toContain('Email inválido')
    })

    it('Guard 3 — bloquea el envío si la contraseña tiene menos de 6 caracteres', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn()

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('123')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(auth.register).not.toHaveBeenCalled()
      expect(wrapper.find('.form-error-summary').text()).toContain('6 caracteres')
    })

    it('Guard 4 — bloquea el envío si las contraseñas no coinciden', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn()

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('secreto123')
      await wrapper.find('input[placeholder="Repite"]').setValue('diferente456')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(auth.register).not.toHaveBeenCalled()
      expect(wrapper.find('.form-error-summary').text()).toContain('No coinciden')
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCH 9: registro exitoso
  // ─────────────────────────────────────────────────
  describe('Branch: registro exitoso', () => {
    it('llama a auth.register y redirige al dashboard', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn().mockResolvedValue({})

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('secreto123')
      await wrapper.find('input[placeholder="Repite"]').setValue('secreto123')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()
      // Esperar promesas pendientes
      await new Promise(r => setTimeout(r, 0))

      expect(auth.register).toHaveBeenCalledWith(
        expect.objectContaining({
          name: 'Juan Pérez',
          email: 'juan@test.com',
          password: 'secreto123',
          password_confirmation: 'secreto123'
        })
      )
      expect(pushMock).toHaveBeenCalledWith('/dashboard')
    })

    it('muestra el spinner mientras loading=true', async () => {
      const { wrapper, auth } = mountComponent()
      // Simular acción lenta
      auth.register = vi.fn(() => new Promise(() => {}))

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('secreto123')
      await wrapper.find('input[placeholder="Repite"]').setValue('secreto123')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.spinner').exists()).toBe(true)
      expect(wrapper.find('[type="submit"]').attributes('disabled')).toBeDefined()
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCH 10: error 422 del backend
  // ─────────────────────────────────────────────────
  describe('Branch: error 422 (errores de validación del backend)', () => {
    it('mapea los errores del backend a los campos del formulario', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn().mockRejectedValue({
        response: {
          status: 422,
          data: {
            errors: {
              email: ['El email ya está en uso.'],
              name:  ['El nombre es requerido.']
            }
          }
        }
      })

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('secreto123')
      await wrapper.find('input[placeholder="Repite"]').setValue('secreto123')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()
      await new Promise(r => setTimeout(r, 0))

      const errorMsgs = wrapper.findAll('.form-error')
      const textos = errorMsgs.map(e => e.text()).join(' ')
      expect(textos).toContain('ya está en uso')
    })
  })

  // ─────────────────────────────────────────────────
  // BRANCH 11: error genérico del servidor (no 422)
  // ─────────────────────────────────────────────────
  describe('Branch: error genérico del servidor (no 422)', () => {
    it('muestra el mensaje del servidor en serverError', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn().mockRejectedValue({
        response: {
          status: 500,
          data: { message: 'Error interno del servidor.' }
        }
      })

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('secreto123')
      await wrapper.find('input[placeholder="Repite"]').setValue('secreto123')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()
      await new Promise(r => setTimeout(r, 0))

      const alert = wrapper.find('.alert-danger')
      expect(alert.exists()).toBe(true)
      expect(alert.text()).toContain('Error interno del servidor.')
    })

    it('muestra mensaje fallback si error no tiene data.message', async () => {
      const { wrapper, auth } = mountComponent()
      auth.register = vi.fn().mockRejectedValue({
        response: { status: 503 }
        // sin data.message
      })

      await wrapper.find('input[placeholder="Juan Pérez"]').setValue('Juan Pérez')
      await wrapper.find('input[type="email"]').setValue('juan@test.com')
      await wrapper.find('input[placeholder="Min 6"]').setValue('secreto123')
      await wrapper.find('input[placeholder="Repite"]').setValue('secreto123')
      await wrapper.find('form').trigger('submit')
      await wrapper.vm.$nextTick()
      await new Promise(r => setTimeout(r, 0))

      const alert = wrapper.find('.alert-danger')
      expect(alert.exists()).toBe(true)
      expect(alert.text()).toContain('Error en el servidor.')
    })
  })
})
