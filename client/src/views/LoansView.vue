<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="clipboard-list" /> Gestión de Préstamos</h1>
        <p>Registra y controla el ciclo de vida de los préstamos.</p>
      </div>
      <button id="btn-new-loan" class="btn btn-primary" @click="openCreate">
        <font-awesome-icon icon="plus" /> Nuevo Préstamo
      </button>
    </div>

    <!-- Filtros -->
    <div class="card" style="margin-bottom:var(--sp-5)">
      <div class="card-body" style="padding:var(--sp-4)">
        <div class="filters-row">
          <select v-model="filters.status" class="form-control" style="width:200px" @change="fetchLoans()">
            <option value="">Todos los estados</option>
            <option value="Activo">Activo</option>
            <option value="Devuelto">Devuelto</option>
            <option value="Atrasado">Atrasado</option>
          </select>
          <div class="search-bar" style="flex:1">
            <span class="search-bar-icon"><font-awesome-icon icon="search" /></span>
            <input
              v-model="filters.user_name"
              type="text"
              class="form-control"
              placeholder="Buscar por nombre de usuario…"
              @input="fetchLoans()"
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
              <th>Usuario</th>
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
              <td>{{ loan.user_name }}</td>
              <td>{{ formatDate(loan.loan_date) }}</td>
              <td>{{ loan.return_date ? formatDate(loan.return_date) : '—' }}</td>
              <td>
                <span class="badge" :class="statusBadge(loan.status)">{{ loan.status }}</span>
              </td>
              <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                  <button
                    v-if="loan.status === 'Activo'"
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
        <button class="btn btn-ghost btn-sm" :disabled="loans.current_page === 1" @click="fetchLoans(loans.current_page - 1)">
          <font-awesome-icon icon="chevron-left" /> Anterior
        </button>
        <span style="font-size:.85rem;color:var(--c-text-secondary)">Página {{ loans.current_page }} de {{ loans.last_page }}</span>
        <button class="btn btn-ghost btn-sm" :disabled="loans.current_page === loans.last_page" @click="fetchLoans(loans.current_page + 1)">
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
            <select v-model="modal.form.book_id" class="form-control">
              <option value="">Seleccionar libro…</option>
              <option v-for="b in books" :key="b.id" :value="b.id">{{ b.title }} — {{ b.author }}</option>
            </select>
            <span v-if="modal.errors.book_id" class="form-error">{{ modal.errors.book_id[0] }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Nombre del usuario *</label>
            <input v-model="modal.form.user_name" type="text" class="form-control" placeholder="Nombre del alumno o lector" />
            <span v-if="modal.errors.user_name" class="form-error">{{ modal.errors.user_name[0] }}</span>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Fecha de Préstamo</label>
              <input v-model="modal.form.loan_date" type="date" class="form-control" />
            </div>
            <div class="form-group">
              <label class="form-label">Fecha de Devolución</label>
              <input v-model="modal.form.return_date" type="date" class="form-control" />
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
import { computed, onMounted } from 'vue'
import { useLoanController } from '@/controllers/loanController'

const {
  loans, books, loading, filters, modal, returnConfirm,
  fetchLoans, openCreate, createLoan, confirmReturn, returnLoan,
} = useLoanController()

const loanList = computed(() => loans.value?.data ?? [])

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' })
}

function statusBadge(s) {
  return { 'Activo': 'badge-info', 'Devuelto': 'badge-success', 'Atrasado': 'badge-danger' }[s] || 'badge-gray'
}

onMounted(() => fetchLoans())
</script>

<style scoped>
.filters-row { display: flex; gap: var(--sp-4); align-items: center; flex-wrap: wrap; }
.pagination  { display: flex; align-items: center; justify-content: center; gap: var(--sp-5); padding: var(--sp-4) var(--sp-6); border-top: 1px solid var(--c-border-light); }
</style>
