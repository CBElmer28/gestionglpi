import { ref, reactive } from 'vue'
import { scanRepository } from '@/repositories/scanRepository'
import { loanRepository } from '@/repositories/loanRepository'
import { useToast } from 'vue-toastification'

export function useScanController() {
  const toast   = useToast()
  const scans   = ref([])
  const loading = ref(false)
  const stats   = reactive({ today: 0, new_today: 0, existing_today: 0, available: 0 })

  const detail = reactive({
    visible: false,
    scan: null,
    form: { user_name: '', loan_date: '', return_date: '' },
    errors: {},
    submitting: false,
  })

  async function fetchScans(silent = false) {
    if (!silent) loading.value = true
    try {
      const res = await scanRepository.getAll()
      scans.value = res.data ?? []
      Object.assign(stats, res.stats ?? {})
    } catch {
      if (!silent) toast.error('No se pudieron cargar los libros escaneados.')
    } finally {
      loading.value = false
    }
  }

  function openDetail(scan) {
    const today = new Date()
    const back  = new Date()
    back.setDate(back.getDate() + 7)
    detail.scan   = scan
    detail.errors = {}
    detail.form   = {
      user_name:   '',
      loan_date:   today.toISOString().split('T')[0],
      return_date: back.toISOString().split('T')[0],
    }
    detail.visible = true
  }

  function closeDetail() {
    detail.visible = false
  }

  async function lendBook() {
    if (!detail.scan?.book_id) return
    detail.errors     = {}
    detail.submitting = true
    try {
      await loanRepository.create({
        book_id:     detail.scan.book_id,
        user_name:   detail.form.user_name,
        loan_date:   detail.form.loan_date,
        return_date: detail.form.return_date,
      })
      toast.success('Préstamo registrado correctamente.')
      detail.visible = false
      await fetchScans(true)
    } catch (err) {
      if (err.response?.status === 422) {
        detail.errors = err.response.data.errors || {}
      } else {
        toast.error(err.response?.data?.message || 'No se pudo registrar el préstamo.')
      }
    } finally {
      detail.submitting = false
    }
  }

  return { scans, stats, loading, detail, fetchScans, openDetail, closeDetail, lendBook }
}