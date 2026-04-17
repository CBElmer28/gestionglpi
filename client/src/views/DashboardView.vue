<template>
  <div>
    <!-- Stats cards -->
    <div class="stats-grid">
      <div v-for="stat in currentStats" :key="stat.label" class="stat-card">
        <div class="stat-icon" :class="stat.color">
          <font-awesome-icon :icon="stat.icon" />
        </div>
        <div class="stat-info">
          <div class="stat-value">
            <span v-if="loading" class="skeleton" style="display:inline-block;width:60px;height:32px;"></span>
            <span v-else>{{ stat.value }}</span>
          </div>
          <div class="stat-label">{{ stat.label }}</div>
          
          <!-- Botón de acción específico -->
          <div v-if="!loading && stat.action" class="stat-action-container">
            <RouterLink :to="stat.action.to" class="btn btn-ghost btn-xs mt-2">
              {{ stat.action.label }} <font-awesome-icon icon="chevron-right" class="ml-1" />
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Grids de resumen -->
    <div class="dashboard-grid">
      <!-- Préstamos recientes -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <font-awesome-icon icon="clipboard-list" /> 
            {{ !auth.can('loans.view_all') ? 'Mis Préstamos' : 'Préstamos Activos' }}
          </h3>
          <RouterLink v-if="auth.can('loans.view_all')" to="/loans" class="btn btn-ghost btn-sm">Ver todos →</RouterLink>
        </div>
        <div class="card-body" style="padding:0">
          <div v-if="loadingLoans" class="spinner-overlay">
            <div class="spinner spinner-lg"></div>
          </div>
          <table v-else-if="loansToShow.length" class="table">
            <thead>
              <tr>
                <th>Libro</th>
                <th v-if="auth.can('loans.view_all')">Usuario</th>
                <th>Fecha Préstamo</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in loansToShow.slice(0,6)" :key="loan.id">
                <td><strong>{{ loan.book?.title || '—' }}</strong></td>
                <td v-if="auth.can('loans.view_all')">{{ loan.user_name }}</td>
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
            <h3>{{ !auth.can('loans.view_all') ? 'No tienes préstamos registrados' : 'Sin préstamos activos' }}</h3>
          </div>
        </div>
      </div>

      <!-- Info panel -->
      <div class="dashboard-side">
        <!-- Estado GLPI -->
        <div v-if="auth.can('glpi.manage')" class="card glpi-status-card">
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
                <p>{{ glpiConnected ? 'GLPI responde correctamente.' : 'No se pudo conectar con GLPI.' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Info Biblioteca -->
        <div v-if="auth.can('incidents.report') && !auth.can('loans.view_all')" class="card">
          <div class="card-header">
            <h3 class="card-title">
              <font-awesome-icon icon="exclamation-circle" /> Información
            </h3>
          </div>
          <div class="card-body">
            <p style="font-size: .85rem; color: var(--c-text-secondary); line-height: 1.6;">
              Recuerda devolver tus libros a tiempo. El plazo máximo de préstamo es de 7 días.
            </p>
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
            <RouterLink v-if="auth.can('books.manage')" to="/books" class="quick-link">
              <font-awesome-icon icon="book" /><span>Libros</span>
            </RouterLink>
            <RouterLink v-if="auth.can('loans.manage')" to="/loans" class="quick-link">
              <font-awesome-icon icon="clipboard-list" /><span>Nuevo Préstamo</span>
            </RouterLink>
            <RouterLink v-if="auth.can('users.manage')" to="/users" class="quick-link">
              <font-awesome-icon icon="users" /><span>Usuarios</span>
            </RouterLink>
            <RouterLink v-if="auth.can('loans.view_own')" to="/loans" class="quick-link">
              <font-awesome-icon icon="clipboard-list" /><span>Mis Préstamos</span>
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
const availableBooks = ref(0)
const activeLoans = ref([])
const overdueCount = ref(0)
const myTotalHistory = ref(0)

const currentStats = computed(() => {
  if (!auth.can('loans.view_all')) {
    return [
      {
        icon: 'clipboard-list', label: 'Mis Préstamos Activos',
        value: activeLoans.value.filter(l => l.status === 'Activo').length,
        color: 'blue',
      },
      {
        icon: 'exclamation-triangle', label: 'Mis Atrasados',
        value: overdueCount.value, color: 'red',
      },
      {
        icon: 'book', label: 'Mi Historial Total',
        value: myTotalHistory.value, color: 'gold',
      },
      {
        icon: 'info-circle', label: 'Plazo de Entrega',
        value: '7 días', color: 'green',
      },
    ]
  }

  // Admin / Biblio stats
  return [
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
      icon: 'exclamation-triangle', label: 'Préstamos Vencidos',
      value: overdueCount.value, color: 'red',
    },
    {
      icon: 'check-circle', label: 'Libros Disponibles',
      value: availableBooks.value, color: 'green',
    },
  ]
})

const loansToShow = computed(() => activeLoans.value)

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
  // Libros (Estadísticas generales)
  try {
    const [booksData, availableData] = await Promise.all([
      bookRepository.getAll(),
      bookRepository.getAll({ status: 'Disponible' })
    ])
    totalBooks.value = booksData.total ?? (booksData.data?.length ?? 0)
    availableBooks.value = availableData.total ?? (availableData.data?.length ?? 0)
  } catch { /* silencioso */ } finally { loading.value = false }

  // Préstamos
  try {
    const isLectorOnly = !auth.can('loans.view_all')
    const queryParams = isLectorOnly ? { user_id: auth.user?.id } : {}
    
    if (isLectorOnly) {
      const [myActive, myOverdue, myAll] = await Promise.all([
        loanRepository.getAll({ ...queryParams, status: 'Activo' }),
        loanRepository.getAll({ ...queryParams, status: 'Atrasado' }),
        loanRepository.getAll({ ...queryParams })
      ])
      
      const activeData = myActive.data ?? myActive
      const overdueData = myOverdue.data ?? myOverdue
      const allData = myAll.data ?? myAll

      activeLoans.value = [...(Array.isArray(activeData) ? activeData : []), ...(Array.isArray(overdueData) ? overdueData : [])]
      overdueCount.value = Array.isArray(overdueData) ? overdueData.length : 0
      myTotalHistory.value = Array.isArray(allData) ? allData.length : 0
    } else {
      const [globalActive, globalOverdue] = await Promise.all([
        loanRepository.getAll({ status: 'Activo' }),
        loanRepository.getAll({ status: 'Atrasado' })
      ])
      const activeData = globalActive.data ?? globalActive
      const overdueData = globalOverdue.data ?? globalOverdue
      activeLoans.value = Array.isArray(activeData) ? activeData : []
      overdueCount.value = Array.isArray(overdueData) ? overdueData.length : 0
    }
  } catch { /* silencioso */ } finally { loadingLoans.value = false }

  // GLPI ping
  if (auth.can('glpi.manage')) {
    try {
      const { data } = await glpiService.ping()
      glpiConnected.value = data.connected
    } catch {
      glpiConnected.value = false
    } finally { glpiLoading.value = false }
  }
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

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>
