<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="barcode" /> Libros escaneados (Monitoreo IoT)</h1>
        <p>Telemetría de lecturas de nodos celulares y estaciones de auditoría en tiempo real.</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-ghost" @click="exportCsv" :disabled="!scans.length">
          <font-awesome-icon icon="file-export" /> Exportar CSV
        </button>
        <RouterLink to="/scanner" class="btn btn-primary">
          <font-awesome-icon icon="barcode" /> Abrir escáner
        </RouterLink>
      </div>
    </div>

    <!-- Panel de Indicadores y Telemetría IoT -->
    <div class="stats-grid" style="margin-bottom:var(--sp-4)">
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

    <!-- Gráfica de Distribución de Nodos IoT y Telemetría -->
    <div class="card tele-card" style="margin-bottom: var(--sp-5);">
      <div class="tele-header">
        <h4><font-awesome-icon icon="chart-bar" /> Distribución de Dispositivos Emisores (Nodos IoT)</h4>
        <small class="muted" v-if="stats.last_sync">
          Última sincronización telemétrica: {{ timeAgo(stats.last_sync) }}
        </small>
      </div>
      <div class="tele-body">
        <div class="tele-bar-wrap">
          <div class="tele-bar-progress">
            <div
              class="bar-seg mobile"
              :style="{ width: mobilePercentage + '%' }"
              :title="`Celulares: ${stats.device_counts?.Celular || 0}`"
            ></div>
            <div
              class="bar-seg pc"
              :style="{ width: pcPercentage + '%' }"
              :title="`PC / Laptops: ${stats.device_counts?.['PC / Laptop'] || 0}`"
            ></div>
            <div
              class="bar-seg other"
              :style="{ width: otherPercentage + '%' }"
              :title="`Otros: ${stats.device_counts?.Otros || 0}`"
            ></div>
          </div>
        </div>
        <div class="tele-legend">
          <span class="legend-item">
            <span class="dot mobile"></span>
            <strong>Celular:</strong> {{ stats.device_counts?.Celular || 0 }} ({{ mobilePercentage }}%)
          </span>
          <span class="legend-item">
            <span class="dot pc"></span>
            <strong>PC / Laptop:</strong> {{ stats.device_counts?.['PC / Laptop'] || 0 }} ({{ pcPercentage }}%)
          </span>
          <span class="legend-item" v-if="stats.device_counts?.Otros">
            <span class="dot other"></span>
            <strong>Otros:</strong> {{ stats.device_counts?.Otros || 0 }} ({{ otherPercentage }}%)
          </span>
        </div>
      </div>
    </div>

    <!-- Galería de Capturas IoT (Imágenes reales de la cámara) -->
    <div v-if="photos.length" class="card" style="margin-bottom: var(--sp-5);">
      <div class="card-header gallery-toolbar">
        <div>
          <h3 style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
            <font-awesome-icon icon="camera" /> Galería de Capturas Físicas (Nodo IoT)
          </h3>
          <span class="muted" style="font-size:0.85rem">Evidencias fotográficas tomadas en tiempo real</span>
        </div>
        <!-- Filtros de la Galería -->
        <div class="gallery-filters">
          <button
            class="filter-chip"
            :class="{ active: galleryFilter === 'all' }"
            @click="galleryFilter = 'all'"
          >
            Todas ({{ photos.length }})
          </button>
          <button
            class="filter-chip"
            :class="{ active: galleryFilter === 'new' }"
            @click="galleryFilter = 'new'"
          >
            Libros nuevos
          </button>
          <button
            class="filter-chip"
            :class="{ active: galleryFilter === 'mobile' }"
            @click="galleryFilter = 'mobile'"
          >
            Solo Celular
          </button>
        </div>
      </div>

      <div class="gallery-grid" v-if="filteredPhotos.length">
        <div v-for="s in filteredPhotos" :key="'photo-'+s.id" class="gallery-item" @click="openDetail(s)">
          <img :src="s.photo_url" :alt="s.title" loading="lazy" />
          <div class="gallery-caption">
            <span class="title-truncate">{{ s.title }}</span>
            <span class="device-tag">{{ formatDeviceBadge(s.device_info) }}</span>
          </div>
        </div>
      </div>
      <div v-else class="empty-state" style="padding:24px">
        <p class="muted">No hay capturas con el filtro seleccionado.</p>
      </div>
    </div>

    <!-- Tabla Histórica de Escaneos -->
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
              <th>Libro</th>
              <th>ISBN</th>
              <th>Operación</th>
              <th>Dispositivo (Nodo IoT)</th>
              <th>Estado</th>
              <th>Fecha y Hora</th>
              <th style="text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in scans" :key="s.id">
              <td>
                <div class="book-cell">
                  <div class="cover" :style="coverStyle(s)"></div>
                  <div>
                    <div class="t">{{ s.title }}</div>
                    <div class="a">{{ s.author }}</div>
                  </div>
                </div>
              </td>
              <td class="mono">{{ s.isbn }}</td>
              <td>
                <span v-if="s.result === 'nuevo'" class="chip new">Registro Nuevo</span>
                <span v-else-if="s.result === 'prestamo'" class="chip loan-event">Préstamo</span>
                <span v-else-if="s.result === 'devolucion'" class="chip loan-event">Devolución</span>
                <span v-else class="chip gray">Auditoría / Verificación</span>
              </td>
              <td>
                <!-- En la tabla se muestra limpiamente 'Celular' o 'PC' y en el detalle se especifica el modelo -->
                <span class="badge-device" :class="isMobileDevice(s.device_info) ? 'badge-mobile' : 'badge-pc'">
                  {{ formatDeviceBadge(s.device_info) }}
                </span>
              </td>
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

    <!-- Modal de detalle con evidencia y modelo específico -->
    <div v-if="detail.visible" class="overlay" @click.self="closeDetail">
      <div class="modal-box">
        <div class="modal-head">
          <h3>Detalle del registro IoT</h3>
          <button class="close" @click="closeDetail" aria-label="Cerrar">×</button>
        </div>

        <div class="detail">
          <div class="cover big" :style="coverStyle(detail.scan)">
            <img v-if="detail.scan.photo_url" :src="detail.scan.photo_url" class="photo-full" alt="Captura física" />
          </div>
          <div v-if="detail.scan.photo_back_url" class="back-capture">
            <small class="muted">Contraportada</small>
            <img :src="detail.scan.photo_back_url" class="photo-full" alt="Contraportada" />
          </div>
          <div class="detail-info">
            <h2>{{ detail.scan.title }}</h2>
            <div class="muted">{{ detail.scan.author }}</div>
            <div><span class="chip" :class="chipClass(detail.scan.book_status)">{{ detail.scan.book_status || '—' }}</span></div>
            <dl>
              <dt>ISBN</dt><dd class="mono">{{ detail.scan.isbn }}</dd>
              <dt>Editorial</dt><dd>{{ detail.scan.publisher || '—' }}</dd>
              <dt>Dispositivo</dt>
              <!-- Aquí se muestra la especificación detallada del modelo detectado -->
              <dd>
                <strong>{{ detail.scan.device_info || 'Nodo Celular' }}</strong>
              </dd>
              <dt>Escaneado</dt><dd>{{ timeAgo(detail.scan.scanned_at) }} ({{ formatDate(detail.scan.scanned_at) }})</dd>
              <dt>Operación</dt>
              <dd>
                <span v-if="detail.scan.result === 'nuevo'">Registro de libro nuevo en biblioteca</span>
                <span v-else-if="detail.scan.result === 'devolucion'">
                  Devolución registrada por <strong>{{ detail.scan.reader || 'Lector' }}</strong>
                  (portada y contraportada capturadas)
                </span>
                <span v-else-if="detail.scan.result === 'prestamo'">
                  Préstamo asignado a <strong>{{ detail.scan.reader || 'Lector' }}</strong>
                </span>
                <span v-else>Auditoría / Verificación física de inventario</span>
              </dd>
            </dl>
          </div>
        </div>

        <div class="modal-footer" style="margin-top:16px">
          <button class="btn btn-primary" @click="closeDetail">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useScanController } from '@/controllers/scanController'

const { scans, stats, loading, detail, fetchScans, openDetail, closeDetail } = useScanController()

const photos = computed(() => scans.value.filter((s) => Boolean(s.photo_url)))

// Filtro de la galería
const galleryFilter = ref('all')
const filteredPhotos = computed(() => {
  if (galleryFilter.value === 'new') {
    // Filtrar solo el momento exacto en que fue agregado por primera vez (result === 'nuevo')
    // y evitar duplicados de un mismo libro mostrando solo su evento de registro
    const seenBooks = new Set()
    return photos.value.filter((p) => {
      if (p.result !== 'nuevo') return false
      const key = p.isbn || p.book_id
      if (seenBooks.has(key)) return false
      seenBooks.add(key)
      return true
    })
  }
  if (galleryFilter.value === 'mobile') {
    return photos.value.filter((p) => isMobileDevice(p.device_info))
  }
  return photos.value
})

// Porcentajes para la barra de telemetría IoT
const mobileCount = computed(() => Number(stats.device_counts?.Celular ?? stats.device_counts?.['Celular'] ?? 0))
const pcCount     = computed(() => Number(stats.device_counts?.['PC / Laptop'] ?? 0))
const otherCount  = computed(() => Number(stats.device_counts?.Otros ?? 0))

const totalDevices = computed(() => {
  const sum = mobileCount.value + pcCount.value + otherCount.value
  return sum > 0 ? sum : 1
})

const mobilePercentage = computed(() => {
  if (totalDevices.value <= 1 && mobileCount.value === 0) return 0
  return Math.round((mobileCount.value / totalDevices.value) * 100)
})
const pcPercentage = computed(() => {
  if (totalDevices.value <= 1 && pcCount.value === 0) return 0
  return Math.round((pcCount.value / totalDevices.value) * 100)
})
const otherPercentage = computed(() => {
  if (totalDevices.value <= 1 && otherCount.value === 0) return 0
  return Math.max(0, 100 - mobilePercentage.value - pcPercentage.value)
})

function isMobileDevice(info) {
  const d = String(info || '').toLowerCase()
  return d.includes('celular') || d.includes('android') || d.includes('iphone') || d.includes('mobile')
}

// En la tabla muestra limpiamente 'Celular' o 'PC / Laptop'
function formatDeviceBadge(info) {
  if (isMobileDevice(info)) return 'Celular'
  const d = String(info || '').toLowerCase()
  if (d.includes('windows') || d.includes('mac') || d.includes('pc') || d.includes('laptop')) return 'PC / Laptop'
  return 'Nodo IoT'
}

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

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString()
}

// Exportar datos a archivo CSV
function exportCsv() {
  if (!scans.value.length) return
  const headers = ['ID', 'ISBN', 'Título', 'Autor', 'Dispositivo_Nodo', 'Resultado', 'Fecha_Hora']
  const rows = scans.value.map((s) => [
    s.id,
    `"${s.isbn}"`,
    `"${(s.title || '').replace(/"/g, '""')}"`,
    `"${(s.author || '').replace(/"/g, '""')}"`,
    `"${(s.device_info || 'Celular').replace(/"/g, '""')}"`,
    s.result,
    s.scanned_at || '',
  ])
  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e) => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `auditoria_iot_readout_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<style scoped>
.header-actions { display: flex; gap: var(--sp-2); align-items: center; }
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
.chip.new        { background: var(--c-gold-300); color: #5c4400; margin-left: 6px; }
.chip.loan-event { background: #dbeafe; color: #1e40af; margin-left: 6px; }

.badge-device {
  padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 500;
  border: 1px solid var(--c-border);
}
.badge-mobile { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.badge-pc     { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }

/* Barra y Telemetría */
.tele-card { padding: 16px 20px; }
.tele-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.tele-header h4 { margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
.tele-bar-progress {
  display: flex; height: 10px; border-radius: 99px; overflow: hidden; background: #e2e8f0;
  margin-bottom: 10px;
}
.bar-seg.mobile { background: #2563eb; }
.bar-seg.pc     { background: #0284c7; }
.bar-seg.other  { background: #94a3b8; }

.tele-legend { display: flex; gap: 18px; flex-wrap: wrap; font-size: 0.85rem; }
.legend-item { display: flex; align-items: center; gap: 6px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot.mobile { background: #2563eb; }
.dot.pc     { background: #0284c7; }
.dot.other  { background: #94a3b8; }

/* Toolbar de Galería */
.gallery-toolbar {
  display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;
  flex-wrap: wrap; gap: 12px;
}
.gallery-filters { display: flex; gap: 6px; }
.filter-chip {
  padding: 4px 10px; border-radius: 99px; border: 1px solid var(--c-border);
  background: #fff; font-size: 0.75rem; cursor: pointer; transition: all 0.15s;
}
.filter-chip.active {
  background: var(--c-primary); color: #fff; border-color: var(--c-primary);
}
.gallery-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 12px; padding: 16px 20px;
}
.gallery-item {
  border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--c-border);
  cursor: pointer; background: #f8fafc; transition: transform 0.15s, box-shadow 0.15s;
  display: flex; flex-direction: column;
}
.gallery-item:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.gallery-item img {
  width: 100%; aspect-ratio: 3/4; object-fit: cover; background: #e2e8f0;
}
.gallery-caption { padding: 6px 8px; display: flex; flex-direction: column; gap: 2px; }
.title-truncate {
  font-size: 0.8rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.device-tag {
  font-size: 0.7rem; color: var(--c-text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.photo-full { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }
.back-capture { width: 120px; display: flex; flex-direction: column; gap: 4px; }
.back-capture .photo-full { height: 168px; }

/* Modal */
.overlay {
  position: fixed; inset: 0; background: rgba(10, 32, 64, .55);
  display: flex; align-items: center; justify-content: center; z-index: 300; padding: var(--sp-4);
}
.modal-box {
  background: #fff; border-radius: var(--radius-lg); width: 100%; max-width: 560px;
  max-height: 90vh; overflow-y: auto; padding: var(--sp-6); box-shadow: var(--shadow-xl);
}
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4); }
.close { font-size: 1.8rem; line-height: 1; width: 44px; height: 44px; color: var(--c-text-secondary); border: none; background: none; cursor: pointer; }
.detail { display: flex; gap: var(--sp-5); flex-wrap: wrap; }
.detail-info { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: var(--sp-2); }
.detail-info h2 { font-size: 1.3rem; }
dl { display: grid; grid-template-columns: 90px 1fr; gap: 4px 12px; margin-top: var(--sp-2); }
dt { color: var(--c-text-muted); font-size: .85rem; }
.modal-footer { display: flex; justify-content: flex-end; }
</style>