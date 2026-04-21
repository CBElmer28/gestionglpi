<template>
  <RouterView v-if="isPublicRoute || !auth.isAuthenticated" />
  <div v-else class="layout">
    <TheSidebar />
    <div class="main-content">
      <TheTopbar :title="pageTitle" />
      <main class="page-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useRoute }    from 'vue-router'
import { useAuthStore } from '@/store/auth'
import TheSidebar from '@/components/common/TheSidebar.vue'
import TheTopbar  from '@/components/common/TheTopbar.vue'

const auth          = useAuthStore()
const route         = useRoute()
const isPublicRoute = computed(() => route.meta?.public === true)
const pageTitle     = computed(() => route.meta?.title || 'Biblioteca')

// ── Gestión de Inactividad ──────────────────────────────────────────
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
