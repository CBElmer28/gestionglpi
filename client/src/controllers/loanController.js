import { ref, reactive } from 'vue'
import { loanRepository } from '@/repositories/loanRepository'
import { bookRepository } from '@/repositories/bookRepository'
import { userRepository } from '@/repositories/userRepository'
import { glpiService }    from '@/services/glpiService'
import { useToast }       from 'vue-toastification'

export function useLoanController() {
  const toast   = useToast()
  const loans   = ref(null)
  const books   = ref([])
  const lectors = ref([])
  const loading = ref(false)

  const filters = reactive({ status: '', user_name: '' })

  const modal = reactive({
    visible:     false,
    form: {
      book_id:     '',
      user_id:     null,
      user_name:   '',
      loan_date:   new Date().toISOString().split('T')[0],
      return_date: '',
    },
    errors:      {},
    submitting:  false,
  })

  const returnConfirm = reactive({ visible: false, loanId: null })

  // Modal de Reporte de Incidencias (Específico para lector/admin sobre un libro)
  const reportModal = reactive({
    visible: false,
    loan: null,
    submitting: false,
    form: {
      priority: 'Media',
      description: '',
      image: null
    },
    errors: {}
  })

  async function fetchLoans(page = 1, extraParams = {}) {
    loading.value = true
    try {
      loans.value = await loanRepository.getAll({ ...filters, ...extraParams, page })
    } catch {
      toast.error('Error al cargar los préstamos.')
    } finally {
      loading.value = false
    }
  }

  async function fetchAvailableBooks() {
    try {
      const data  = await bookRepository.getAll({ status: 'Disponible' })
      books.value = data.data ?? data
    } catch {
      books.value = []
    }
  }
  
  async function fetchLectors() {
    try {
      const allUsers = await userRepository.getAll()
      // Filtramos solo los que son rol lector
      lectors.value = allUsers.filter(u => 
        u.role?.slug === 'lector' || 
        u.role === 'lector' || 
        (typeof u.role === 'object' && u.role?.name?.toLowerCase().includes('lector'))
      )
    } catch {
      lectors.value = []
    }
  }

  function openCreate() {
    modal.errors = {}
    const today = new Date().toISOString().split('T')[0]
    const returnDate = new Date()
    returnDate.setDate(returnDate.getDate() + 7)

    modal.form = {
      book_id:     '',
      user_id:     null,
      user_name:   '',
      loan_date:   today,
      return_date: returnDate.toISOString().split('T')[0],
    }
    modal.visible = true
    fetchAvailableBooks()
    fetchLectors()
  }

  async function createLoan() {
    modal.errors     = {}
    modal.submitting = true
    try {
      await loanRepository.create(modal.form)
      toast.success('Préstamo registrado correctamente.')
      modal.visible = false
      await fetchLoans()
    } catch (err) {
      if (err.response?.status === 422) {
        modal.errors = err.response.data.errors || {}
      } else {
        toast.error(err.response?.data?.message || 'Error al registrar el préstamo.')
      }
    } finally {
      modal.submitting = false
    }
  }

  function confirmReturn(loan) {
    returnConfirm.loanId  = loan.id
    returnConfirm.visible = true
  }

  async function returnLoan() {
    try {
      await loanRepository.returnLoan(returnConfirm.loanId)
      toast.success('Devolución registrada correctamente.')
      returnConfirm.visible = false
      await fetchLoans()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al registrar la devolución.')
    }
  }

  // --- Lógica de Reporte ---
  function openReportModal(loan) {
    reportModal.loan = loan
    reportModal.errors = {}
    reportModal.form = { priority: 'Media', description: '', image: null }
    reportModal.visible = true
  }

  async function submitReport() {
    if (!reportModal.loan?.book_id) return
    
    reportModal.submitting = true
    reportModal.errors = {}

    const formData = new FormData()
    formData.append('book_id', reportModal.loan.book_id)
    formData.append('priority', reportModal.form.priority)
    formData.append('description', reportModal.form.description)
    formData.append('technician_login', 'soporte_lectores')
    if (reportModal.form.image) {
      formData.append('image', reportModal.form.image)
    }

    try {
      await glpiService.createReport(formData)
      toast.success('Incidencia reportada correctamente a GLPI.')
      reportModal.visible = false
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
    loans, books, lectors, loading, filters, modal, returnConfirm, reportModal,
    fetchLoans, openCreate, createLoan, confirmReturn, returnLoan, 
    openReportModal, submitReport
  }
}
