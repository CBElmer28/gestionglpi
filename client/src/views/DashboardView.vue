<template>
  <div>
    <!-- Stats cards -->
    <div class="stats-grid">
      <div class="stat-card" v-for="stat in stats" :key="stat.label">
        <div class="stat-icon" :class="stat.color">
          <font-awesome-icon :icon="stat.icon" />
        </div>
        <div class="stat-info">
          <div class="stat-value">
            <span v-if="loading" class="skeleton" style="display:inline-block;width:60px;height:32px;"></span>
            <span v-else>{{ stat.value }}</span>
          </div>
          <div class="stat-label">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- Grids de resumen -->
    <div class="dashboard-grid">
      <!-- Préstamos recientes -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <font-awesome-icon icon="clipboard-list" /> Préstamos Activos
          </h3>
          <RouterLink to="/loans" class="btn btn-ghost btn-sm">Ver todos →</RouterLink>
        </div>
        <div class="card-body" style="padding:0">
          <div v-if="loadingLoans" class="spinner-overlay">
            <div class="spinner spinner-lg"></div>
          </div>
          <table v-else-if="activeLoans.length" class="table">
            <thead>
              <tr>
                <th>Libro</th>
                <th>Usuario</th>
                <th>Fecha Préstamo</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in activeLoans.slice(0,6)" :key="loan.id">
                <td><strong>{{ loan.book?.title || '—' }}</strong></td>
                <td>{{ loan.user_name }}</td>
                <td>{{ formatDate(loan.loan_date) }}</td>
                <td>
                  <span class="badge" :class="loanStatusBadge(loan.status)">
                    {{ loan.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="empty-state">
            <div class="empty-state-icon">
              <font-awesome-icon icon="clipboard-list" />
            </div>
            <h3>Sin préstamos activos</h3>
          </div>
        </div>
      </div>

      <!-- Info panel -->
      <div class="dashboard-side">
        <!-- Estado GLPI -->
        <div class="card glpi-status-card">
          <div class="card-header">
            <h3 class="card-title">
              <font-awesome-icon icon="link" /> Estado GLPI
            </h3>
          </div>
          <div class="card-body">
            <div v-if="glpiLoading" class="glpi-checking">
              <div class="spinner spinner-lg" style="border-top-color:var(--c-primary)"></div>
              <span>Verificando conexión…</span>
            </div>
            <div v-else class="glpi-status-info">
              <div class="glpi-status-dot" :class="glpiConnected ? 'online' : 'offline'"></div>
              <div>
                <strong>{{ glpiConnected ? 'Conectado' : 'Desconectado' }}</strong>
                <p>{{ glpiConnected ? 'GLPI responde correctamente.' : 'No se pudo conectar con GLPI. Verifica que el servidor esté activo.' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <font-awesome-icon icon="bolt" /> Accesos Rápidos
            </h3>
          </div>
          <div class="card-body quick-links">
            <RouterLink to="/books" class="quick-link">
              <font-awesome-icon icon="book" /><span>Agregar Libro</span>
            </RouterLink>
            <RouterLink to="/loans" class="quick-link">
              <font-awesome-icon icon="clipboard-list" /><span>Nuevo Préstamo</span>
            </RouterLink>
            <RouterLink v-if="auth.isAdmin" to="/users" class="quick-link">
              <font-awesome-icon icon="users" /><span>Usuarios</span>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { bookRepository } from '@/repositories/bookRepository'
import { loanRepository } from '@/repositories/loanRepository'
import { glpiService }    from '@/services/glpiService'
import { useAuthStore }   from '@/store/auth'

const auth = useAuthStore()

const loading      = ref(true)
const loadingLoans = ref(true)
const glpiLoading  = ref(true)
const glpiConnected = ref(false)

const totalBooks = ref(0)
const activeLoans = ref([])
const overdueCount = ref(0)

const stats = computed(() => [
  {
    icon: 'book', label: 'Total de Libros',
    value: totalBooks.value, color: 'blue',
  },
  {
    icon: 'clipboard-list', label: 'Préstamos Activos',
    value: activeLoans.value.filter(l => l.status === 'Activo').length,
    color: 'gold',
  },
  {
    icon: 'exclamation-triangle', label: 'Préstamos Atrasados',
    value: overdueCount.value, color: 'red',
  },
  {
    icon: 'check-circle', label: 'Libros Disponibles',
    value: '—', color: 'green',
  },
])

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('es-PE', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

function loanStatusBadge(status) {
  return {
    'Activo':   'badge-info',
    'Devuelto': 'badge-success',
    'Atrasado': 'badge-danger',
  }[status] || 'badge-gray'
}

onMounted(async () => {
  // Libros
  try {
    const booksData = await bookRepository.getAll()
    totalBooks.value = booksData.total ?? (booksData.data?.length ?? 0)
  } catch { /* silencioso */ } finally { loading.value = false }

  // Préstamos
  try {
    const loansData  = await loanRepository.getAll({ status: 'Activo' })
    activeLoans.value = loansData.data ?? loansData
    const overdue     = await loanRepository.getAll({ status: 'Atrasado' })
    overdueCount.value = (overdue.data ?? overdue).length
  } catch { /* silencioso */ } finally { loadingLoans.value = false }

  // GLPI ping
  try {
    const { data } = await glpiService.ping()
    glpiConnected.value = data.connected
  } catch {
    glpiConnected.value = false
  } finally { glpiLoading.value = false }
})
</script>

<style scoped>
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: var(--sp-6);
  align-items: start;
}

.dashboard-side {
  display: flex;
  flex-direction: column;
  gap: var(--sp-5);
}

.glpi-status-card {}

.glpi-checking {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  color: var(--c-text-secondary);
  font-size: .875rem;
}

.glpi-status-info {
  display: flex;
  align-items: flex-start;
  gap: var(--sp-4);
}

.glpi-status-dot {
  width: 12px; height: 12px;
  border-radius: 50%;
  margin-top: 4px;
  flex-shrink: 0;
}

.glpi-status-dot.online  {
  background: var(--c-success);
  box-shadow: 0 0 0 4px rgba(16,185,129,.2);
  animation: pulse 2s infinite;
}

.glpi-status-dot.offline {
  background: var(--c-danger);
}

.glpi-status-info strong {
  display: block;
  margin-bottom: 4px;
}

.glpi-status-info p {
  font-size: .82rem;
  color: var(--c-text-secondary);
}

.quick-links {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--sp-3);
  padding: var(--sp-5) !important;
}

.quick-link {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: var(--sp-3) var(--sp-4);
  background: var(--c-surface-2);
  border: 1.5px solid var(--c-border);
  border-radius: var(--radius-md);
  font-size: .82rem;
  font-weight: 600;
  color: var(--c-text-secondary);
  transition: all var(--tr-fast);
}

.quick-link:hover {
  border-color: var(--c-primary-300);
  background: var(--c-primary-50);
  color: var(--c-primary);
  transform: translateY(-1px);
}

@media (max-width: 1100px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
