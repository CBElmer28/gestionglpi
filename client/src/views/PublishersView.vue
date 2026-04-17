<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="building" /> Editoriales</h1>
        <p>Listado de editoriales (fabricantes) sincronizados desde GLPI.</p>
      </div>
    </div>

    <div class="card">
      <div v-if="loading" class="spinner-overlay">
        <div class="spinner spinner-lg"></div>
      </div>

      <div v-else-if="!publishers.length" class="empty-state">
        <div class="empty-state-icon">
          <font-awesome-icon icon="building" />
        </div>
        <h3>No hay editoriales registradas</h3>
        <p>Pulsa "Sincronizar con GLPI" en la sección de Libros para importar los datos.</p>
      </div>

      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID GLPI</th>
              <th>Nombre de la Editorial</th>
              <th>Fecha de Sincronización</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in publishers" :key="p.id">
              <td style="color:var(--c-text-muted);font-size:.8rem;font-family:monospace">
                #{{ p.glpi_id }}
              </td>
              <td><strong>{{ p.name }}</strong></td>
              <td style="color:var(--c-text-secondary);font-size:.82rem">
                {{ formatDate(p.updated_at) }}
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

const publishers = ref([])
const loading = ref(false)

async function fetchPublishers() {
  loading.value = true
  try {
    const { data } = await glpiService.listPublishers()
    publishers.value = data
  } catch (err) {
    console.error('Error fetching publishers:', err)
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString()
}

onMounted(() => fetchPublishers())
</script>
