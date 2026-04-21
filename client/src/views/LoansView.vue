<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="clipboard-list" /> {{ isLectorMode ? 'Mis Préstamos' : 'Gestión de Préstamos' }}</h1>
        <p>{{ isLectorMode ? 'Consulta tus libros activos y reporta incidencias.' : 'Registra y controla el ciclo de vida de los préstamos.' }}</p>
      </div>
      <button v-if="auth.can('loans.manage')" id="btn-new-loan" class="btn btn-primary" @click="handleOpenCreate">
        <font-awesome-icon icon="plus" /> Nuevo Préstamo
      </button>
    </div>

    <!-- Filtros -->
    <div class="card overflow-visible" style="margin-bottom:var(--sp-5)">
      <div class="card-body" style="padding:var(--sp-4)">
        <div class="filters-row">
          <BaseCombobox 
            v-model="filters.status"
            :options="loanStatuses"
            placeholder="Todos los estados"
            style="width:200px"
            @change="fetchLoans(1, lectorParams)"
          />
          <div v-if="!isLectorMode" class="search-bar" style="flex:1">
            <span class="search-bar-icon"><font-awesome-icon icon="search" /></span>
            <input
              v-model="filters.user_name"
              type="text"
              class="form-control"
              placeholder="Buscar por nombre de usuario…"
              @input="fetchLoans(1, lectorParams)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div v-if="loading" class="spinner-overlay">
        <div class="spinner spinner-lg"></div>
      </div>

      <div v-else-if="!loanList.length" class="empty-state">
        <div class="empty-state-icon">
          <font-awesome-icon icon="clipboard-list" />
        </div>
        <h3>No se encontraron préstamos</h3>
      </div>

      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Libro</th>
              <th v-if="!isLectorMode">Usuario</th>
              <th>Fecha Préstamo</th>
              <th>Fecha Devolución</th>
              <th>Estado</th>
              <th style="text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="loan in loanList" :key="loan.id">
              <td style="color:var(--c-text-muted);font-size:.8rem">{{ loan.id }}</td>
              <td><strong>{{ loan.book?.title || '—' }}</strong></td>
              <td v-if="!isLectorMode">{{ loan.user_name }}</td>
              <td>{{ formatDate(loan.loan_date) }}</td>
              <td>{{ loan.return_date ? formatDate(loan.return_date) : '—' }}</td>
              <td>
                <span class="badge" :class="statusBadge(loan.status)">{{ loan.status }}</span>
              </td>
              <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                  <!-- Botón Reportar / Ver Info -->
                  <button
                    v-if="auth.can('incidents.report') && (loan.status === 'Activo' || loan.status === 'Atrasado')"
                    class="btn btn-sm"
                    :class="loan.book?.status === 'Mantenimiento' ? 'btn-action-info' : 'btn-action-report'"
                    @click="openReportModal(loan)"
                    :title="loan.book?.status === 'Mantenimiento' ? 'Ver información de la incidencia' : 'Reportar daño/incidencia'"
                  >
                    <font-awesome-icon :icon="loan.book?.status === 'Mantenimiento' ? 'circle-info' : 'exclamation-triangle'" />
                    {{ loan.book?.status === 'Mantenimiento' ? 'Ver Info' : 'Reportar' }}
                  </button>

                  <button
                    v-if="auth.can('loans.manage') && loan.status === 'Activo'"
                    class="btn btn-ghost btn-sm"
                    @click="confirmReturn(loan)"
                    title="Registrar devolución"
                  >
                    <font-awesome-icon icon="reply" /> Devolver
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="loans && loans.last_page > 1" class="pagination">
        <button class="btn btn-ghost btn-sm" :disabled="loans.current_page === 1" @click="fetchLoans(loans.current_page - 1, lectorParams)">
          <font-awesome-icon icon="chevron-left" /> Anterior
        </button>
        <span style="font-size:.85rem;color:var(--c-text-secondary)">Página {{ loans.current_page }} de {{ loans.last_page }}</span>
        <button class="btn btn-ghost btn-sm" :disabled="loans.current_page === loans.last_page" @click="fetchLoans(loans.current_page + 1, lectorParams)">
          Siguiente <font-awesome-icon icon="chevron-right" />
        </button>
      </div>
    </div>

    <!-- Modal Nuevo Préstamo -->
    <div v-if="modal.visible" class="modal-backdrop" @click.self="modal.visible = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon icon="circle-plus" /> Nuevo Préstamo
          </h3>
          <button class="btn btn-ghost btn-icon" @click="modal.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Libro *</label>
            <BaseCombobox 
              v-model="modal.form.book_id"
              :options="books"
              label-key="title"
              sub-label-key="author"
              placeholder="Buscar libro por título o autor..."
              :has-error="!!modal.errors.book_id"
            />
            <span v-if="modal.errors.book_id" class="form-error">{{ modal.errors.book_id[0] }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Nombre del usuario *</label>
            <BaseCombobox 
              v-model="modal.form.user_name"
              :options="lectors"
              label-key="name"
              sub-label-key="email"
              value-key="name"
              placeholder="Buscar lector por nombre o correo..."
              :has-error="!!modal.errors.user_name"
              @change="(u) => modal.form.user_id = u.id"
            />
            <span v-if="modal.errors.user_name" class="form-error">{{ modal.errors.user_name[0] }}</span>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Fecha de Préstamo</label>
              <input v-model="modal.form.loan_date" type="date" class="form-control" :min="today" @input="updateReturnDate" />
            </div>
            <div class="form-group">
              <label class="form-label">Fecha de Devolución (Plazo 7 días)</label>
              <input v-model="modal.form.return_date" type="date" class="form-control readonly-input" readonly />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="modal.visible = false">Cancelar</button>
          <button id="btn-save-loan" class="btn btn-primary" @click="createLoan" :disabled="modal.submitting">
            <span v-if="modal.submitting" class="spinner"></span>
            <span v-else>Registrar Préstamo</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Reportar Incidencia -->
    <div v-if="reportModal.visible" class="modal-backdrop" @click.self="reportModal.visible = false">
      <div id="modal-report" class="modal" style="max-width:500px">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon :icon="isReadOnlyReport ? 'circle-info' : 'exclamation-triangle'" />
            {{ isReadOnlyReport ? 'Información de la Incidencia' : 'Reportar Incidencia' }}
          </h3>
          <button class="btn btn-ghost btn-icon" @click="reportModal.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <div v-if="isReadOnlyReport" class="alert alert-info" style="margin-bottom:var(--sp-4); padding:var(--sp-2) var(--sp-3); font-size:.85rem">
            <font-awesome-icon icon="lock" /> Este libro está en mantenimiento. La incidencia ya ha sido reportada.
          </div>
          <div v-if="reportModal.loan" style="margin-bottom:var(--sp-4);padding:var(--sp-3);background:var(--c-bg-page);border-radius:var(--radius-sm)">
             <div style="font-weight:600;font-size:.9rem">{{ reportModal.loan.book?.title }}</div>
             <div style="font-size:.8rem;color:var(--c-text-secondary)">{{ reportModal.loan.book?.author }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Prioridad *</label>
            <BaseCombobox 
              v-model="reportModal.form.priority"
              :options="priorityOptions"
              placeholder="Seleccione prioridad..."
              :disabled="isReadOnlyReport"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Descripción del problema *</label>
            <textarea
              v-model="reportModal.form.description"
              class="form-control"
              rows="4"
              placeholder="Ej: El libro tiene varias hojas sueltas y la portada dañada..."
              :disabled="isReadOnlyReport"
            ></textarea>
            <span v-if="reportModal.errors.description" class="form-error">{{ reportModal.errors.description[0] }}</span>
          </div>

          <div v-if="isReadOnlyReport && reportModal.form.glpi_ticket_id" class="form-group">
            <label class="form-label">Ticket GLPI</label>
            <div class="form-control" style="background:var(--c-bg-page); font-family:monospace; font-weight:600">
              #{{ reportModal.form.glpi_ticket_id }}
            </div>
          </div>

          <div v-if="!isReadOnlyReport" class="form-group">
            <label class="form-label">Evidencia Fotográfica (Opcional)</label>
            <input type="file" @change="handleFileChange" accept="image/jpeg,image/png,image/webp" class="form-control" />
            <span v-if="reportModal.errors.image" class="form-error">{{ reportModal.errors.image[0] }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="reportModal.visible = false">{{ isReadOnlyReport ? 'Cerrar' : 'Cancelar' }}</button>
          <button v-if="!isReadOnlyReport" class="btn btn-warning" @click="submitReport" :disabled="reportModal.submitting">
            <span v-if="reportModal.submitting" class="spinner"></span>
            <span v-else>Enviar a GLPI</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmar devolución -->
    <div v-if="returnConfirm.visible" class="modal-backdrop" @click.self="returnConfirm.visible = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon icon="reply" /> Confirmar Devolución
          </h3>
          <button class="btn btn-ghost btn-icon" @click="returnConfirm.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <p>¿Confirmas la devolución del libro de este préstamo? Se marcará como <strong>Devuelto</strong> y el libro quedará disponible.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="returnConfirm.visible = false">Cancelar</button>
          <button class="btn btn-primary" @click="returnLoan">Confirmar Devolución</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useLoanController } from '@/controllers/loanController'
import { useAuthStore } from '@/store/auth'
import BaseCombobox from '@/components/common/BaseCombobox.vue'

const auth = useAuthStore()
const route = useRoute()
const {
  loans, books, lectors, loading, filters, modal, returnConfirm, reportModal,
  fetchLoans, openCreate, createLoan, confirmReturn, returnLoan, 
  openReportModal, submitReport
} = useLoanController()

const loanStatuses = [
  { id: '', name: 'Todos los estados' },
  { id: 'Activo', name: 'Activo' },
  { id: 'Devuelto', name: 'Devuelto' },
  { id: 'Atrasado', name: 'Atrasado' }
]

const priorityOptions = [
  { id: 'Baja', name: 'Baja (Minor Issue)' },
  { id: 'Media', name: 'Media (Standard)' },
  { id: 'Alta', name: 'Alta (Urgent Repair)' }
]

// Reseteamos el buscador local al abrir el modal
const originalOpenCreate = openCreate
const today = new Date().toISOString().split('T')[0]

function handleOpenCreate() {
  originalOpenCreate()
  updateReturnDate()
}

function updateReturnDate() {
  const val = modal.form.loan_date
  if (val) {
    const d = new Date(val + 'T12:00:00')
    d.setDate(d.getDate() + 7)
    modal.form.return_date = d.toISOString().split('T')[0]
  }
}


const loanList = computed(() => loans.value?.data ?? [])
// Si NO tiene permiso para ver todos, asumimos que solo ve los suyos
const isLectorMode = computed(() => !auth.can('loans.view_all'))
const lectorParams = computed(() => isLectorMode.value ? { user_id: auth.user?.id } : {})

const isReadOnlyReport = computed(() => {
  return reportModal.loan?.book?.status === 'Mantenimiento'
})

function formatDate(d) {
  if (!d) return '—'
  // Si es solo fecha (YYYY-MM-DD), lo parseamos como local para evitar desfase de zona horaria
  if (typeof d === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(d)) {
    const [y, m, day] = d.split('-').map(Number)
    return new Date(y, m - 1, day).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' })
  }
  return new Date(d).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' })
}

function statusBadge(s) {
  return { 'Activo': 'badge-info', 'Devuelto': 'badge-success', 'Atrasado': 'badge-danger' }[s] || 'badge-gray'
}

function handleFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    reportModal.form.image = file
  }
}

onMounted(() => {
  // Manejar parámetros de consulta (Dashboard quick links)
  if (route.query.status) {
    filters.status = route.query.status
  }

  fetchLoans(1, lectorParams.value)

  if (route.query.action === 'new' && auth.can('loans.manage')) {
    handleOpenCreate()
  }
})
</script>

<style scoped>
.filters-row { display: flex; gap: var(--sp-4); align-items: center; flex-wrap: wrap; }
.pagination  { display: flex; align-items: center; justify-content: center; gap: var(--sp-5); padding: var(--sp-4) var(--sp-6); border-top: 1px solid var(--c-border-light); }
.text-warning { color: var(--c-warning) !important; }
.readonly-input {
  background-color: var(--c-primary-50) !important;
  cursor: not-allowed;
  opacity: 0.8;
  border-style: dashed;
}
</style>
