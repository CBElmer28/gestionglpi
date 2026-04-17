<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="close">
    <div class="modal register-modal animate-in">
      <button class="modal-close" @click="close" aria-label="Cerrar">
        <font-awesome-icon icon="times" />
      </button>

      <div class="modal-header">
        <div class="modal-icon">
          <font-awesome-icon icon="user-plus" />
        </div>
        <div class="modal-header-text">
          <h3>Registro de Lector</h3>
          <p>Crea tu cuenta para gestionar tus préstamos</p>
        </div>
      </div>

      <div class="modal-body">
        <form @submit.prevent="handleRegister" class="register-form">
          <!-- Nombre Completo -->
          <div class="form-group">
            <label class="form-label">Nombre Completo</label>
            <div class="input-with-icon">
              <span class="input-icon"><font-awesome-icon icon="user" /></span>
              <input
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-error': errors.name }"
                placeholder="Juan Pérez"
                @input="validateName"
                required
              />
            </div>
            <span v-if="errors.name" class="form-error">{{ errors.name }}</span>
          </div>

          <!-- Correo Electrónico -->
          <div class="form-group">
            <label class="form-label">Correo electrónico</label>
            <div class="input-with-icon">
              <span class="input-icon"><font-awesome-icon icon="envelope" /></span>
              <input
                v-model="form.email"
                type="email"
                class="form-control"
                :class="{ 'is-error': errors.email }"
                placeholder="ejemplo@correo.com"
                required
              />
            </div>
            <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
          </div>

          <div class="form-row">
            <!-- Contraseña -->
            <div class="form-group">
              <label class="form-label">Contraseña</label>
              <div class="input-with-icon">
                <span class="input-icon"><font-awesome-icon icon="lock" /></span>
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-error': errors.password }"
                  placeholder="Min 6"
                  required
                />
              </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div class="form-group">
              <label class="form-label">Confirmar</label>
              <div class="input-with-icon">
                <span class="input-icon"><font-awesome-icon icon="check-circle" /></span>
                <input
                  v-model="form.password_confirmation"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-error': errors.password_confirmation }"
                  placeholder="Repite"
                  required
                />
              </div>
            </div>
          </div>
          
          <div v-if="errors.password || errors.password_confirmation" class="form-error-summary">
            {{ errors.password || errors.password_confirmation }}
          </div>

          <div class="password-options">
            <label class="checkbox-container">
              <input type="checkbox" v-model="showPassword">
              <span class="checkmark"></span>
              Mostrar contraseñas
            </label>
          </div>

          <div v-if="serverError" class="alert alert-danger">
            <font-awesome-icon icon="exclamation-triangle" /> {{ serverError }}
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" @click="close">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner"></span>
              <span v-else>Crear mi cuenta</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import Swal from 'sweetalert2'

const props = defineProps({
  isOpen: Boolean
})

const emit = defineEmits(['close'])

const router = useRouter()
const auth   = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const serverError = ref('')
const loading     = ref(false)
const showPassword = ref(false)

function close() {
  emit('close')
  // Limpiar form al cerrar
  form.name = ''
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  Object.keys(errors).forEach(k => errors[k] = '')
}

function sanitize(val) {
  return val.replace(/[<>'";\-\-]/g, '')
}

function validateName() {
  form.name = form.name.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')
  errors.name = form.name.length > 0 && form.name.length < 3 ? 'Nombre muy corto.' : ''
}

async function handleRegister() {
  serverError.value = ''
  Object.keys(errors).forEach(k => errors[k] = '')

  if (form.name.length < 3) { errors.name = 'Nombre inválido.'; return }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.email)) { errors.email = 'Email inválido.'; return }
  if (form.password.length < 6) { errors.password = 'Mínimo 6 caracteres.'; return }
  if (form.password !== form.password_confirmation) { errors.password_confirmation = 'No coinciden.'; return }

  loading.value = true
  const payload = {
    name:     sanitize(form.name),
    email:    sanitize(form.email),
    password: form.password,
    password_confirmation: form.password_confirmation
  }

  try {
    await auth.register(payload)
    close()
    
    await Swal.fire({
      title: '¡Registro Exitoso!',
      text: 'Tu cuenta ha sido creada. Ahora puedes gestionar tus préstamos.',
      icon: 'success',
      confirmButtonText: 'Ir al Dashboard',
      confirmButtonColor: 'var(--c-primary)',
      allowOutsideClick: false
    })

    router.push('/dashboard')
  } catch (err) {
    if (err.response?.status === 422) {
      const backendErrors = err.response.data.errors
      Object.keys(backendErrors).forEach(key => {
        errors[key] = backendErrors[key][0]
      })
    } else {
      serverError.value = err.response?.data?.message || 'Error en el servidor.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-modal {
  max-width: 500px;
  overflow: hidden; /* Para que el header no se salga de los radios */
}

.modal-close {
  position: absolute;
  top: var(--sp-4);
  right: var(--sp-4);
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: var(--c-text-muted);
  transition: all var(--tr-fast);
  z-index: 10;
}

.modal-close:hover {
  background: var(--c-primary-50);
  color: var(--c-primary);
}

.modal-header {
  padding: var(--sp-8) var(--sp-8) var(--sp-4);
  background: linear-gradient(to bottom, var(--c-primary-50) 0%, transparent 100%);
  border-bottom: none;
  display: flex;
  align-items: flex-start;
  gap: var(--sp-4);
}

.modal-icon {
  width: 48px;
  height: 48px;
  background: var(--c-primary);
  color: #fff;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 4px 12px rgba(26,95,163,.2);
  flex-shrink: 0;
}

.modal-header-text h3 {
  font-size: 1.4rem;
  margin-bottom: 2px;
}

.modal-header-text p {
  font-size: .85rem;
  color: var(--c-text-secondary);
}

.modal-body {
  padding: var(--sp-4) var(--sp-8) var(--sp-8);
}

.register-form {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--sp-4);
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: var(--sp-3);
  font-size: .9rem;
  color: var(--c-text-muted);
}

.input-with-icon .form-control {
  padding-left: 2.5rem;
}

.form-error {
  font-size: .75rem;
  color: var(--c-danger);
  margin-top: 4px;
}

.form-error-summary {
  font-size: .75rem;
  color: var(--c-danger);
  text-align: center;
  margin-top: -8px;
}

.password-options {
  margin: var(--sp-1) 0;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  font-size: .85rem;
  color: var(--c-text-secondary);
  cursor: pointer;
  user-select: none;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--sp-3);
  margin-top: var(--sp-4);
}

.alert {
  padding: var(--sp-3);
  border-radius: var(--radius-md);
  font-size: .85rem;
}

.alert-danger {
  background: #fef2f2;
  color: var(--c-danger);
  border: 1px solid #fee2e2;
}

.animate-in {
  animation: modalScaleIn .3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalScaleIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

</style>
