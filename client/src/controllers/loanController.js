import { ref, reactive } from 'vue'
import { loanRepository } from '@/repositories/loanRepository'
import { bookRepository } from '@/repositories/bookRepository'
import { useToast } from 'vue-toastification'

export function useLoanController() {
  const toast   = useToast()
  const loans   = ref(null)
  const books   = ref([])
  const loading = ref(false)

  const filters = reactive({ status: '', user_name: '' })

  const modal = reactive({
    visible:     false,
    form: {
      book_id:     '',
      user_name:   '',
      loan_date:   new Date().toISOString().split('T')[0],
      return_date: '',
    },
    errors:      {},
    submitting:  false,
  })

  const returnConfirm = reactive({ visible: false, loanId: null })

  async function fetchLoans(page = 1) {
    loading.value = true
    try {
      loans.value = await loanRepository.getAll({ ...filters, page })
    } catch {
      toast.error('Error al cargar los préstamos.')
    } finally {
      loading.value = false
    }
  }

  async function fetchAvailableBooks() {
    try {
      const data  = await bookRepository.getAll({ status: 'Disponible' })
      // data puede ser paginado o array
      books.value = data.data ?? data
    } catch {
      books.value = []
    }
  }

  function openCreate() {
    modal.errors = {}
    modal.form   = {
      book_id:     '',
      user_name:   '',
      loan_date:   new Date().toISOString().split('T')[0],
      return_date: '',
    }
    modal.visible = true
    fetchAvailableBooks()
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

  return {
    loans, books, loading, filters, modal, returnConfirm,
    fetchLoans, openCreate, createLoan, confirmReturn, returnLoan,
  }
}
