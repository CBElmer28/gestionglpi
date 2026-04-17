import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const LoginView     = () => import('@/views/LoginView.vue')
const DashboardView = () => import('@/views/DashboardView.vue')
const BooksView     = () => import('@/views/BooksView.vue')
const GenresView    = () => import('@/views/GenresView.vue')
const PublishersView = () => import('@/views/PublishersView.vue')
const LoansView     = () => import('@/views/LoansView.vue')
const UsersView     = () => import('@/views/UsersView.vue')
const GlpiView      = () => import('@/views/GlpiView.vue')
const NotFoundView  = () => import('@/views/NotFoundView.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { public: true },
    },
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { title: 'Dashboard' },
    },
    {
      path: '/books',
      name: 'books',
      component: BooksView,
      meta: { title: 'Libros', permission: 'books.view' },
    },
    {
      path: '/genres',
      name: 'genres',
      component: GenresView,
      meta: { title: 'Géneros', permission: 'catalog.manage' },
    },
    {
      path: '/publishers',
      name: 'publishers',
      component: PublishersView,
      meta: { title: 'Editoriales', permission: 'catalog.manage' },
    },
    {
      path: '/loans',
      name: 'loans',
      component: LoansView,
      meta: { title: 'Préstamos', permission: 'loans.view_own' },
    },
    {
      path: '/users',
      name: 'users',
      component: UsersView,
      meta: { title: 'Usuarios', permission: 'users.manage' },
    },
    {
      path: '/glpi',
      name: 'glpi',
      component: GlpiView,
      meta: { title: 'GLPI', permission: 'glpi.manage' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
      meta: { public: true, title: 'No Encontrado' },
    },
  ],
})

// ── Guard Logic (Exported for Testing) ──────────────────────────────────
export const navigationGuard = (to) => {
  const auth = useAuthStore()

  // 1. Si la ruta no es pública y no está autenticado -> login
  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // 2. Si ya está autenticado e intenta ir al login -> dashboard
  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // 3. Verificación de permisos dinámicos
  if (to.meta.permission && auth.isAuthenticated) {
    if (!auth.can(to.meta.permission)) {
      // Si no tiene el permiso, enviamos a 404 (Access Denied)
      return { name: 'not-found' }
    }
  }
}

router.beforeEach(navigationGuard)

export default router
