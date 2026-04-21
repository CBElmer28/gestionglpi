import { ref, reactive, computed, watch } from 'vue'
import { bookRepository } from '@/repositories/bookRepository'
import { glpiService } from '@/services/glpiService'
import { useToast } from 'vue-toastification'

export function useBookController() {
  const toast   = useToast()
  const books      = ref(null) // paginado
  const genres     = ref([])
  const publishers = ref([])
  const loading    = ref(false)
  const error      = ref(null)

  const filters = reactive({ 
    genre_id: '', 
    status: '', 
    publisher_id: '',
    title: '',
    author: '',
    isbn: ''
  })

  const modal = reactive({
    visible: false,
    type: 'create',   // 'create' | 'edit'
    form: {
      id: null, title: '', author: '', isbn: '', edition: '',
      genre_id: '', publisher_id: '', genre: '', publisher: '',
      status: 'Disponible', synopsis: '',
    },
    errors: {},
    submitting: false,
  })

  const deleteConfirm = reactive({ visible: false, bookId: null, bookTitle: '' })

  const reportModal = reactive({
    visible: false,
    book: null,
    form: { priority: 'Media', description: '', image: null },
    submitting: false,
    errors: {}
  })

  // ── Paginación Local ────────────────────────────────────────────────
  const currentPage = ref(1)
  const itemsPerPage = 10

  // ── Listar ──────────────────────────────────────────────────────────
  async function fetchBooks() {
    loading.value = true
    error.value   = null
    try {
      // Pedimos TODOS para filtrar y paginar localmente (mejor UX y menos carga buscador)
      const res = await bookRepository.getAll({ per_page: 'all' })
      // Si recibimos array directo (Collection) o paginado (data: [])
      books.value = Array.isArray(res) ? { data: res } : res
    } catch (err) {
      error.value = 'Error al cargar los libros.'
      toast.error('No se pudieron cargar los libros.')
    } finally {
      loading.value = false
    }
  }

  // Filtrado reactivo local
  const filteredBooks = computed(() => {
    const data = books.value?.data ?? []
    if (!data.length) return []
    
    return data.filter(book => {
      const title = book.title ?? ''
      const author = book.author ?? ''
      const isbn = book.isbn ?? ''
      
      const matchTitle = !filters.title || title.toLowerCase().includes(filters.title.toLowerCase())
      const matchAuthor = !filters.author || author.toLowerCase().includes(filters.author.toLowerCase())
      const matchIsbn = !filters.isbn || isbn.toLowerCase().includes(filters.isbn.toLowerCase())
      const matchGenre = !filters.genre_id || book.genre_id == filters.genre_id
      const matchPublisher = !filters.publisher_id || book.publisher_id == filters.publisher_id
      const matchStatus = !filters.status || book.status === filters.status
      
      return matchTitle && matchAuthor && matchIsbn && matchGenre && matchPublisher && matchStatus
    })
  })

  // Paginación local sobre el resultado filtrado
  const paginatedBooks = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return filteredBooks.value.slice(start, start + itemsPerPage)
  })

  const totalPages = computed(() => Math.ceil(filteredBooks.value.length / itemsPerPage))

  // Al cambiar filtros, volvemos a la página 1
  watch(filters, () => {
    currentPage.value = 1
  }, { deep: true })

  async function fetchMasters() {
    try {
      const [resG, resP] = await Promise.all([
        glpiService.listGenres(),
        glpiService.listPublishers()
      ])
      genres.value     = [{ id: '', name: 'Cualquier género' }, ...resG.data]
      publishers.value = [{ id: '', name: 'Cualquier editorial' }, ...resP.data]
    } catch (err) {
      console.error('Error fetching masters:', err)
    }
  }

  // ── Abrir modal crear ────────────────────────────────────────────────
  function openCreate() {
    modal.type    = 'create'
    modal.errors  = {}
    Object.assign(modal.form, {
      id: null, title: '', author: '', isbn: '', edition: '',
      genre_id: '', publisher_id: '', genre: '', publisher: '',
      status: 'Disponible', synopsis: '',
    })
    modal.visible = true
  }

  // ── Abrir modal editar ───────────────────────────────────────────────
  function openEdit(book) {
    modal.type   = 'edit'
    modal.errors = {}
    Object.assign(modal.form, {
      id:           book.id,
      title:        book.title,
      author:       book.author,
      isbn:         book.isbn,
      edition:      book.edition || '',
      genre:        book.genre || '',
      publisher:    book.publisher || '',
      genre_id:     book.genre_id || '',
      publisher_id: book.publisher_id || '',
      status:       book.status || 'Disponible',
      synopsis:     book.synopsis || '',
    })
    modal.visible = true
  }

  // ── Guardar (crear / editar) ─────────────────────────────────────────
  async function saveBook() {
    modal.errors    = {}
    modal.submitting = true
    try {
      if (modal.type === 'create') {
        await bookRepository.create(modal.form)
        toast.success('Libro creado correctamente.')
      } else {
        await bookRepository.update(modal.form.id, modal.form)
        toast.success('Libro actualizado correctamente.')
      }
      modal.visible = false
      await fetchBooks()
    } catch (err) {
      if (err.response?.status === 422) {
        modal.errors = err.response.data.errors || {}
      } else {
        toast.error(err.response?.data?.message || 'Error al guardar el libro.')
      }
    } finally {
      modal.submitting = false
    }
  }

  // ── Confirmar eliminación ────────────────────────────────────────────
  function confirmDelete(book) {
    deleteConfirm.bookId    = book.id
    deleteConfirm.bookTitle = book.title
    deleteConfirm.visible   = true
  }

  async function deleteBook() {
    try {
      await bookRepository.delete(deleteConfirm.bookId)
      toast.success('Libro eliminado correctamente.')
      deleteConfirm.visible = false
      await fetchBooks()
    } catch (err) {
      toast.error('No se pudo eliminar el libro.')
    }
  }

  // ── Reportar Incidencia ───────────────────────────────────────────────
  function openReportModal(book) {
    reportModal.book = book
    // Si el libro está en mantenimiento, cargar datos del reporte existente (solo lectura)
    if (book.status === 'Mantenimiento' && book.latest_report) {
      reportModal.form = { 
        priority: book.latest_report.priority || 'Media', 
        description: book.latest_report.description || '', 
        image: null 
      }
    } else {
      reportModal.form = { priority: 'Media', description: '', image: null }
    }
    reportModal.errors = {}
    reportModal.visible = true
  }

  async function submitReport() {
    reportModal.submitting = true
    reportModal.errors = {}
    
    const formData = new FormData()
    formData.append('book_id', reportModal.book.id)
    formData.append('priority', reportModal.form.priority)
    formData.append('description', reportModal.form.description)
    formData.append('technician_login', 'soporte_biblioteca')
    if (reportModal.form.image) {
      formData.append('image', reportModal.form.image)
    }

    try {
      await glpiService.createReport(formData)
      toast.success('Gracias por reportar esta incidencia, por favor acerquese a devolver el libro para ayudarlo con su problema')
      reportModal.visible = false
      await fetchBooks()
    } catch (err) {
      if (err.response?.status === 422) {
        reportModal.errors = err.response.data.errors || {}
      } else {
        toast.error('Error al reportar la incidencia.')
      }
    } finally {
      reportModal.submitting = false
    }
  }

  return {
    books, filteredBooks, paginatedBooks, currentPage, totalPages,
    genres, publishers, loading, error, filters,
    modal, deleteConfirm, reportModal,
    fetchBooks, fetchMasters, openCreate, openEdit, saveBook,
    confirmDelete, deleteBook, openReportModal, submitReport,
  }
}
