<template>
  <div class="login-shell">
    <!-- Panel izquierdo decorativo -->
    <div class="login-hero">
      <div class="login-hero-content">
        <div class="login-hero-icon">
          <font-awesome-icon icon="book" />
        </div>
        <h1>Biblioteca Digital</h1>
        <p>Gestiona libros, préstamos y usuarios de forma fácil e integrada con GLPI.</p>

        <div class="login-hero-features">
          <div class="feature-item">
            <font-awesome-icon icon="book" />
            <span>Catálogo completo de libros</span>
          </div>
          <div class="feature-item">
            <font-awesome-icon icon="clipboard-list" />
            <span>Control de préstamos en tiempo real</span>
          </div>
          <div class="feature-item">
            <font-awesome-icon icon="link" />
            <span>Integración con GLPI</span>
          </div>
        </div>
      </div>
      <div class="login-hero-pattern"></div>
    </div>

    <!-- Panel derecho — formulario -->
    <div class="login-form-panel">
      <div class="login-form-card">
        <div class="login-form-header">
          <h2>Iniciar Sesión</h2>
          <p>Ingresa tus credenciales para continuar</p>
        </div>

        <form @submit.prevent="handleLogin" class="login-form" id="login-form">
          <div class="form-group">
            <label class="form-label" for="email">Correo electrónico</label>
            <div class="input-with-icon">
              <span class="input-icon"><font-awesome-icon icon="envelope" /></span>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="form-control"
                :class="{ 'is-error': errors.email }"
                placeholder="admin@biblioteca.com"
                autocomplete="email"
                required
              />
            </div>
            <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Contraseña</label>
            <div class="input-with-icon">
              <span class="input-icon"><font-awesome-icon icon="lock" /></span>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control"
                :class="{ 'is-error': errors.password }"
                placeholder="••••••••"
                autocomplete="current-password"
                required
              />
              <button
                type="button"
                class="password-toggle"
                @click="showPassword = !showPassword"
              >
                <font-awesome-icon :icon="showPassword ? 'eye-slash' : 'eye'" />
              </button>
            </div>
            <span v-if="errors.password" class="form-error">{{ errors.password }}</span>
          </div>

          <div v-if="serverError" class="login-error-alert">
            <font-awesome-icon icon="exclamation-triangle" /> {{ serverError }}
          </div>

          <button
            id="btn-login"
            type="submit"
            class="btn btn-primary btn-lg w-full"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner"></span>
            <span v-else>Ingresar al sistema</span>
          </button>

          <div class="login-demo-hint">
            <strong>Demo:</strong> admin@biblioteca.com / admin123
          </div>
        </form>

        <div class="login-form-footer">
          ¿Aún no tienes cuenta? 
          <button @click="isRegisterModalOpen = true" class="btn-link-primary">Regístrate</button>
        </div>
      </div>

      <p class="login-footer-text">Sistema de Gestión de Biblioteca © {{ year }}</p>
    </div>

    <!-- Modal de Registro -->
    <RegisterModal 
      :is-open="isRegisterModalOpen" 
      @close="isRegisterModalOpen = false" 
    />
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import RegisterModal from '@/components/auth/RegisterModal.vue'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const serverError = ref('')
const loading     = ref(false)
const showPassword = ref(false)
const isRegisterModalOpen = ref(false)
const year = new Date().getFullYear()

async function handleLogin() {
  serverError.value = ''
  errors.email      = ''
  errors.password   = ''

  if (!form.email)    { errors.email    = 'El correo es requerido.'; return }
  if (!form.password) { errors.password = 'La contraseña es requerida.'; return }

  loading.value = true
  try {
    await auth.login({ email: form.email, password: form.password })
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
  } catch (err) {
    serverError.value = err.response?.data?.message || 'Error al iniciar sesión. Verifica tus credenciales.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-shell {
  min-height: 100vh;
  display: flex;
}

.login-hero {
  flex: 1;
  background: linear-gradient(135deg, var(--c-primary-900) 0%, var(--c-primary-700) 60%, var(--c-primary-600) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-12);
  position: relative;
  overflow: hidden;
}

.login-hero-pattern {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 20% 80%, rgba(201,162,39,.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(168,196,224,.1) 0%, transparent 50%);
  pointer-events: none;
}

.login-hero-content {
  position: relative;
  z-index: 1;
  max-width: 420px;
  color: #fff;
}

.login-hero-icon {
  font-size: 4rem;
  margin-bottom: var(--sp-6);
  filter: drop-shadow(0 8px 24px rgba(201,162,39,.4));
}

.login-hero-content h1 {
  font-size: 2.4rem;
  color: #fff;
  margin-bottom: var(--sp-4);
  letter-spacing: -.01em;
}

.login-hero-content p {
  color: rgba(255,255,255,.75);
  font-size: 1.05rem;
  line-height: 1.7;
  margin-bottom: var(--sp-10);
}

.login-hero-features {
  display: flex;
  flex-direction: column;
  gap: var(--sp-3);
}

.feature-item {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  color: rgba(255,255,255,.85);
  font-size: .92rem;
  background: rgba(255,255,255,.06);
  padding: var(--sp-3) var(--sp-4);
  border-radius: var(--radius-md);
  border: 1px solid rgba(255,255,255,.1);
}

/* ── Form panel ── */
.login-form-panel {
  width: 480px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--sp-12) var(--sp-8);
  background: var(--c-bg);
}

.login-form-card {
  width: 100%;
  background: var(--c-surface);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  padding: var(--sp-8);
}

.login-form-footer {
  margin-top: var(--sp-8);
  text-align: center;
  font-size: .9rem;
  color: var(--c-text-secondary);
  border-top: 1px solid var(--c-border);
  padding-top: var(--sp-6);
}

.btn-link-primary {
  background: none;
  border: none;
  padding: 0;
  color: var(--c-primary);
  font-weight: 600;
  font-size: .9rem;
  cursor: pointer;
  transition: color var(--tr-fast);
  font-family: inherit;
}

.btn-link-primary:hover {
  color: var(--c-primary-600);
  text-decoration: underline;
}

.login-form-header {
  margin-bottom: var(--sp-8);
  text-align: center;
}

.login-form-header h2 {
  font-size: 1.7rem;
  color: var(--c-text-primary);
  margin-bottom: var(--sp-2);
}

.login-form-header p {
  color: var(--c-text-secondary);
  font-size: .9rem;
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: var(--sp-3);
  font-size: .95rem;
  pointer-events: none;
  z-index: 1;
}

.input-with-icon .form-control {
  padding-left: 2.6rem;
}

.password-toggle {
  position: absolute;
  right: var(--sp-3);
  font-size: .95rem;
  z-index: 1;
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
  line-height: 1;
}

.form-control.is-error {
  border-color: var(--c-danger);
  box-shadow: 0 0 0 3px rgba(239,68,68,.1);
}

.login-error-alert {
  background: #fef2f2;
  border: 1px solid #fee2e2;
  color: var(--c-danger);
  border-radius: var(--radius-md);
  padding: var(--sp-3) var(--sp-4);
  font-size: .87rem;
  margin-bottom: var(--sp-4);
}

.w-full { width: 100%; }

.login-demo-hint {
  text-align: center;
  margin-top: var(--sp-4);
  font-size: .78rem;
  color: var(--c-text-muted);
  background: var(--c-surface-2);
  border-radius: var(--radius-md);
  padding: var(--sp-3);
}

.login-footer-text {
  margin-top: var(--sp-8);
  font-size: .78rem;
  color: var(--c-text-muted);
  text-align: center;
}

@media (max-width: 900px) {
  .login-shell { flex-direction: column; }
  .login-hero  { min-height: 280px; flex: none; }
  .login-form-panel { width: 100%; }
}
</style>
