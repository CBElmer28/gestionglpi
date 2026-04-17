<template>
  <RouterView v-if="isPublicRoute" />
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
import { computed } from 'vue'
import { useRoute }    from 'vue-router'
import TheSidebar from '@/components/common/TheSidebar.vue'
import TheTopbar  from '@/components/common/TheTopbar.vue'

const route         = useRoute()
const isPublicRoute = computed(() => route.meta?.public === true)
const pageTitle     = computed(() => route.meta?.title || 'Biblioteca')
</script>
