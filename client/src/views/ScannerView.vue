<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="barcode" /> Escáner IoT de Libros</h1>
        <p>Selecciona la operación, enfoca el código ISBN con la cámara del celular.</p>
      </div>
      <RouterLink to="/scans" class="btn btn-ghost">
        <font-awesome-icon icon="clipboard-list" /> Ver registro IoT
      </RouterLink>
    </div>

    <!-- Pestañas de Modo de Operación -->
    <div class="mode-tabs">
      <button
        class="mode-tab"
        :class="{ active: currentMode === 'register' }"
        @click="switchMode('register')"
      >
        <font-awesome-icon icon="book" />
        <div>
          <strong>1. Registrar libro</strong>
          <small>Captura código, autocompleta con Open Library y pide confirmación</small>
        </div>
      </button>

      <button
        class="mode-tab"
        :class="{ active: currentMode === 'loan' }"
        @click="switchMode('loan')"
      >
        <font-awesome-icon icon="user-check" />
        <div>
          <strong>2. Prestar libro</strong>
          <small>Valida disponibilidad y muestra la portada oficial</small>
        </div>
      </button>

      <button
        class="mode-tab"
        :class="{ active: currentMode === 'check' }"
        @click="switchMode('check')"
      >
        <font-awesome-icon icon="search" />
        <div>
          <strong>3. Consultar estado / Auditar</strong>
          <small>Verifica si está Disponible, Prestado o No registrado</small>
        </div>
      </button>
    </div>

    <div class="card">
      <div class="card-body scanner-body">
        <!-- Contenedor del video de la cámara -->
        <div id="reader" class="reader" v-show="!previewModal.visible && !loanModal.visible && !checkModal.visible"></div>

        <p v-if="cameraError" class="msg-error">{{ cameraError }}</p>

        <div class="scanner-actions" v-if="!previewModal.visible && !loanModal.visible && !checkModal.visible">
          <button v-if="!cameraOn" class="btn btn-primary" @click="startCamera">
            <font-awesome-icon icon="camera" /> Iniciar cámara
          </button>
          <template v-else>
            <button class="btn btn-ghost" @click="stopCamera">
              Detener cámara
            </button>
            <button v-if="hasTorch" class="btn btn-ghost" @click="toggleTorch">
              <font-awesome-icon icon="lightbulb" /> {{ torchOn ? 'Apagar linterna' : 'Encender linterna' }}
            </button>
          </template>
        </div>

        <div v-if="busy" class="msg-info">
          <span class="spinner-sm"></span> Procesando lectura IoT con la cámara…
        </div>

        <!-- Alerta de resultado rápido -->
        <div v-if="result" class="result" :class="result.ok ? 'ok' : 'bad'">
          <div v-if="result.cover" class="result-cover" :style="{ backgroundImage: `url(${result.cover})` }"></div>
          <div>
            <strong>{{ result.label }}</strong>
            <div v-if="result.title">{{ result.title }}</div>
            <div v-if="result.author" class="muted">{{ result.author }}</div>
            <div v-if="result.isbn" class="muted mono">{{ result.isbn }}</div>
            <div v-if="result.message" class="muted">{{ result.message }}</div>
          </div>
        </div>

        <!-- Entrada manual de ISBN alternativa -->
        <div class="manual" v-if="!previewModal.visible && !loanModal.visible && !checkModal.visible">
          <label class="filter-label">¿Sin cámara? Escribe el código ISBN</label>
          <div class="manual-row">
            <input v-model="manualIsbn" class="form-control" inputmode="numeric" placeholder="978..." />
            <button class="btn btn-ghost" :disabled="busy || !manualIsbn" @click="handleManualLookup">
              Buscar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: CONFIRMAR REGISTRO DE NUEVO LIBRO                    -->
    <!-- ============================================================== -->
    <div v-if="previewModal.visible" class="overlay" @click.self="cancelPreview">
      <div class="modal-box">
        <div class="modal-head">
          <h3>
            <font-awesome-icon icon="book" />
            {{ previewModal.isNew ? 'Confirmar Registro de Libro' : 'Libro ya existente en Catálogo' }}
          </h3>
          <button class="close" @click="cancelPreview">×</button>
        </div>

        <div class="preview-layout">
          <!-- Imágenes: Portada Oficial Open Library + Captura Física de la Cámara -->
          <div class="preview-photos-col">
            <div class="photo-card">
              <label class="filter-label">Portada oficial (Open Library):</label>
              <img v-if="previewModal.officialCover" :src="previewModal.officialCover" class="cover-img" alt="Portada oficial" />
              <div v-else class="preview-placeholder">Sin carátula oficial</div>
            </div>

            <div class="photo-card" v-if="previewModal.capturedPhotoUrl">
              <label class="filter-label">Captura física (Cámara celular):</label>
              <img :src="previewModal.capturedPhotoUrl" class="cover-img captured" alt="Captura física" />
              <small class="muted">Dispositivo: {{ previewModal.device }}</small>
            </div>
          </div>

          <!-- Campos editables antes de guardar en BD -->
          <div class="preview-fields">
            <div class="form-group">
              <label class="filter-label">Código ISBN-13</label>
              <input :value="previewModal.form.isbn" class="form-control mono" readonly />
            </div>

            <div class="form-group">
              <label class="filter-label">Título del libro *</label>
              <input v-model="previewModal.form.title" class="form-control" placeholder="Título" />
            </div>

            <div class="form-group">
              <label class="filter-label">Autor(es) *</label>
              <input v-model="previewModal.form.author" class="form-control" placeholder="Autor" />
            </div>

            <div class="form-group">
              <label class="filter-label">Editorial</label>
              <input v-model="previewModal.form.publisher" class="form-control" placeholder="Editorial (opcional)" />
            </div>

            <div v-if="previewModal.isNew" class="alert-info">
              Este libro no existía en el catálogo. Al confirmar, se creará en la base de datos con su carátula de Open Library y la evidencia del escáner.
            </div>
            <div v-else class="alert-warn">
              Este libro ya se encuentra en el catálogo. Al confirmar se registrará este escaneo como evento de auditoría IoT.
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-ghost" @click="cancelPreview" :disabled="busy">
            Cancelar / Reintentar
          </button>
          <button class="btn btn-primary" :disabled="busy || !previewModal.form.title" @click="confirmRegistration">
            {{ busy ? 'Guardando...' : 'Confirmar y Guardar en BD' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: GESTIÓN DE PRÉSTAMO DIRECTO TRAS ESCANEO             -->
    <!-- ============================================================== -->
    <div v-if="loanModal.visible" class="overlay" @click.self="cancelLoan">
      <div class="modal-box">
        <div class="modal-head">
          <h3><font-awesome-icon icon="user-check" /> Asignar Préstamo de Libro</h3>
          <button class="close" @click="cancelLoan">×</button>
        </div>

        <div class="loan-book-container">
          <!-- Portada Oficial Asignada por Open Library -->
          <div class="loan-cover-wrap">
            <img v-if="loanModal.book.cover_url" :src="loanModal.book.cover_url" class="loan-cover" alt="Portada oficial" />
            <div v-else class="preview-placeholder">Sin carátula</div>
          </div>

          <div class="loan-book-details">
            <h4>{{ loanModal.book.title }}</h4>
            <p class="muted">{{ loanModal.book.author }}</p>
            <p class="mono muted">ISBN: {{ loanModal.book.isbn }}</p>
            <div>
              Estado actual: 
              <span class="chip" :class="loanModal.book.status === 'Disponible' ? 'ok' : 'bad'">
                {{ loanModal.book.status }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="loanModal.book.status === 'Disponible'" class="loan-form">
          <div class="form-group">
            <label class="filter-label">Nombre del lector o estudiante *</label>
            <input v-model="loanModal.userName" class="form-control" placeholder="Nombre completo del alumno" />
          </div>

          <div class="form-group" style="margin-top:10px">
            <label class="filter-label">Fecha estimada de devolución *</label>
            <input v-model="loanModal.returnDate" type="date" class="form-control" />
          </div>

          <div class="modal-footer" style="margin-top:16px">
            <button class="btn btn-ghost" @click="cancelLoan" :disabled="busy">Cancelar</button>
            <button class="btn btn-primary" :disabled="busy || !loanModal.userName" @click="confirmLoan">
              {{ busy ? 'Prestando...' : 'Confirmar Préstamo' }}
            </button>
          </div>
        </div>

        <div v-else class="alert-warn" style="margin-top:16px">
          El libro no está disponible para préstamo en este momento (Estado: {{ loanModal.book.status }}).
          <div class="modal-footer" style="margin-top:12px">
            <button class="btn btn-ghost" @click="cancelLoan">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: CONSULTAR / AUDITAR ESTADO DE LIBRO (NO REGISTRA)     -->
    <!-- ============================================================== -->
    <div v-if="checkModal.visible" class="overlay" @click.self="closeCheckModal">
      <div class="modal-box">
        <div class="modal-head">
          <h3><font-awesome-icon icon="search" /> Resultado de Auditoría / Consulta</h3>
          <button class="close" @click="closeCheckModal">×</button>
        </div>

        <div class="check-container">
          <div class="check-cover-wrap">
            <img v-if="checkModal.data.cover_url" :src="checkModal.data.cover_url" class="loan-cover" alt="Portada oficial" />
            <div v-else class="preview-placeholder">Sin carátula</div>
          </div>

          <div class="check-details">
            <h3>{{ checkModal.data.title || 'Título desconocido' }}</h3>
            <p class="muted">{{ checkModal.data.author || 'Autor no registrado' }}</p>
            <p class="mono muted">ISBN: {{ checkModal.data.isbn }}</p>

            <!-- Semáforo de Estado en Biblioteca -->
            <div class="status-box" :class="checkStatusClass">
              <div class="status-title">
                <strong>Estado: {{ checkModal.data.status }}</strong>
              </div>
              <div v-if="checkModal.data.status === 'Disponible'" class="status-desc">
                ✔ El libro está en estantería listo para préstamo.
              </div>
              <div v-else-if="checkModal.data.status === 'Prestado'" class="status-desc">
                ⚠ Prestado actualmente a: 
                <strong>{{ checkModal.data.active_loan?.user_name || 'Lector' }}</strong><br />
                Fecha estimada de devolución: 
                <strong>{{ checkModal.data.active_loan?.return_date || 'Sin fecha' }}</strong>
              </div>
              <div v-else class="status-desc">
                ✖ Este ejemplar no está registrado en el catálogo de la biblioteca. Ve al <strong>Modo 1</strong> para agregarlo.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" @click="closeCheckModal">Aceptar / Continuar escaneando</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onBeforeUnmount } from 'vue'
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'
import { scanRepository } from '@/repositories/scanRepository'
import { loanService } from '@/services/loanService'

// Modos: 'register' | 'loan' | 'check'
const currentMode = ref('register')

function switchMode(mode) {
  currentMode.value = mode
  result.value = null
}

const cameraOn    = ref(false)
const cameraError = ref('')
const busy        = ref(false)
const result      = ref(null)
const manualIsbn  = ref('')

let scanner  = null
let lastCode = ''
let lastTime = 0
let pendingPhotoBlob = null

// Modal de confirmación de registro
const previewModal = reactive({
  visible: false,
  isNew: true,
  officialCover: null,
  capturedPhotoUrl: null,
  device: '',
  form: {
    isbn: '',
    title: '',
    author: '',
    publisher: '',
  },
})

// Modal de préstamo
const loanModal = reactive({
  visible: false,
  book: {},
  userName: '',
  returnDate: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
})

// Modal de consulta / auditoría
const checkModal = reactive({
  visible: false,
  data: {},
})

const checkStatusClass = computed(() => {
  if (checkModal.data.status === 'Disponible') return 'status-ok'
  if (checkModal.data.status === 'Prestado') return 'status-loan'
  return 'status-none'
})

const hasTorch = ref(false)
const torchOn   = ref(false)

function getDeviceInfo() {
  const ua = navigator.userAgent || ''
  if (/android/i.test(ua)) {
    const match = ua.match(/Android[^;]+; ([^;)]+)/)
    const model = match ? match[1].trim() : 'Android'
    return `Celular (${model})`
  }
  if (/iphone/i.test(ua)) return 'Celular (iPhone)'
  if (/ipad|ipod/i.test(ua)) return 'Celular/Tablet (iPad/iPod)'
  if (/windows/i.test(ua)) return 'PC / Laptop (Windows)'
  if (/macintosh/i.test(ua)) return 'PC / Laptop (Mac)'
  if (/linux/i.test(ua)) return 'PC / Laptop (Linux)'
  return 'Celular'
}

function captureFrame() {
  const video = document.querySelector('#reader video')
  if (!video || !video.videoWidth) return Promise.resolve(null)
  const scale = Math.min(1, 800 / video.videoWidth)
  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth * scale
  canvas.height = video.videoHeight * scale
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
  return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82))
}

async function startCamera() {
  cameraError.value = ''
  hasTorch.value = false
  torchOn.value = false
  try {
    scanner = new Html5Qrcode('reader', {
      formatsToSupport: [Html5QrcodeSupportedFormats.EAN_13],
      verbose: false,
    })
    await scanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 300, height: 150 } },
      onDecoded,
      () => {},
    )
    cameraOn.value = true

    // Verificar si el dispositivo tiene linterna/flash
    try {
      const track = scanner.getRunningTrack()
      const caps = track?.getCapabilities ? track.getCapabilities() : {}
      if (caps && caps.torch) {
        hasTorch.value = true
      }
    } catch { /* soporte de flash no disponible */ }
  } catch (e) {
    scanner = null
    cameraError.value =
      'No se pudo abrir la cámara. Permite el acceso en el navegador y abre la página con HTTPS.'
  }
}

async function toggleTorch() {
  if (!scanner || !hasTorch.value) return
  try {
    const track = scanner.getRunningTrack()
    if (track) {
      torchOn.value = !torchOn.value
      await track.applyConstraints({
        advanced: [{ torch: torchOn.value }],
      })
    }
  } catch {
    hasTorch.value = false
  }
}

async function stopCamera() {
  if (scanner) {
    try {
      if (torchOn.value) {
        const track = scanner.getRunningTrack()
        await track?.applyConstraints({ advanced: [{ torch: false }] })
      }
    } catch { /* ignorar */ }
    torchOn.value = false
    try { await scanner.stop() } catch { /* ya estaba detenida */ }
    try { scanner.clear() } catch { /* sin acción */ }
    scanner = null
  }
  cameraOn.value = false
  hasTorch.value = false
}

async function onDecoded(text) {
  const now = Date.now()
  if (busy.value || (text === lastCode && now - lastTime < 3500)) return
  lastCode = text
  lastTime = now
  busy.value = true

  // Capturar el fotograma inmediatamente
  const photoBlob = await captureFrame()
  pendingPhotoBlob = photoBlob
  feedback(true)

  // Delay de confirmación visual (800ms) para que el usuario perciba la captura
  await new Promise((resolve) => setTimeout(resolve, 800))

  processScan(text, photoBlob)
}

function handleManualLookup() {
  if (!manualIsbn.value) return
  processScan(manualIsbn.value.trim(), null)
}

async function processScan(isbn, photoBlob) {
  busy.value = true
  result.value = null

  try {
    // Consultar información sin alterar la base de datos
    const lookup = await scanRepository.lookup(isbn)

    if (currentMode.value === 'register') {
      // Modo 1: Registrar con confirmación previa
      previewModal.isNew = !lookup.exists
      previewModal.device = getDeviceInfo()
      previewModal.form.isbn = isbn
      previewModal.form.title = lookup.title || ''
      previewModal.form.author = lookup.author || ''
      previewModal.form.publisher = lookup.publisher || ''
      previewModal.officialCover = lookup.cover_url || null
      
      if (photoBlob) {
        previewModal.capturedPhotoUrl = URL.createObjectURL(photoBlob)
      } else {
        previewModal.capturedPhotoUrl = null
      }
      previewModal.visible = true
      stopCamera()
    } else if (currentMode.value === 'loan') {
      // Modo 2: Prestar
      if (!lookup.exists) {
        result.value = {
          ok: false,
          label: 'Libro no registrado',
          message: 'Primero debes registrar este libro en el catálogo (Modo 1).',
          isbn,
        }
      } else {
        loanModal.book = {
          id: lookup.book_id,
          title: lookup.title,
          author: lookup.author,
          isbn: lookup.isbn,
          cover_url: lookup.cover_url,
          status: lookup.status || 'Disponible',
        }
        loanModal.userName = ''
        loanModal.visible = true
        stopCamera()
      }
    } else {
      // Modo 3: Consultar / Auditar (NUNCA crea el libro)
      checkModal.data = lookup
      checkModal.visible = true
      stopCamera()
    }
  } catch (err) {
    result.value = {
      ok: false,
      label: 'Error en lectura',
      message: err.response?.data?.message || 'No se pudo verificar el código.',
    }
  } finally {
    busy.value = false
  }
}

// Acción: Confirmar y Guardar Registro en la BD
async function confirmRegistration() {
  busy.value = true
  try {
    const fd = new FormData()
    fd.append('isbn', previewModal.form.isbn)
    fd.append('title', previewModal.form.title)
    fd.append('author', previewModal.form.author)
    if (previewModal.form.publisher) fd.append('publisher', previewModal.form.publisher)
    fd.append('device_info', previewModal.device)

    if (pendingPhotoBlob) {
      fd.append('photo', pendingPhotoBlob, 'cover.jpg')
    }

    const data = await scanRepository.create(fd)
    const s = data.scan
    result.value = {
      ok: true,
      label: data.result === 'nuevo' ? '✔ ¡Libro registrado con éxito en la base de datos!' : '✔ Escaneo registrado',
      title: s.title,
      author: s.author,
      isbn: s.isbn,
      cover: s.cover_url || s.photo_url,
    }
    cancelPreview()
  } catch (err) {
    alert(err.response?.data?.message || 'Error al guardar el libro en la base de datos.')
  } finally {
    busy.value = false
  }
}

function cancelPreview() {
  previewModal.visible = false
  if (previewModal.capturedPhotoUrl && previewModal.capturedPhotoUrl.startsWith('blob:')) {
    URL.revokeObjectURL(previewModal.capturedPhotoUrl)
  }
  previewModal.capturedPhotoUrl = null
  pendingPhotoBlob = null
}

async function confirmLoan() {
  busy.value = true
  try {
    await loanService.create({
      book_id: loanModal.book.id,
      user_name: loanModal.userName,
      return_date: loanModal.returnDate,
      device_info: getDeviceInfo(),
    })
    result.value = {
      ok: true,
      label: '✔ Préstamo realizado con éxito',
      title: loanModal.book.title,
      cover: loanModal.book.cover_url,
      message: `Asignado a: ${loanModal.userName} (Devolución: ${loanModal.returnDate})`,
    }
    cancelLoan()
  } catch (err) {
    alert(err.response?.data?.message || 'Error al registrar el préstamo.')
  } finally {
    busy.value = false
  }
}

function cancelLoan() {
  loanModal.visible = false
}

function closeCheckModal() {
  checkModal.visible = false
}

function feedback(ok) {
  if (navigator.vibrate) navigator.vibrate(ok ? 200 : [100, 60, 100])
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.frequency.value = ok ? 880 : 220
    gain.gain.value = 0.1
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.start()
    osc.stop(ctx.currentTime + 0.15)
  } catch { /* sin audio */ }
}

onBeforeUnmount(stopCamera)
</script>

<style scoped>
.mode-tabs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--sp-3);
  margin-bottom: var(--sp-4);
}
.mode-tab {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  padding: 12px 16px;
  background: #fff;
  border: 2px solid var(--c-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
}
.mode-tab:hover {
  border-color: var(--c-primary);
  background: var(--c-surface-hover, #f8fafc);
}
.mode-tab.active {
  border-color: var(--c-primary);
  background: #eff6ff;
  color: #1e3a8a;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15);
}
.mode-tab div { display: flex; flex-direction: column; }
.mode-tab small { font-size: 0.75rem; color: var(--c-text-secondary); }

.scanner-body { display: flex; flex-direction: column; gap: var(--sp-4); padding: var(--sp-5); }
.reader {
  width: 100%; max-width: 480px; min-height: 220px; margin: 0 auto;
  border: 2px dashed var(--c-border); border-radius: var(--radius-md); overflow: hidden;
}
.reader :deep(video) { width: 100% !important; }
.scanner-actions { display: flex; justify-content: center; }
.msg-error { color: var(--c-danger); text-align: center; }
.msg-info  { color: var(--c-text-secondary); text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; }

.result {
  display: flex; gap: var(--sp-4); align-items: center; padding: var(--sp-4);
  border-radius: var(--radius-md); border: 2px solid; max-width: 480px; margin: 0 auto; width: 100%;
}
.result.ok  { background: #ecfdf5; border-color: var(--c-success); }
.result.bad { background: #fef2f2; border-color: var(--c-danger); }
.result-cover {
  width: 56px; height: 80px; flex-shrink: 0; border-radius: 4px;
  background: var(--c-border) center / cover no-repeat;
}
.muted { color: var(--c-text-secondary); font-size: .85rem; }
.mono  { font-family: monospace; }
.manual { display: flex; flex-direction: column; gap: var(--sp-2); max-width: 480px; margin: 0 auto; width: 100%; }
.manual-row { display: flex; gap: var(--sp-2); }
.manual-row .form-control { flex: 1; }

/* Modales */
.overlay {
  position: fixed; inset: 0; background: rgba(10, 32, 64, .6);
  display: flex; align-items: center; justify-content: center; z-index: 300; padding: var(--sp-4);
}
.modal-box {
  background: #fff; border-radius: var(--radius-lg); width: 100%; max-width: 620px;
  max-height: 90vh; overflow-y: auto; padding: var(--sp-6); box-shadow: var(--shadow-xl);
}
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4); }
.modal-head h3 { margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; }
.close { font-size: 1.8rem; line-height: 1; border: none; background: none; cursor: pointer; color: var(--c-text-secondary); }

.preview-layout {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: var(--sp-4);
  margin-bottom: var(--sp-4);
}
@media (max-width: 600px) {
  .preview-layout { grid-template-columns: 1fr; }
}
.preview-photos-col {
  display: flex; flex-direction: column; gap: 12px;
}
.photo-card { display: flex; flex-direction: column; gap: 4px; }
.cover-img {
  width: 100%; aspect-ratio: 3/4; object-fit: cover;
  border-radius: var(--radius-md); border: 1px solid var(--c-border);
}
.cover-img.captured {
  border: 2px dashed #93c5fd;
}
.preview-placeholder {
  width: 100%; aspect-ratio: 3/4; display: flex; align-items: center; justify-content: center;
  background: #f1f5f9; border-radius: var(--radius-md); font-size: 0.8rem; color: var(--c-text-muted);
}
.preview-fields { display: flex; flex-direction: column; gap: var(--sp-3); }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: var(--sp-3); margin-top: var(--sp-5);
  padding-top: var(--sp-4); border-top: 1px solid var(--c-border);
}
.alert-info {
  background: #eff6ff; color: #1e40af; padding: 10px; border-radius: var(--radius-md); font-size: 0.85rem;
}
.alert-warn {
  background: #fefce8; color: #854d0e; padding: 10px; border-radius: var(--radius-md); font-size: 0.85rem;
}

/* Préstamo y Consulta */
.loan-book-container, .check-container {
  display: flex; gap: 16px; background: #f8fafc; padding: 16px; border-radius: var(--radius-md);
  border: 1px solid var(--c-border); margin-bottom: 16px;
}
.loan-cover-wrap, .check-cover-wrap {
  width: 90px; flex-shrink: 0;
}
.loan-cover {
  width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 4px;
  border: 1px solid var(--c-border);
}
.loan-book-details, .check-details {
  display: flex; flex-direction: column; gap: 4px; flex: 1;
}
.loan-book-details h4, .check-details h3 { margin: 0; font-size: 1.15rem; }

.status-box {
  margin-top: 10px; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid;
}
.status-box.status-ok {
  background: #ecfdf5; border-color: #a7f3d0; color: #065f46;
}
.status-box.status-loan {
  background: #eff6ff; border-color: #bfdbfe; color: #1e40af;
}
.status-box.status-none {
  background: #fef2f2; border-color: #fecaca; color: #991b1b;
}
.status-title { font-size: 0.95rem; margin-bottom: 4px; }
.status-desc { font-size: 0.85rem; }

.chip { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
.chip.ok { background: #dcfce7; color: #166534; }
.chip.bad { background: #fee2e2; color: #991b1b; }
</style>