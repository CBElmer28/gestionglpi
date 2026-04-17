<template>
  <header class="topbar">
    <div class="topbar-title">{{ title }}</div>
    <div class="topbar-actions">
      <div class="topbar-badge">
        <font-awesome-icon :icon="roleIcon" />
        <strong>{{ auth.user?.name }}</strong>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/store/auth'

const props = defineProps({
  title: { type: String, default: '' },
})

const auth = useAuthStore()

const roleIcon = computed(() => {
  const icons = { admin: 'key', bibliotecario: 'book', lector: 'user-circle' }
  return icons[auth.user?.role] || 'user-circle'
})
</script>
