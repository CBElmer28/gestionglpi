<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="tags" /> Géneros</h1>
        <p>Listado de géneros sincronizados desde GLPI.</p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-ghost" @click="handleSync" :disabled="syncing">
          <font-awesome-icon :icon="syncing ? 'sync' : 'sync'" :spin="syncing" />
          {{ syncing ? 'Sincronizando...' : 'Sincronizar con GLPI' }}
        </button>
      </div>
    </div>

    <div class="card">
      <div v-if="loading" class="spinner-overlay">
        <div class="spinner spinner-lg"></div>
      </div>

      <div v-else-if="!genres.length" class="empty-state">
        <div class="empty-state-icon">
          <font-awesome-icon icon="tags" />
        </div>
        <h3>No hay géneros registrados</h3>
        <p>Pulsa "Sincronizar con GLPI" en la sección de Libros para importar los datos.</p>
      </div>

      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID GLPI</th>
              <th>Nombre del Género</th>
              <th>Fecha de Sincronización</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="g in genres" :key="g.id">
              <td style="color:var(--c-text-muted);font-size:.8rem;font-family:monospace">
                #{{ g.glpi_id }}
              </td>
              <td><strong>{{ g.name }}</strong></td>
              <td style="color:var(--c-text-secondary);font-size:.82rem">
                {{ formatDate(g.updated_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { glpiService } from '@/services/glpiService'
import { useToast } from 'vue-toastification'

const toast = useToast()
const genres = ref([])
const loading = ref(false)
const syncing = ref(false)

async function fetchGenres() {
  loading.value = true
  try {
    const { data } = await glpiService.listGenres()
    genres.value = data
  } catch (err) {
    console.error('Error fetching genres:', err)
  } finally {
    loading.value = false
  }
}

async function handleSync() {
  syncing.value = true
  try {
    const { data } = await glpiService.syncGenres()
    toast.success(data.message)
    await fetchGenres()
  } catch (err) {
    toast.error('Error al sincronizar géneros.')
  } finally {
    syncing.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString()
}

onMounted(() => fetchGenres())
</script>
