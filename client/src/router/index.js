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
      meta: { title: 'Libros' },
    },
    {
      path: '/genres',
      name: 'genres',
      component: GenresView,
      meta: { title: 'Géneros' },
    },
    {
      path: '/publishers',
      name: 'publishers',
      component: PublishersView,
      meta: { title: 'Editoriales' },
    },
    {
      path: '/loans',
      name: 'loans',
      component: LoansView,
      meta: { title: 'Préstamos' },
    },
    {
      path: '/users',
      name: 'users',
      component: UsersView,
      meta: { title: 'Usuarios', requiresAdmin: true },
    },
    {
      path: '/glpi',
      name: 'glpi',
      component: GlpiView,
      meta: { title: 'GLPI' },
    },
  ],
})

// ── Guard Logic (Exported for Testing) ──────────────────────────────────
export const navigationGuard = (to) => {
  const auth = useAuthStore()

  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'dashboard' }
  }
}

router.beforeEach(navigationGuard)

export default router
