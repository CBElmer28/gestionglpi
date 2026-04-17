<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="link" /> Integración GLPI</h1>
        <p>Estado de la conexión y gestión de activos en GLPI.</p>
      </div>
      <div style="display:flex;gap:var(--sp-2)">
        <button class="btn btn-ghost" @click="handleSyncAll" :disabled="syncing">
          <font-awesome-icon :icon="syncing ? 'sync' : 'sync'" :spin="syncing" />
          {{ syncing ? 'Sincronizando...' : 'Sincronizar con GLPI' }}
        </button>
        <button class="btn btn-primary" @click="checkGlpi" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else><font-awesome-icon icon="sync" /> Verificar conexión</span>
        </button>
      </div>
    </div>

    <!-- Estado de conexión -->
    <div class="stats-grid" style="margin-bottom:var(--sp-6)">
      <div class="stat-card">
        <div class="stat-icon" :class="connected ? 'green' : 'red'">
          <font-awesome-icon :icon="connected ? 'check-circle' : 'times-circle'" />
        </div>
        <div class="stat-info">
          <div class="stat-value" style="font-size:1.1rem">{{ connected ? 'Conectado' : 'Desconectado' }}</div>
          <div class="stat-label">Estado de GLPI API</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><font-awesome-icon icon="book" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ glpiBooks.length }}</div>
          <div class="stat-label">Libros en GLPI</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon gold"><font-awesome-icon icon="ticket-alt" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ tickets.length }}</div>
          <div class="stat-label">Tickets recientes</div>
        </div>
      </div>
    </div>

    <div class="glpi-grid">
      <!-- Libros en GLPI -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><font-awesome-icon icon="book" /> Libros en GLPI</h3>
          <button class="btn btn-ghost btn-sm" @click="loadGlpiBooks"><font-awesome-icon icon="sync" /> Actualizar</button>
        </div>
        <div class="card-body" style="padding:0">
          <div v-if="loadingBooks" class="spinner-overlay"><div class="spinner spinner-lg"></div></div>
          <div v-else-if="!glpiBooks.length" class="empty-state">
            <div class="empty-state-icon"><font-awesome-icon icon="inbox" /></div>
            <h3>Sin libros en GLPI</h3>
            <p>Los libros se sincronizan automáticamente al crearlos.</p>
          </div>
          <div v-else class="table-container">
            <div class="table-wrapper books-table-wrapper">
              <table class="table">
                <thead><tr><th>ID GLPI</th><th>Título</th><th>Autor</th><th>ISBN</th></tr></thead>
                <tbody>
                  <tr v-for="item in paginatedBooks" :key="item.id">
                    <td style="font-family:monospace;font-size:.8rem">#{{ item.id }}</td>
                    <td><strong>{{ item.title || item.name || '—' }}</strong></td>
                    <td>{{ item.author || '—' }}</td>
                    <td style="font-family:monospace;font-size:.8rem">{{ item.isbn || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Pagination Controls -->
            <div v-if="totalPagesBooks > 1" class="mini-pagination">
              <button class="btn btn-ghost btn-xs" :disabled="booksPage === 1" @click="booksPage--">
                <font-awesome-icon icon="chevron-left" />
              </button>
              <span>{{ booksPage }} / {{ totalPagesBooks }}</span>
              <button class="btn btn-ghost btn-xs" :disabled="booksPage === totalPagesBooks" @click="booksPage++">
                <font-awesome-icon icon="chevron-right" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tickets -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><font-awesome-icon icon="ticket-alt" /> Tickets Recientes</h3>
          <button class="btn btn-ghost btn-sm" @click="loadTickets"><font-awesome-icon icon="sync" /> Actualizar</button>
        </div>
        <div class="card-body" style="padding:0">
          <div v-if="loadingTickets" class="spinner-overlay"><div class="spinner spinner-lg"></div></div>
          <div v-else-if="!tickets.length" class="empty-state">
            <div class="empty-state-icon"><font-awesome-icon icon="ticket-alt" /></div>
            <h3>Sin tickets</h3>
            <p>No se encontraron tickets en GLPI.</p>
          </div>
          <div v-else class="table-container">
            <div class="table-wrapper tickets-table-wrapper">
              <table class="table">
                <thead><tr><th>ID</th><th>Nombre</th><th>Estado</th></tr></thead>
                <tbody>
                  <tr v-for="ticket in paginatedTickets" :key="ticket.id">
                    <td style="font-family:monospace;font-size:.8rem">{{ ticket.id }}</td>
                    <td><strong>{{ ticket.name }}</strong></td>
                    <td>
                      <span class="badge" :class="ticketStatus(ticket.status).class">
                        {{ ticketStatus(ticket.status).label }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Pagination Controls -->
            <div v-if="totalPagesTickets > 1" class="mini-pagination">
              <button class="btn btn-ghost btn-xs" :disabled="ticketsPage === 1" @click="ticketsPage--">
                <font-awesome-icon icon="chevron-left" />
              </button>
              <span>{{ ticketsPage }} / {{ totalPagesTickets }}</span>
              <button class="btn btn-ghost btn-xs" :disabled="ticketsPage === totalPagesTickets" @click="ticketsPage++">
                <font-awesome-icon icon="chevron-right" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Credenciales info -->
    <div class="card" style="margin-top:var(--sp-6)">
      <div class="card-header">
        <h3 class="card-title"><font-awesome-icon icon="cog" /> Configuración de Conexión</h3>
      </div>
      <div class="card-body">
        <div class="glpi-config-info">
          <div class="config-item">
            <div class="config-label">URL de API</div>
            <code class="config-value">http://localhost:8080/api.php/v1</code>
          </div>
          <div class="config-item">
            <span class="config-label">Item Type (Libros)</span>
            <code class="config-value">Glpi\CustomAsset\LibrosAsset</code>
          </div>
          <div class="config-item">
            <span class="config-label">Método de Auth</span>
            <code class="config-value">App-Token + User-Token</code>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { glpiService } from '@/services/glpiService'
import { useToast }    from 'vue-toastification'

const toast = useToast()

const connected      = ref(false)
const syncing        = ref(false)
const loading        = ref(false)
const loadingBooks   = ref(false)
const loadingTickets = ref(false)
const glpiBooks      = ref([])
const tickets        = ref([])

// Pagination States
const booksPage      = ref(1)
const BOOKS_PER_PAGE = 6
const ticketsPage    = ref(1)
const TICKETS_PER_PAGE = 6

// Computed for Pagination
const totalPagesBooks = computed(() => Math.ceil(glpiBooks.value.length / BOOKS_PER_PAGE))
const paginatedBooks  = computed(() => {
  const start = (booksPage.value - 1) * BOOKS_PER_PAGE
  return glpiBooks.value.slice(start, start + BOOKS_PER_PAGE)
})

const totalPagesTickets = computed(() => Math.ceil(tickets.value.length / TICKETS_PER_PAGE))
const paginatedTickets  = computed(() => {
  const start = (ticketsPage.value - 1) * TICKETS_PER_PAGE
  return tickets.value.slice(start, start + TICKETS_PER_PAGE)
})

async function handleSyncAll() {
  syncing.value = true
  try {
    const { data } = await glpiService.syncAll()
    toast.success(`${data.message} Locales: +${data.details.created_local}, GLPI: +${data.details.created_glpi}`)
    await Promise.all([loadGlpiBooks(), loadTickets()])
  } catch (err) {
    toast.error('Error durante la sincronización.')
  } finally {
    syncing.value = false
  }
}

async function checkGlpi() {
  loading.value = true
  try {
    const { data } = await glpiService.ping()
    connected.value = data.connected
    toast[connected.value ? 'success' : 'warning'](data.message)
  } catch {
    connected.value = false
    toast.error('No se pudo contactar con GLPI.')
  } finally {
    loading.value = false
  }
}

async function loadGlpiBooks() {
  loadingBooks.value = true
  booksPage.value = 1
  try {
    const { data } = await glpiService.listBooks()
    glpiBooks.value = Array.isArray(data) ? data : []
  } catch {
    glpiBooks.value = []
  } finally {
    loadingBooks.value = false
  }
}

async function loadTickets() {
  loadingTickets.value = true
  ticketsPage.value = 1
  try {
    const { data } = await glpiService.listTickets()
    tickets.value = Array.isArray(data) ? data : []
  } catch {
    tickets.value = []
  } finally {
    loadingTickets.value = false
  }
}

function ticketStatus(status) {
  const s = parseInt(status)
  const map = {
    1: { label: 'Nuevo',        class: 'badge-info' },
    2: { label: 'Asignado',     class: 'badge-warning' },
    3: { label: 'Planificado',  class: 'badge-warning' },
    4: { label: 'En espera',    class: 'badge-gray' },
    5: { label: 'Resuelto',     class: 'badge-success' },
    6: { label: 'Cerrado',      class: 'badge-gray' },
  }
  return map[s] || { label: 'Enviado', class: 'badge-info' }
}

onMounted(async () => {
  await checkGlpi()
  await Promise.all([loadGlpiBooks(), loadTickets()])
})
</script>

<style scoped>
.glpi-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--sp-6);
}

.table-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Alturas fijas para mantener consistencia visual (ajustado a 6 filas) */
.books-table-wrapper {
  min-height: 320px;
  display: flex;
  flex-direction: column;
}

.tickets-table-wrapper {
  min-height: 320px;
  display: flex;
  flex-direction: column;
}

.table-wrapper {
  flex: 1;
}

.mini-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-4);
  padding: var(--sp-3);
  border-top: 1px solid var(--c-border-light);
  font-size: .8rem;
  color: var(--c-text-secondary);
  height: 45px; /* Altura fija para el control */
}

.glpi-config-info {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
}

.config-item {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
}

.config-label {
  min-width: 160px;
  font-size: .82rem;
  font-weight: 600;
  color: var(--c-text-secondary);
}

.config-value {
  background: var(--c-surface-2);
  border: 1px solid var(--c-border);
  padding: var(--sp-1) var(--sp-3);
  border-radius: var(--radius-sm);
  font-size: .82rem;
  color: var(--c-primary-700);
}

@media (max-width: 900px) {
  .glpi-grid { grid-template-columns: 1fr; }
}
</style>
