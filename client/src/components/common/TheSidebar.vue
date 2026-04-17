<template>
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">
        <font-awesome-icon icon="book" />
      </div>
      <div class="sidebar-logo-text">
        <strong>Biblioteca</strong>
        <span>Sistema de Gestión</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="sidebar-section-title">Principal</span>

      <RouterLink
        v-for="item in mainItems"
        :key="item.route"
        :to="item.route"
        class="sidebar-item"
        :class="{ active: currentRoute === item.route }"
      >
        <span class="sidebar-item-icon">
          <font-awesome-icon :icon="item.icon" />
        </span>
        {{ item.label }}
      </RouterLink>

      <div v-if="auth.can('users.manage') || auth.can('glpi.manage')">
        <span class="sidebar-section-title">Administración</span>

        <RouterLink
          v-if="auth.can('users.manage')"
          to="/users"
          class="sidebar-item"
          :class="{ active: currentRoute === '/users' }"
        >
          <span class="sidebar-item-icon">
            <font-awesome-icon icon="users" />
          </span>
          Usuarios
        </RouterLink>

        <RouterLink
          v-if="auth.can('glpi.manage')"
          to="/glpi"
          class="sidebar-item"
          :class="{ active: currentRoute === '/glpi' }"
        >
          <span class="sidebar-item-icon">
            <font-awesome-icon icon="link" />
          </span>
          GLPI
        </RouterLink>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar">{{ userInitial }}</div>
        <div class="sidebar-user-info">
          <span class="sidebar-user-name">{{ auth.user?.name }}</span>
          <span class="sidebar-user-role">{{ roleName }}</span>
        </div>
        <button class="sidebar-logout" @click="handleLogout" title="Cerrar sesión">
          <font-awesome-icon icon="power-off" />
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import Swal from 'sweetalert2'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

const mainItems = computed(() => {
  const items = [
    { icon: 'chart-line',     label: 'Dashboard',   route: '/dashboard' },
    { icon: 'book',           label: 'Libros',      route: '/books',      permission: 'books.view' },
    { icon: 'tags',           label: 'Géneros',     route: '/genres',     permission: 'catalog.manage' },
    { icon: 'building',       label: 'Editoriales', route: '/publishers', permission: 'catalog.manage' },
    { icon: 'clipboard-list', label: 'Préstamos',   route: '/loans',     permission: 'loans.view_own' },
  ]

  // Filtramos por permisos
  return items.filter(i => !i.permission || auth.can(i.permission))
})

const currentRoute = computed(() => route.path)

const userInitial = computed(() =>
  (auth.user?.name || 'U').charAt(0).toUpperCase()
)

const roleName = computed(() => {
  const map = { admin: 'Administrador', bibliotecario: 'Bibliotecario', lector: 'Lector' }
  const slug = auth.user?.role?.slug || auth.user?.role // Fallback a legacy si existe
  return map[slug] || slug || 'Usuario'
})

async function handleLogout() {
  const result = await Swal.fire({
    title: '¿Cerrar sesión?',
    text: '¿Estás seguro de que deseas salir del sistema?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, salir',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: 'var(--c-primary-600)',
    reverseButtons: true
  })

  if (result.isConfirmed) {
    await auth.logout()
    router.push({ name: 'login' })
  }
}
</script>

<style scoped>
.sidebar-user {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

.sidebar-user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--c-primary-500) 0%, var(--c-primary-700) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  font-size: .9rem;
  flex-shrink: 0;
}

.sidebar-user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-user-name {
  font-size: .82rem;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-user-role {
  font-size: .7rem;
  color: var(--sidebar-text);
  text-transform: capitalize;
}

.sidebar-logout {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  color: var(--sidebar-text);
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--tr-fast);
  flex-shrink: 0;
}

.sidebar-logout:hover {
  background: rgba(239,68,68,.2);
  color: #ef4444;
}
</style>
