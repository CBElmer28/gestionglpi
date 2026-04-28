import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useBookController } from '@/controllers/bookController'
import { bookRepository } from '@/repositories/bookRepository'
import { glpiService } from '@/services/glpiService'
import { useToast } from 'vue-toastification'

const mockToast = { success: vi.fn(), error: vi.fn() }

vi.mock('@/repositories/bookRepository', () => ({
  bookRepository: {
    create:  vi.fn(),
    update:  vi.fn(),
    delete:  vi.fn(),
    getAll:  vi.fn(() => Promise.resolve({ data: [] }))
  }
}))

vi.mock('@/services/glpiService', () => ({
  glpiService: {
    listGenres:     vi.fn(() => Promise.resolve({ data: [] })),
    listPublishers: vi.fn(() => Promise.resolve({ data: [] })),
    createReport:   vi.fn()
  }
}))

vi.mock('vue-toastification', () => ({
  useToast: vi.fn(() => mockToast)
}))

// ─────────────────────────────────────────────────────────────────────────────
// CAJA BLANCA — saveBook() — los 4 caminos originales (V(G) = 4)
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - White Box Testing (saveBook)', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Camino 1: Creación Exitosa (CREATE path)', async () => {
    controller.openCreate()
    controller.modal.form = { title: 'Nuevo Libro', author: 'Autor Test' }
    bookRepository.create.mockResolvedValueOnce({ data: { id: 1 } })

    await controller.saveBook()

    expect(bookRepository.create).toHaveBeenCalled()
    expect(mockToast.success).toHaveBeenCalledWith('Libro creado correctamente.')
    expect(controller.modal.visible).toBe(false)
    expect(controller.modal.submitting).toBe(false)
  })

  it('Camino 2: Edición Exitosa (UPDATE path)', async () => {
    controller.openEdit({ id: 1, title: 'Original' })
    controller.modal.form.title = 'Libro Editado'
    bookRepository.update.mockResolvedValueOnce({ data: { id: 1 } })

    await controller.saveBook()

    expect(bookRepository.update).toHaveBeenCalled()
    expect(mockToast.success).toHaveBeenCalledWith('Libro actualizado correctamente.')
    expect(controller.modal.visible).toBe(false)
  })

  it('Camino 3: Error de Validación (422 path)', async () => {
    controller.openCreate()
    bookRepository.create.mockRejectedValueOnce({
      response: { status: 422, data: { errors: { title: ['Error'] } } }
    })

    await controller.saveBook()

    expect(controller.modal.errors.title).toBeDefined()
    expect(controller.modal.visible).toBe(true)
    expect(controller.modal.submitting).toBe(false)
  })

  it('Camino 4: Error General de Servidor (Fallback path)', async () => {
    controller.openCreate()
    bookRepository.create.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'INTERNAL SERVER ERROR' } }
    })

    await controller.saveBook()

    expect(mockToast.error).toHaveBeenCalledWith('INTERNAL SERVER ERROR')
    expect(controller.modal.visible).toBe(true)
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — fetchBooks()
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - fetchBooks()', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Branch: respuesta paginada (objeto con .data)', async () => {
    const paginado = { data: [{ id: 1, title: 'A' }], total: 1 }
    bookRepository.getAll.mockResolvedValueOnce(paginado)

    await controller.fetchBooks()

    expect(controller.books.value).toEqual(paginado)
    expect(controller.loading.value).toBe(false)
  })

  it('Branch: respuesta array directo → envuelve en { data: [] }', async () => {
    const arr = [{ id: 2, title: 'B' }]
    bookRepository.getAll.mockResolvedValueOnce(arr)

    await controller.fetchBooks()

    expect(controller.books.value).toEqual({ data: arr })
  })

  it('Branch: error en fetchBooks → muestra toast y asigna error', async () => {
    bookRepository.getAll.mockRejectedValueOnce(new Error('Network Error'))

    await controller.fetchBooks()

    expect(controller.error.value).toBe('Error al cargar los libros.')
    expect(mockToast.error).toHaveBeenCalledWith('No se pudieron cargar los libros.')
    expect(controller.loading.value).toBe(false)
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — filteredBooks computed
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - filteredBooks computed', () => {
  let controller

  const BOOKS = [
    { id: 1, title: 'Laravel Avanzado', author: 'Taylor',  isbn: '111', genre_id: 1, publisher_id: 1, status: 'Disponible' },
    { id: 2, title: 'Vue Moderno',       author: 'Evan',    isbn: '222', genre_id: 2, publisher_id: 2, status: 'Prestado'    },
    { id: 3, title: 'PHP Básico',        author: 'Rasmus',  isbn: '333', genre_id: 1, publisher_id: 1, status: 'Disponible' },
  ]

  beforeEach(async () => {
    vi.clearAllMocks()
    controller = useBookController()
    bookRepository.getAll.mockResolvedValueOnce({ data: BOOKS })
    await controller.fetchBooks()
  })

  it('Branch: sin filtros → retorna todos los libros', () => {
    expect(controller.filteredBooks.value).toHaveLength(3)
  })

  it('Branch: books.value sin data → retorna []', () => {
    controller.books.value = null
    expect(controller.filteredBooks.value).toHaveLength(0)
  })

  it('Branch: data vacío → retorna []', () => {
    controller.books.value = { data: [] }
    expect(controller.filteredBooks.value).toHaveLength(0)
  })

  it('Branch: filtro title activo', () => {
    controller.filters.title = 'laravel'
    expect(controller.filteredBooks.value).toHaveLength(1)
    expect(controller.filteredBooks.value[0].id).toBe(1)
  })

  it('Branch: filtro author activo', () => {
    controller.filters.author = 'evan'
    expect(controller.filteredBooks.value).toHaveLength(1)
    expect(controller.filteredBooks.value[0].id).toBe(2)
  })

  it('Branch: filtro isbn activo', () => {
    controller.filters.isbn = '333'
    expect(controller.filteredBooks.value).toHaveLength(1)
    expect(controller.filteredBooks.value[0].id).toBe(3)
  })

  it('Branch: filtro genre_id activo', () => {
    controller.filters.genre_id = 2
    expect(controller.filteredBooks.value).toHaveLength(1)
    expect(controller.filteredBooks.value[0].id).toBe(2)
  })

  it('Branch: filtro publisher_id activo', () => {
    controller.filters.publisher_id = 2
    expect(controller.filteredBooks.value).toHaveLength(1)
  })

  it('Branch: filtro status activo', () => {
    controller.filters.status = 'Prestado'
    expect(controller.filteredBooks.value).toHaveLength(1)
    expect(controller.filteredBooks.value[0].id).toBe(2)
  })

  it('Branch: múltiples filtros combinados', () => {
    controller.filters.genre_id = 1
    controller.filters.status   = 'Disponible'
    expect(controller.filteredBooks.value).toHaveLength(2)
  })

  it('Branch: filtro sin coincidencias → retorna []', () => {
    controller.filters.title = 'XYZ_inexistente'
    expect(controller.filteredBooks.value).toHaveLength(0)
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — fetchMasters()
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - fetchMasters()', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Branch: éxito → carga géneros y editoriales', async () => {
    glpiService.listGenres.mockResolvedValueOnce({ data: [{ id: 1, name: 'Ficción' }] })
    glpiService.listPublishers.mockResolvedValueOnce({ data: [{ id: 1, name: 'Planeta' }] })

    await controller.fetchMasters()

    // El primer elemento es el placeholder "Cualquier género / editorial"
    expect(controller.genres.value).toHaveLength(2)
    expect(controller.genres.value[0].name).toBe('Cualquier género')
    expect(controller.publishers.value).toHaveLength(2)
    expect(controller.publishers.value[0].name).toBe('Cualquier editorial')
  })

  it('Branch: error → no lanza excepción (catch silencioso)', async () => {
    glpiService.listGenres.mockRejectedValueOnce(new Error('GLPI offline'))

    await expect(controller.fetchMasters()).resolves.not.toThrow()
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — openEdit() — campos opcionales con || ''
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - openEdit() branches de campos opcionales', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Branch: libro completo → todos los campos se asignan', () => {
    controller.openEdit({
      id: 5, title: 'T', author: 'A', isbn: '123',
      edition: '2nd', genre: 'Sci-Fi', publisher: 'Planeta',
      genre_id: 3, publisher_id: 7, status: 'Prestado', synopsis: 'Resumen'
    })
    expect(controller.modal.form.edition).toBe('2nd')
    expect(controller.modal.form.genre).toBe('Sci-Fi')
    expect(controller.modal.form.status).toBe('Prestado')
    expect(controller.modal.type).toBe('edit')
    expect(controller.modal.visible).toBe(true)
  })

  it('Branch: libro sin campos opcionales → usa string vacío como fallback', () => {
    controller.openEdit({ id: 6, title: 'T2', author: 'A2', isbn: '456' })
    expect(controller.modal.form.edition).toBe('')
    expect(controller.modal.form.genre).toBe('')
    expect(controller.modal.form.publisher).toBe('')
    expect(controller.modal.form.status).toBe('Disponible')
    expect(controller.modal.form.synopsis).toBe('')
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — saveBook() ramas de error adicionales
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - saveBook() branches de error adicionales', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Branch: error 422 sin campo errors → asigna {} como fallback', async () => {
    controller.openCreate()
    bookRepository.create.mockRejectedValueOnce({
      response: { status: 422, data: {} } // sin .errors
    })

    await controller.saveBook()

    expect(controller.modal.errors).toEqual({})
  })

  it('Branch: error genérico sin data.message → usa mensaje genérico', async () => {
    controller.openCreate()
    bookRepository.create.mockRejectedValueOnce({
      response: { status: 503 } // sin data.message
    })

    await controller.saveBook()

    expect(mockToast.error).toHaveBeenCalledWith('Error al guardar el libro.')
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — confirmDelete() + deleteBook()
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - confirmDelete() y deleteBook()', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
    bookRepository.getAll.mockResolvedValue({ data: [] })
  })

  it('Branch: confirmDelete → abre el diálogo con los datos del libro', () => {
    controller.confirmDelete({ id: 9, title: 'Libro a Borrar' })

    expect(controller.deleteConfirm.visible).toBe(true)
    expect(controller.deleteConfirm.bookId).toBe(9)
    expect(controller.deleteConfirm.bookTitle).toBe('Libro a Borrar')
  })

  it('Branch: deleteBook exitoso → muestra toast y cierra confirmación', async () => {
    controller.confirmDelete({ id: 9, title: 'Libro a Borrar' })
    bookRepository.delete.mockResolvedValueOnce({})

    await controller.deleteBook()

    expect(bookRepository.delete).toHaveBeenCalledWith(9)
    expect(mockToast.success).toHaveBeenCalledWith('Libro eliminado correctamente.')
    expect(controller.deleteConfirm.visible).toBe(false)
  })

  it('Branch: deleteBook con error → muestra toast de error', async () => {
    controller.confirmDelete({ id: 9, title: 'Libro a Borrar' })
    bookRepository.delete.mockRejectedValueOnce(new Error('fail'))

    await controller.deleteBook()

    expect(mockToast.error).toHaveBeenCalledWith('No se pudo eliminar el libro.')
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — openReportModal()
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - openReportModal() branches', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
  })

  it('Branch: libro en Mantenimiento CON latest_report → carga datos del reporte', () => {
    const book = {
      id: 1, status: 'Mantenimiento',
      latest_report: { priority: 'Alta', description: 'Páginas rotas' }
    }
    controller.openReportModal(book)

    expect(controller.reportModal.form.priority).toBe('Alta')
    expect(controller.reportModal.form.description).toBe('Páginas rotas')
    expect(controller.reportModal.visible).toBe(true)
  })

  it('Branch: libro en Mantenimiento SIN latest_report → formulario vacío', () => {
    const book = { id: 2, status: 'Mantenimiento', latest_report: null }
    controller.openReportModal(book)

    expect(controller.reportModal.form.priority).toBe('Media')
    expect(controller.reportModal.form.description).toBe('')
  })

  it('Branch: libro Disponible → formulario vacío', () => {
    const book = { id: 3, status: 'Disponible' }
    controller.openReportModal(book)

    expect(controller.reportModal.form.priority).toBe('Media')
    expect(controller.reportModal.form.description).toBe('')
    expect(controller.reportModal.book).toStrictEqual(book)
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// BRANCHES — submitReport()
// ─────────────────────────────────────────────────────────────────────────────
describe('bookController - submitReport() branches', () => {
  let controller

  beforeEach(() => {
    vi.clearAllMocks()
    controller = useBookController()
    bookRepository.getAll.mockResolvedValue({ data: [] })
    // Abrir modal con un libro de prueba
    controller.openReportModal({ id: 10, status: 'Disponible' })
  })

  it('Branch: sin imagen → FormData NO incluye imagen', async () => {
    glpiService.createReport.mockResolvedValueOnce({})
    controller.reportModal.form.image = null

    const appendSpy = vi.spyOn(FormData.prototype, 'append')
    await controller.submitReport()

    const calls = appendSpy.mock.calls.map(c => c[0])
    expect(calls).not.toContain('image')
    expect(mockToast.success).toHaveBeenCalled()
    expect(controller.reportModal.visible).toBe(false)
  })

  it('Branch: con imagen → FormData incluye imagen', async () => {
    glpiService.createReport.mockResolvedValueOnce({})
    const fakeFile = new File(['contenido'], 'foto.jpg', { type: 'image/jpeg' })
    controller.reportModal.form.image = fakeFile

    const appendSpy = vi.spyOn(FormData.prototype, 'append')
    await controller.submitReport()

    const calls = appendSpy.mock.calls.map(c => c[0])
    expect(calls).toContain('image')
  })

  it('Branch: error 422 en submitReport → asigna errores al reportModal', async () => {
    glpiService.createReport.mockRejectedValueOnce({
      response: { status: 422, data: { errors: { description: ['Requerido'] } } }
    })

    await controller.submitReport()

    expect(controller.reportModal.errors).toHaveProperty('description')
    expect(controller.reportModal.submitting).toBe(false)
  })

  it('Branch: error 422 sin .errors → asigna {} como fallback', async () => {
    glpiService.createReport.mockRejectedValueOnce({
      response: { status: 422, data: {} }
    })

    await controller.submitReport()

    expect(controller.reportModal.errors).toEqual({})
  })

  it('Branch: error genérico en submitReport → toast de error', async () => {
    glpiService.createReport.mockRejectedValueOnce({
      response: { status: 500 }
    })

    await controller.submitReport()

    expect(mockToast.error).toHaveBeenCalledWith('Error al reportar la incidencia.')
    expect(controller.reportModal.submitting).toBe(false)
  })
})
