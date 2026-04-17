import { ref, reactive } from 'vue'
import { userRepository } from '@/repositories/userRepository'
import { roleService }    from '@/services/roleService'
import { useToast }       from 'vue-toastification'

export function useUserController() {
  const toast   = useToast()
  const users   = ref([])
  const roles   = ref([])
  const loading = ref(false)

  const modal = reactive({
    visible:     false,
    type:        'create',
    form: {
      id: null, name: '', email: '', password: '',
      password_confirmation: '', role_id: null,
    },
    errors:      {},
    submitting:  false,
  })

  const deleteConfirm = reactive({ visible: false, userId: null, userName: '' })

  async function fetchUsers() {
    loading.value = true
    try {
      users.value = await userRepository.getAll()
    } catch {
      toast.error('Error al cargar los usuarios.')
    } finally {
      loading.value = false
    }
  }

  async function fetchRoles() {
    try {
      const { data } = await roleService.getAll()
      roles.value = data
    } catch {
      toast.error('Error al cargar los roles.')
    }
  }

  function openCreate() {
    modal.type    = 'create'
    modal.errors  = {}
    modal.form    = {
      id: null, name: '', email: '', password: '',
      password_confirmation: '', role_id: roles.value.find(r => r.slug === 'lector')?.id || null,
    }
    modal.visible = true
  }

  function openEdit(user) {
    modal.type    = 'edit'
    modal.errors  = {}
    modal.form    = {
      id:                    user.id,
      name:                  user.name,
      email:                 user.email,
      password:              '',
      password_confirmation: '',
      role_id:               user.role_id,
    }
    modal.visible = true
  }

  async function save() {
    modal.errors     = {}
    modal.submitting = true
    try {
      const payload = { ...modal.form }
      if (modal.type === 'edit' && !payload.password) {
        delete payload.password
        delete payload.password_confirmation
      }

      if (modal.type === 'create') {
        await userRepository.create(payload)
        toast.success('Usuario creado correctamente.')
      } else {
        await userRepository.update(modal.form.id, payload)
        toast.success('Usuario actualizado correctamente.')
      }
      modal.visible = false
      await fetchUsers()
    } catch (err) {
      if (err.response?.status === 422) {
        modal.errors = err.response.data.errors || {}
      } else {
        toast.error(err.response?.data?.message || 'Error al guardar el usuario.')
      }
    } finally {
      modal.submitting = false
    }
  }

  function confirmDelete(user) {
    deleteConfirm.userId   = user.id
    deleteConfirm.userName = user.name
    deleteConfirm.visible  = true
  }

  async function deleteUser() {
    try {
      await userRepository.delete(deleteConfirm.userId)
      toast.success('Usuario eliminado.')
      deleteConfirm.visible = false
      await fetchUsers()
    } catch {
      toast.error('No se pudo eliminar el usuario.')
    }
  }

  return {
    users, roles, loading, modal, deleteConfirm,
    fetchUsers, fetchRoles, openCreate, openEdit, save, confirmDelete, deleteUser,
  }
}
