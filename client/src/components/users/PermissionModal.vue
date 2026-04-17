<template>
  <div v-if="visible" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal modal-lg">
      <div class="modal-header">
        <h3 class="modal-title">
          <font-awesome-icon icon="shield-alt" /> Gestionar Permisos del Sistema
        </h3>
        <button class="btn btn-ghost btn-icon" @click="emit('close')">
          <font-awesome-icon icon="times" />
        </button>
      </div>
      
      <div class="modal-body">
        <div v-if="loading" class="spinner-container">
          <div class="spinner spinner-lg"></div>
          <p>Cargando configuración de permisos…</p>
        </div>
        
        <div v-else class="permission-manager">
          <!-- Table of Roles vs Permissions -->
          <div class="table-wrapper">
            <table class="table table-permissions">
              <thead>
                <tr>
                  <th>Permiso</th>
                  <th v-for="role in roles" :key="role.id" class="text-center">
                    {{ role.name }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="perm in permissions" :key="perm.id">
                  <td>
                    <strong>{{ perm.name }}</strong>
                  </td>
                  <td v-for="role in roles" :key="role.id" class="text-center">
                    <input 
                      type="checkbox" 
                      :checked="hasPermission(role, perm.id)"
                      @change="togglePermission(role, perm.id)"
                      :disabled="submitting"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <p class="text-muted mr-auto" style="font-size: .8rem;">
          * Los cambios se aplican automáticamente al marcar/desmarcar.
        </p>
        <button class="btn btn-primary" @click="emit('close')">Cerrar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { roleService } from '@/services/roleService'
import { useToast } from 'vue-toastification'

const props = defineProps({
  visible: Boolean
})
const emit = defineEmits(['close'])

const toast       = useToast()
const loading     = ref(false)
const submitting  = ref(false)
const roles       = ref([])
const permissions = ref([])

async function loadData() {
  loading.value = true
  try {
    const [rolesRes, permsRes] = await Promise.all([
      roleService.getAll(),
      roleService.getPermissions()
    ])
    roles.value       = rolesRes.data
    permissions.value = permsRes.data
  } catch {
    toast.error('No se pudo cargar la configuración de permisos.')
  } finally {
    loading.value = false
  }
}

function hasPermission(role, permId) {
  return role.permissions?.some(p => p.id === permId)
}

async function togglePermission(role, permId) {
  submitting.value = true
  
  // Clonamos hay que usar spread o map para no mutar directamente si fuera reactivo profundo, 
  // pero aquí role ya viene de la lista roles.value
  let currentPermIds = role.permissions.map(p => p.id)
  
  if (currentPermIds.includes(permId)) {
    currentPermIds = currentPermIds.filter(id => id !== permId)
  } else {
    currentPermIds.push(permId)
  }

  try {
    const { data } = await roleService.syncPermissions(role.id, currentPermIds)
    // Actualizamos el objeto local del rol para reflejar el cambio en la UI
    role.permissions = data.role.permissions
    toast.success(`Permisos de ${role.name} actualizados.`)
  } catch {
    toast.error('Error al actualizar permisos.')
  } finally {
    submitting.value = false
  }
}

watch(() => props.visible, (newVal) => {
  if (newVal && !roles.value.length) {
    loadData()
  }
})

onMounted(() => {
  if (props.visible) loadData()
})
</script>

<style scoped>
.modal-lg {
  max-width: 900px !important;
}

.spinner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem;
  gap: 1rem;
  color: var(--c-text-secondary);
}

.table-permissions th {
  background: var(--c-surface-2);
  font-size: .75rem;
  text-transform: uppercase;
  letter-spacing: .05em;
}

.text-center { text-align: center; }

.perm-slug {
  font-size: .7rem;
  color: var(--c-text-muted);
  font-family: monospace;
}

.table-permissions td {
  padding: 1rem .75rem;
}

input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--c-primary);
}

input[type="checkbox"]:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mr-auto { margin-right: auto; }
</style>
