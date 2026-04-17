<template>
  <div>
    <!-- Header -->
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="book" /> Catálogo de Libros</h1>
        <p>Administra el inventario de libros y sincroniza con GLPI.</p>
      </div>
      <div style="display:flex;gap:var(--sp-2)">
        <button class="btn btn-ghost" @click="handleSyncAll" :disabled="syncing">
          <font-awesome-icon icon="sync" :spin="syncing" />
          {{ syncing ? 'Sincronizando...' : 'Sincronizar con GLPI' }}
        </button>
        <button id="btn-new-book" class="btn btn-primary" @click="openCreate">
          <font-awesome-icon icon="plus" /> Nuevo Libro
        </button>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card" style="margin-bottom:var(--sp-5)">
      <div class="card-body" style="padding:var(--sp-4)">
        <div class="filters-row">
          <div class="search-bar" style="flex:1">
            <span class="search-bar-icon"><font-awesome-icon icon="search" /></span>
            <input
              v-model="searchQuery"
              type="text"
              class="form-control"
              placeholder="Buscar por título, autor o ISBN…"
              @input="onSearch"
            />
          </div>
          <select v-model="filters.status" class="form-control" style="width:180px" @change="fetchBooks()">
            <option value="">Todos los estados</option>
            <option value="Disponible">Disponible</option>
            <option value="Prestado">Prestado</option>
            <option value="Mantenimiento">Mantenimiento</option>
          </select>
          <select v-model="filters.genre_id" class="form-control" style="width:160px" @change="fetchBooks()">
            <option value="">Todos los géneros</option>
            <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div v-if="loading" class="spinner-overlay">
        <div class="spinner spinner-lg"></div>
      </div>

      <div v-else-if="!bookList.length" class="empty-state">
        <div class="empty-state-icon">
          <font-awesome-icon icon="book" />
        </div>
        <h3>No se encontraron libros</h3>
        <p>Agrega tu primer libro con el botón "Nuevo Libro".</p>
      </div>

      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Título / Autor</th>
              <th>ISBN</th>
              <th>Género</th>
              <th>Editorial</th>
              <th>Estado</th>
              <th>GLPI</th>
              <th style="text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="book in bookList" :key="book.id">
              <td style="color:var(--c-text-muted);font-size:.8rem">{{ book.id }}</td>
              <td>
                <div style="font-weight:600">{{ book.title }}</div>
                <div style="font-size:.8rem;color:var(--c-text-secondary)">{{ book.author }}</div>
              </td>
              <td style="font-family:monospace;font-size:.82rem">{{ book.isbn }}</td>
              <td>{{ book.genre?.name || '—' }}</td>
              <td>{{ book.publisher?.name || '—' }}</td>
              <td>
                <span class="badge" :class="statusBadge(book.status)">{{ book.status }}</span>
              </td>
              <td>
                <span v-if="book.glpi_id" class="badge badge-success" :title="`GLPI ID: ${book.glpi_id}`">✓ Sync</span>
                <span v-else class="badge badge-gray">—</span>
              </td>
              <td>
                <div class="table-actions">
                  <button class="btn btn-ghost btn-icon" @click="openEdit(book)" title="Editar">
                    <font-awesome-icon icon="edit" />
                  </button>
                  <button
                    class="btn btn-ghost btn-icon"
                    :class="{ 'text-warning': book.status === 'Mantenimiento' }"
                    @click="openReportModal(book)"
                    title="Reportar Daño / Ver Info"
                  >
                    <font-awesome-icon :icon="book.status === 'Mantenimiento' ? 'exclamation-circle' : 'exclamation-triangle'" />
                  </button>
                  <button
                    v-if="auth.isAdmin"
                    class="btn btn-ghost btn-icon"
                    @click="confirmDelete(book)"
                    title="Eliminar"
                  >
                    <font-awesome-icon icon="trash-alt" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="books && books.last_page > 1" class="pagination">
        <button
          class="btn btn-ghost btn-sm"
          :disabled="books.current_page === 1"
          @click="fetchBooks(books.current_page - 1)"
        >
          <font-awesome-icon icon="chevron-left" /> Anterior
        </button>
        <span style="font-size:.85rem;color:var(--c-text-secondary)">
          Página {{ books.current_page }} de {{ books.last_page }}
        </span>
        <button
          class="btn btn-ghost btn-sm"
          :disabled="books.current_page === books.last_page"
          @click="fetchBooks(books.current_page + 1)"
        >
          Siguiente <font-awesome-icon icon="chevron-right" />
        </button>
      </div>
    </div>

    <!-- Modal Crear / Editar -->
    <div v-if="modal.visible" class="modal-backdrop" @click.self="modal.visible = false">
      <div id="modal-book" class="modal">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon :icon="modal.type === 'create' ? 'circle-plus' : 'edit'" />
            {{ modal.type === 'create' ? ' Nuevo Libro' : ' Editar Libro' }}
          </h3>
          <button class="btn btn-ghost btn-icon" @click="modal.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Título *</label>
              <input v-model="modal.form.title" type="text" class="form-control" placeholder="Título del libro" />
              <span v-if="modal.errors.title" class="form-error">{{ modal.errors.title[0] }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Autor *</label>
              <input v-model="modal.form.author" type="text" class="form-control" placeholder="Nombre del autor" />
              <span v-if="modal.errors.author" class="form-error">{{ modal.errors.author[0] }}</span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">ISBN *</label>
              <input v-model="modal.form.isbn" type="text" class="form-control" placeholder="978-XXXXXXXXXX" />
              <span v-if="modal.errors.isbn" class="form-error">{{ modal.errors.isbn[0] }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Edición</label>
              <input v-model="modal.form.edition" type="text" class="form-control" placeholder="Ej: 2da Edición" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Género</label>
              <select v-model="modal.form.genre_id" class="form-control">
                <option value="">Seleccione un género...</option>
                <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Editorial</label>
              <select v-model="modal.form.publisher_id" class="form-control">
                <option value="">Seleccione una editorial...</option>
                <option v-for="p in publishers" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Estado</label>
              <select v-model="modal.form.status" class="form-control">
                <option value="Disponible">Disponible</option>
                <option value="Prestado">Prestado</option>
                <option value="Mantenimiento">Mantenimiento</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Sinopsis</label>
            <textarea v-model="modal.form.synopsis" class="form-control" placeholder="Descripción del libro…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="modal.visible = false">Cancelar</button>
          <button
            id="btn-save-book"
            class="btn btn-primary"
            @click="saveBook"
            :disabled="modal.submitting"
          >
            <span v-if="modal.submitting" class="spinner"></span>
            <span v-else>{{ modal.type === 'create' ? 'Crear Libro' : 'Guardar Cambios' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div v-if="deleteConfirm.visible" class="modal-backdrop" @click.self="deleteConfirm.visible = false">
      <div id="modal-delete" class="modal" style="max-width:400px">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon icon="trash-alt" /> Confirmar Eliminación
          </h3>
          <button class="btn btn-ghost btn-icon" @click="deleteConfirm.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <p>¿Estás seguro de que deseas eliminar el libro <strong>{{ deleteConfirm.bookTitle }}</strong>? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="deleteConfirm.visible = false">Cancelar</button>
          <button class="btn btn-danger" @click="deleteBook">Eliminar</button>
        </div>
      </div>
    </div>

    <!-- Modal Reportar Incidencia -->
    <div v-if="reportModal.visible" class="modal-backdrop" @click.self="reportModal.visible = false">
      <div id="modal-report" class="modal" style="max-width:500px">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon icon="exclamation-triangle" /> Reportar Incidencia
          </h3>
          <button class="btn btn-ghost btn-icon" @click="reportModal.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <div v-if="reportModal.book" style="margin-bottom:var(--sp-4);padding:var(--sp-3);background:var(--c-bg-page);border-radius:var(--radius-sm)">
             <div style="font-weight:600;font-size:.9rem">{{ reportModal.book.title }}</div>
             <div style="font-size:.8rem;color:var(--c-text-secondary)">{{ reportModal.book.author }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Prioridad *</label>
            <select v-model="reportModal.form.priority" class="form-control">
              <option value="Baja">Baja (Minor Issue)</option>
              <option value="Media">Media (Standard)</option>
              <option value="Alta">Alta (Urgent Repair)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Descripción del problema *</label>
            <textarea
              v-model="reportModal.form.description"
              class="form-control"
              rows="4"
              placeholder="Ej: El libro tiene varias hojas sueltas y la portada dañada..."
            ></textarea>
            <span v-if="reportModal.errors.description" class="form-error">{{ reportModal.errors.description[0] }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Evidencia Fotográfica (Opcional)</label>
            <input type="file" @change="handleFileChange" accept="image/jpeg,image/png,image/webp" class="form-control" />
            <p style="font-size:.75rem;color:var(--c-text-muted);margin-top:4px">Máximo 5MB. Formatos: JPG, PNG, WEBP.</p>
            <span v-if="reportModal.errors.image" class="form-error">{{ reportModal.errors.image[0] }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="reportModal.visible = false">Cancelar</button>
          <button class="btn btn-warning" @click="submitReport" :disabled="reportModal.submitting">
            <span v-if="reportModal.submitting" class="spinner"></span>
            <span v-else>Enviar a GLPI</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useBookController } from '@/controllers/bookController'
import { glpiService } from '@/services/glpiService'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/store/auth'

const toast = useToast()
const auth = useAuthStore()
const {
  books, genres, publishers, loading, filters,
  modal, deleteConfirm, reportModal,
  fetchBooks, fetchMasters, openCreate, openEdit, saveBook,
  confirmDelete, deleteBook, openReportModal, submitReport,
} = useBookController()

const syncing = ref(false)
const searchQuery = ref('')

async function handleSyncAll() {
  syncing.value = true
  try {
    const { data } = await glpiService.syncAll()
    toast.success(`${data.message} Locales: +${data.details.created_local}, GLPI: +${data.details.created_glpi}`)
    await fetchBooks()
  } catch (err) {
    toast.error('Error durante la sincronización.')
  } finally {
    syncing.value = false
    await fetchMasters() // Recargar maestros tras sync
  }
}

const bookList = computed(() => books.value?.data ?? [])

let searchTimeout
function onSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    await fetchBooks()
  }, 400)
}

function handleFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    reportModal.form.image = file
  }
}

function statusBadge(status) {
  return {
    'Disponible':   'badge-success',
    'Prestado':     'badge-warning',
    'Mantenimiento':'badge-danger',
  }[status] || 'badge-gray'
}

onMounted(async () => {
  fetchBooks()
  fetchMasters()
})
</script>

<style scoped>
.filters-row {
  display: flex;
  gap: var(--sp-4);
  align-items: center;
  flex-wrap: wrap;
}

.table-actions {
  display: flex;
  gap: var(--sp-1);
  justify-content: flex-end;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-5);
  padding: var(--sp-4) var(--sp-6);
  border-top: 1px solid var(--c-border-light);
}
</style>
