<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="barcode" /> Libros escaneados</h1>
        <p>Registrados desde el celular. Se actualiza automáticamente.</p>
      </div>
      <RouterLink to="/scanner" class="btn btn-primary">
        <font-awesome-icon icon="barcode" /> Abrir escáner
      </RouterLink>
    </div>

    <div class="stats-grid" style="margin-bottom:var(--sp-5)">
      <div class="stat-card">
        <div class="stat-icon blue"><font-awesome-icon icon="barcode" /></div>
        <div class="stat-info">
          <div class="stat-value"><span>{{ stats.today }}</span></div>
          <div class="stat-label">Escaneados hoy</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon gold"><font-awesome-icon icon="book" /></div>
        <div class="stat-info">
          <div class="stat-value"><span>{{ stats.new_today }}</span></div>
          <div class="stat-label">Libros nuevos</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><font-awesome-icon icon="clipboard-list" /></div>
        <div class="stat-info">
          <div class="stat-value"><span>{{ stats.existing_today }}</span></div>
          <div class="stat-label">Ya existentes</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><font-awesome-icon icon="info-circle" /></div>
        <div class="stat-info">
          <div class="stat-value"><span>{{ stats.available }}</span></div>
          <div class="stat-label">Disponibles para prestar</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div v-if="loading" class="spinner-overlay"><div class="spinner spinner-lg"></div></div>

      <div v-else-if="!scans.length" class="empty-state">
        <div class="empty-state-icon"><font-awesome-icon icon="barcode" /></div>
        <h3>Aún no hay libros escaneados</h3>
        <p>Usa el botón "Abrir escáner" para registrar el primero.</p>
      </div>

      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Libro</th><th>ISBN</th><th>Estado</th><th>Escaneado</th>
              <th style="text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in scans" :key="s.id">
              <td>
                <div class="book-cell">
                  <div class="cover" :style="coverStyle(s)"></div>
                  <div>
                    <div class="t">
                      {{ s.title }}
                      <span v-if="s.result === 'nuevo'" class="chip new">Nuevo</span>
                    </div>
                    <div class="a">{{ s.author }}</div>
                  </div>
                </div>
              </td>
              <td class="mono">{{ s.isbn }}</td>
              <td><span class="chip" :class="chipClass(s.book_status)">{{ s.book_status || '—' }}</span></td>
              <td class="muted">{{ timeAgo(s.scanned_at) }}</td>
              <td style="text-align:right">
                <button class="btn btn-ghost btn-sm" @click="openDetail(s)">Ver detalle</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de detalle -->
    <div v-if="detail.visible" class="overlay" @click.self="closeDetail">
      <div class="modal-box">
        <div class="modal-head">
          <h3>Detalle del libro</h3>
          <button class="close" @click="closeDetail" aria-label="Cerrar">×</button>
        </div>

        <div class="detail">
          <div class="cover big" :style="coverStyle(detail.scan)"></div>
          <div class="detail-info">
            <h2>{{ detail.scan.title }}</h2>
            <div class="muted">{{ detail.scan.author }}</div>
            <div><span class="chip" :class="chipClass(detail.scan.book_status)">{{ detail.scan.book_status || '—' }}</span></div>
            <dl>
              <dt>ISBN</dt><dd class="mono">{{ detail.scan.isbn }}</dd>
              <dt>Editorial</dt><dd>{{ detail.scan.publisher || '—' }}</dd>
              <dt>Escaneado</dt><dd>{{ timeAgo(detail.scan.scanned_at) }}</dd>
            </dl>
          </div>
        </div>

        <div class="lend" v-if="auth.can('loans.manage')">
          <h4>Prestar este libro</h4>

          <template v-if="detail.scan.book_status === 'Disponible'">
            <label class="filter-label">Nombre del lector</label>
            <input v-model="detail.form.user_name" class="form-control" placeholder="Nombre y apellido" />
            <p v-if="detail.errors.user_name" class="err">{{ detail.errors.user_name[0] }}</p>

            <label class="filter-label">Fecha de devolución</label>
            <input v-model="detail.form.return_date" type="date" class="form-control" />
            <p v-if="detail.errors.return_date" class="err">{{ detail.errors.return_date[0] }}</p>

            <div class="actions">
              <button class="btn btn-ghost" @click="closeDetail">Cancelar</button>
              <button class="btn btn-primary" :disabled="detail.submitting || !detail.form.user_name" @click="lendBook">
                {{ detail.submitting ? 'Registrando…' : 'Prestar libro' }}
              </button>
            </div>
          </template>

          <p v-else class="muted">
            Este libro no está disponible para préstamo (estado: {{ detail.scan.book_status || 'desconocido' }}).
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '@/store/auth'
import { useScanController } from '@/controllers/scanController'

const auth = useAuthStore()
const { scans, stats, loading, detail, fetchScans, openDetail, closeDetail, lendBook } = useScanController()

let timer = null
onMounted(() => {
  fetchScans()
  timer = setInterval(() => fetchScans(true), 5000)
})
onBeforeUnmount(() => clearInterval(timer))

const chipClass = (st) => ({ Disponible: 'ok', Prestado: 'loan', Mantenimiento: 'maint' }[st] || 'gray')

const coverStyle = (s) => (s?.cover_url ? { backgroundImage: `url(${s.cover_url})` } : {})

function timeAgo(iso) {
  if (!iso) return '—'
  const m = Math.floor((Date.now() - new Date(iso).getTime()) / 60000)
  if (m < 1) return 'Hace un momento'
  if (m < 60) return `Hace ${m} min`
  const h = Math.floor(m / 60)
  if (h < 24) return `Hace ${h} h`
  return new Date(iso).toLocaleDateString()
}
</script>

<style scoped>
.mono  { font-family: monospace; font-size: .82rem; }
.muted { color: var(--c-text-secondary); font-size: .85rem; }
.book-cell { display: flex; gap: var(--sp-3); align-items: center; }
.t { font-weight: 600; }
.a { font-size: .8rem; color: var(--c-text-secondary); }
.cover {
  width: 40px; height: 56px; flex-shrink: 0; border-radius: 4px;
  background: var(--c-border) center / cover no-repeat;
}
.cover.big { width: 120px; height: 168px; }

.chip { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: .75rem; font-weight: 600; }
.chip.ok    { background: #dcfce7; color: #166534; }
.chip.loan  { background: #dbeafe; color: #1e40af; }
.chip.maint { background: #fef3c7; color: #92400e; }
.chip.gray  { background: var(--c-border); color: var(--c-text-secondary); }
.chip.new   { background: var(--c-gold-300); color: #5c4400; margin-left: 6px; }

.overlay {
  position: fixed; inset: 0; background: rgba(10, 32, 64, .55);
  display: flex; align-items: center; justify-content: center; z-index: 300; padding: var(--sp-4);
}
.modal-box {
  background: #fff; border-radius: var(--radius-lg); width: 100%; max-width: 560px;
  max-height: 90vh; overflow-y: auto; padding: var(--sp-6); box-shadow: var(--shadow-xl);
}
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4); }
.close { font-size: 1.8rem; line-height: 1; width: 44px; height: 44px; color: var(--c-text-secondary); }
.detail { display: flex; gap: var(--sp-5); flex-wrap: wrap; }
.detail-info { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: var(--sp-2); }
.detail-info h2 { font-size: 1.3rem; }
dl { display: grid; grid-template-columns: 90px 1fr; gap: 4px 12px; margin-top: var(--sp-2); }
dt { color: var(--c-text-muted); font-size: .85rem; }
.lend {
  margin-top: var(--sp-5); padding-top: var(--sp-4); border-top: 1px solid var(--c-border);
  display: flex; flex-direction: column; gap: var(--sp-2);
}
.err { color: var(--c-danger); font-size: .8rem; }
.actions { display: flex; justify-content: flex-end; gap: var(--sp-2); margin-top: var(--sp-3); }
</style>