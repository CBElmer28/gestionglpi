<template>
  <RouterView v-if="isPublicRoute || !auth.isAuthenticated" />
  <div v-else class="layout">
    <TheSidebar :class="{ 'is-open': menuOpen }" />
    <div v-if="menuOpen" class="sidebar-backdrop" @click="menuOpen = false"></div>
    <div class="main-content">
      <TheTopbar :title="pageTitle" @toggle-menu="menuOpen = !menuOpen" />
      <main class="page-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { useRoute }    from 'vue-router'
import { useAuthStore } from '@/store/auth'
import TheSidebar from '@/components/common/TheSidebar.vue'
import TheTopbar  from '@/components/common/TheTopbar.vue'

const auth          = useAuthStore()
const route         = useRoute()
const isPublicRoute = computed(() => route.meta?.public === true)
const pageTitle     = computed(() => route.meta?.title || 'Biblioteca')

// Menú lateral en pantallas pequeñas
const menuOpen = ref(false)
watch(() => route.path, () => { menuOpen.value = false })

// ── Gestión de Inactividad ──
const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'click']

function handleActivity() {
  auth.resetInactivityTimer()
}

onMounted(() => {
  events.forEach(event => {
    window.addEventListener(event, handleActivity)
  })
})

onUnmounted(() => {
  events.forEach(event => {
    window.removeEventListener(event, handleActivity)
  })
})
</script>

<style>

.sidebar-backdrop {
  display: none;
}

@media (max-width: 900px) {
  .layout .sidebar {
    position: fixed !important;
    top: 0;
    left: 0;
    bottom: 0;
    width: min(280px, 85vw);
    height: 100vh;
    height: 100dvh;
    overflow-y: auto;
    box-sizing: border-box;
    transform: translateX(-100%) !important;
    transition: transform 0.25s ease;
    z-index: 1001;
  }

  .layout .sidebar.is-open {
    transform: translateX(0) !important;
  }

  .layout .main-content {
    width: 100%;
    min-width: 0;
    margin-left: 0 !important;
  }

  .layout .page-content {
    min-width: 0;
    padding: 16px !important;
  }

  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    border: 0;
    background: rgba(10, 32, 64, 0.55);
    z-index: 1000;
  }
}

</style>