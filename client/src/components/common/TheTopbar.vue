<template>
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" @click="emit('toggle-menu')" aria-label="Abrir menú">☰</button>
      <div class="topbar-title">{{ title }}</div>
    </div>
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
const emit = defineEmits(['toggle-menu'])

const auth = useAuthStore()

const roleIcon = computed(() => {
  const icons = { admin: 'key', bibliotecario: 'book', lector: 'user-circle' }
  return icons[auth.user?.role] || 'user-circle'
})
</script>

<style scoped>
.topbar-left { display: flex; align-items: center; gap: 8px; min-width: 0; }
.menu-toggle {
  display: none; width: 44px; height: 44px; font-size: 1.5rem;
  align-items: center; justify-content: center; color: var(--c-text-primary);
}
@media (max-width: 900px) {
  .menu-toggle { display: inline-flex; }
}
</style>