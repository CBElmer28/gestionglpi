import { vi, beforeEach } from 'vitest'
import { config } from '@vue/test-utils'

// 1. Mock de vue-router global completo
export const pushMock = vi.fn()
export const routerMock = {
  beforeEach: vi.fn(),
  afterEach: vi.fn(),
  push: pushMock,
  install: vi.fn(), // Necesario para app.use(router)
}

vi.mock('vue-router', () => ({
  useRouter: vi.fn(() => ({
    push: pushMock
  })),
  useRoute: vi.fn(() => ({
    query: {},
    params: {}
  })),
  createRouter: vi.fn(() => routerMock),
  createWebHistory: vi.fn(),
  createWebHashHistory: vi.fn(),
}))

// 2. Mock de vue-toastification
vi.mock('vue-toastification', () => ({
  useToast: vi.fn(() => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
  })),
  POSITION: {
    TOP_RIGHT: 'top-right'
  }
}))

// 3. Mock de FontAwesomeIcon para evitar errores de renderizado
config.global.stubs = {
  'font-awesome-icon': true
}

// 4. Limpiar mocks antes de cada test
beforeEach(() => {
  vi.clearAllMocks()
  localStorage.clear()
})
