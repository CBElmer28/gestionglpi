<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="barcode" /> Escáner de libros</h1>
        <p>Apunta la cámara al código de barras de la contraportada.</p>
      </div>
      <RouterLink to="/scans" class="btn btn-ghost">Ver libros escaneados</RouterLink>
    </div>

    <div class="card">
      <div class="card-body scanner-body">
        <div id="reader" class="reader"></div>

        <p v-if="cameraError" class="msg-error">{{ cameraError }}</p>

        <div class="scanner-actions">
          <button v-if="!cameraOn" class="btn btn-primary" @click="startCamera">
            <font-awesome-icon icon="barcode" /> Iniciar cámara
          </button>
          <button v-else class="btn btn-ghost" @click="stopCamera">Detener cámara</button>
        </div>

        <div v-if="busy" class="msg-info">Buscando el libro…</div>

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

        <div v-if="needsManual" class="manual">
          <label class="filter-label">Título</label>
          <input v-model="manualForm.title" class="form-control" placeholder="Título del libro" />
          <label class="filter-label">Autor</label>
          <input v-model="manualForm.author" class="form-control" placeholder="Autor" />
          <button class="btn btn-primary" :disabled="busy || !manualForm.title" @click="submitManualBook">
            Registrar libro
          </button>
        </div>

        <div class="manual">
          <label class="filter-label">¿Sin cámara? Escribe el ISBN</label>
          <div class="manual-row">
            <input v-model="manualIsbn" class="form-control" inputmode="numeric" placeholder="978..." />
            <button class="btn btn-ghost" :disabled="busy || !manualIsbn" @click="submitManualIsbn">Buscar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onBeforeUnmount } from 'vue'
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'
import { scanRepository } from '@/repositories/scanRepository'

const cameraOn    = ref(false)
const cameraError = ref('')
const busy        = ref(false)
const result      = ref(null)
const needsManual = ref(null)
const manualIsbn  = ref('')
const manualForm  = reactive({ title: '', author: '' })

let scanner  = null
let lastCode = ''
let lastTime = 0

async function startCamera() {
  cameraError.value = ''
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
  } catch (e) {
    scanner = null
    cameraError.value =
      'No se pudo abrir la cámara. Permite el acceso en el navegador y abre la página con HTTPS.'
  }
}

async function stopCamera() {
  if (scanner) {
    try { await scanner.stop() } catch { /* ya estaba detenida */ }
    try { scanner.clear() } catch { /* sin acción */ }
    scanner = null
  }
  cameraOn.value = false
}

function onDecoded(text) {
  const now = Date.now()
  if (busy.value || (text === lastCode && now - lastTime < 4000)) return
  lastCode = text
  lastTime = now
  sendIsbn(text)
}

async function sendIsbn(isbn, extra = {}) {
  busy.value = true
  result.value = null
  try {
    const data = await scanRepository.create({ isbn, ...extra })
    const s = data.scan
    needsManual.value = null
    result.value = {
      ok: true,
      label: data.result === 'nuevo' ? '✔ Libro registrado' : '✔ Libro ya existente',
      title: s.title,
      author: s.author,
      cover: s.cover_url,
      isbn: s.isbn,
    }
    feedback(true)
  } catch (err) {
    if (err.response?.status === 404 && err.response.data?.found === false) {
      needsManual.value = { isbn: err.response.data.isbn }
      manualForm.title = ''
      manualForm.author = ''
      result.value = {
        ok: false,
        label: 'No encontramos este libro',
        message: 'Completa el título y el autor para registrarlo.',
        isbn: err.response.data.isbn,
      }
    } else {
      result.value = {
        ok: false,
        label: 'No se pudo registrar',
        message: err.response?.data?.message || 'Error de conexión con el servidor.',
      }
    }
    feedback(false)
  } finally {
    busy.value = false
  }
}

function submitManualBook() {
  if (!needsManual.value) return
  sendIsbn(needsManual.value.isbn, { title: manualForm.title, author: manualForm.author })
}

function submitManualIsbn() {
  sendIsbn(manualIsbn.value)
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
.scanner-body { display: flex; flex-direction: column; gap: var(--sp-4); padding: var(--sp-5); }
.reader {
  width: 100%; max-width: 480px; min-height: 220px; margin: 0 auto;
  border: 2px dashed var(--c-border); border-radius: var(--radius-md); overflow: hidden;
}
.reader :deep(video) { width: 100% !important; }
.scanner-actions { display: flex; justify-content: center; }
.msg-error { color: var(--c-danger); text-align: center; }
.msg-info  { color: var(--c-text-secondary); text-align: center; }
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
</style>