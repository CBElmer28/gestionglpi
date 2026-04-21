import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useBookController } from '@/controllers/bookController'
import { bookRepository } from '@/repositories/bookRepository'
import { useToast } from 'vue-toastification'

// Crear un objeto de toast constante para las aserciones
const mockToast = {
  success: vi.fn(),
  error: vi.fn()
}

// Mock de dependencias
vi.mock('@/repositories/bookRepository', () => ({
  bookRepository: {
    create: vi.fn(),
    update: vi.fn(),
    getAll: vi.fn(() => Promise.resolve({ data: [] }))
  }
}))

vi.mock('@/services/glpiService', () => ({
  glpiService: {
    listGenres: vi.fn(() => Promise.resolve({ data: [] })),
    listPublishers: vi.fn(() => Promise.resolve({ data: [] }))
  }
}))

vi.mock('vue-toastification', () => ({
  useToast: vi.fn(() => mockToast)
}))

describe('bookController - White Box Testing (saveBook)', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Camino 1: Creación Exitosa (CREATE path)', async () => {
    // Inicializar estado
    controller.openCreate()
    controller.modal.form = { title: 'Nuevo Libro', author: 'Autor Test' }
    
    bookRepository.create.mockResolvedValueOnce({ data: { id: 1 } })

    await controller.saveBook()

    // Verificaciones (Nodos 1 -> 2 -> 3 -> 5 -> 10)
    expect(bookRepository.create).toHaveBeenCalled()
    expect(mockToast.success).toHaveBeenCalledWith('Libro creado correctamente.')
    expect(controller.modal.visible).toBe(false)
    expect(controller.modal.submitting).toBe(false)
  })

  it('Camino 2: Edición Exitosa (UPDATE path)', async () => {
    // Inicializar estado
    controller.openEdit({ id: 1, title: 'Original' })
    controller.modal.form.title = 'Libro Editado'
    
    bookRepository.update.mockResolvedValueOnce({ data: { id: 1 } })

    await controller.saveBook()

    // Verificaciones (Nodos 1 -> 2 -> 4 -> 5 -> 10)
    expect(bookRepository.update).toHaveBeenCalled()
    expect(mockToast.success).toHaveBeenCalledWith('Libro actualizado correctamente.')
    expect(controller.modal.visible).toBe(false)
  })

  it('Camino 3: Error de Validación (422 path)', async () => {
    controller.openCreate()
    
    const error422 = {
      response: {
        status: 422,
        data: { errors: { title: ['Error'] } }
      }
    }
    bookRepository.create.mockRejectedValueOnce(error422)

    await controller.saveBook()

    // Verificaciones (Nodos 1 -> 2 -> 3 -> 6 -> 7 -> 8 -> 10)
    expect(controller.modal.errors.title).toBeDefined()
    expect(controller.modal.visible).toBe(true) // Debe permanecer abierto
    expect(controller.modal.submitting).toBe(false)
  })

  it('Camino 4: Error General de Servidor (Fallback path)', async () => {
    controller.openCreate()
    
    const error500 = {
      response: {
        status: 500,
        data: { message: 'INTERNAL SERVER ERROR' }
      }
    }
    bookRepository.create.mockRejectedValueOnce(error500)

    await controller.saveBook()

    // Verificaciones (Nodos 1 -> 2 -> 3 -> 6 -> 7 -> 9 -> 10)
    expect(mockToast.error).toHaveBeenCalledWith('INTERNAL SERVER ERROR')
    expect(controller.modal.visible).toBe(true)
  })
})
