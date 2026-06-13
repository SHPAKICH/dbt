import { createRouter, createWebHistory } from 'vue-router'
import { api } from '@/api/client'
import Login from '@/views/Login.vue'
import ForgotPassword from '@/views/ForgotPassword.vue'
import ResetPassword from '@/views/ResetPassword.vue'
import MainLayout from '@/layouts/MainLayout.vue'
import Profile from '@/views/Profile.vue'
import Schedule from '@/views/Schedule.vue'
import Settings from '@/views/Settings.vue'
import Availability from '@/views/Availability.vue'
import Guru from '@/views/Guru.vue'
import GuruTestsList from '@/views/guru/GuruTestsList.vue'
import GuruTestView from '@/views/guru/GuruTestView.vue'
import GuruTestTake from '@/views/guru/GuruTestTake.vue'
import GuruTestResult from '@/views/guru/GuruTestResult.vue'
import GuruTestLeaderboard from '@/views/guru/GuruTestLeaderboard.vue'
import GuruTestResults from '@/views/guru/GuruTestResults.vue'
import GuruCardsList from '@/views/guru/GuruCardsList.vue'
import GuruCardView from '@/views/guru/GuruCardView.vue'
import GuruCardEdit from '@/views/guru/GuruCardEdit.vue'
import GuruCardCreate from '@/views/guru/GuruCardCreate.vue'
import GuruTestCreate from '@/views/guru/GuruTestCreate.vue'
import GuruTestEdit from '@/views/guru/GuruTestEdit.vue'
import GuruLessonsList from '@/views/guru/GuruLessonsList.vue'
import GuruLessonView from '@/views/guru/GuruLessonView.vue'
import GuruLessonCreate from '@/views/guru/GuruLessonCreate.vue'
import GuruLessonEdit from '@/views/guru/GuruLessonEdit.vue'
import GuruTrainingProgress from '@/views/guru/GuruTrainingProgress.vue'
import AdminDashboard from '@/views/admin/AdminDashboard.vue'
import AdminUsers from '@/views/admin/AdminUsers.vue'
import AdminUserForm from '@/views/admin/AdminUserForm.vue'
import AdminLocations from '@/views/admin/AdminLocations.vue'
import AdminLocationForm from '@/views/admin/AdminLocationForm.vue'
import AdminPush from '@/views/admin/AdminPush.vue'
import AdminNews from '@/views/admin/AdminNews.vue'
import AdminCardConstructor from '@/views/admin/AdminCardConstructor.vue'
import AdminPayrollSettings from '@/views/admin/AdminPayrollSettings.vue'
import Analytics from '@/views/admin/Analytics.vue'
import News from '@/views/News.vue'
import NewsView from '@/views/NewsView.vue'
import Documentation from '@/views/Documentation.vue'
import SupplyCreate from '@/views/SupplyCreate.vue'
import Daily from '@/views/Daily.vue'
import WriteOff from '@/views/WriteOff.vue'
import Payroll from '@/views/Payroll.vue'
import AboutProject from '@/views/AboutProject.vue'
import AccountingChat from '@/views/AccountingChat.vue'
import TechService from '@/views/TechService.vue'
import ShiftTasks from '@/views/ShiftTasks.vue'
import StockBalance from '@/views/StockBalance.vue'
import { canAccessStock, hasAdminAccess } from '@/utils/access'

const routes = [
  { path: '/login', name: 'Login', component: Login, meta: { guest: true } },
  { path: '/forgot-password', name: 'ForgotPassword', component: ForgotPassword, meta: { guest: true } },
  { path: '/reset-password', name: 'ResetPassword', component: ResetPassword, meta: { guest: true } },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'Profile', component: Profile },
      { path: 'profile', redirect: '/' },
      { path: 'schedule', name: 'Schedule', component: Schedule },
      { path: 'availability', name: 'Availability', component: Availability },
      { path: 'news', name: 'News', component: News },
      { path: 'news/:id', name: 'NewsView', component: NewsView },
      { path: 'learning', name: 'Guru', component: Guru },
      { path: 'learning/tests', name: 'GuruTestsList', component: GuruTestsList },
      { path: 'learning/test/:id', name: 'GuruTestView', component: GuruTestView },
      { path: 'learning/test/:id/edit', name: 'GuruTestEdit', component: GuruTestEdit },
      { path: 'learning/test/:id/take', name: 'GuruTestTake', component: GuruTestTake },
      { path: 'learning/test/result/:id', name: 'GuruTestResult', component: GuruTestResult },
      { path: 'learning/test/:id/leaderboard', name: 'GuruTestLeaderboard', component: GuruTestLeaderboard },
      { path: 'learning/test/:id/results', name: 'GuruTestResults', component: GuruTestResults },
      { path: 'learning/cards', name: 'GuruCardsList', component: GuruCardsList },
      { path: 'learning/cards/create', name: 'GuruCardCreate', component: GuruCardCreate },
      { path: 'learning/card/:id', name: 'GuruCardView', component: GuruCardView },
      { path: 'learning/card/:id/edit', name: 'GuruCardEdit', component: GuruCardEdit },
      { path: 'learning/tests/create', name: 'GuruTestCreate', component: GuruTestCreate },
      { path: 'learning/lessons', name: 'GuruLessonsList', component: GuruLessonsList },
      { path: 'learning/lesson/:id', name: 'GuruLessonView', component: GuruLessonView },
      { path: 'learning/lessons/create', name: 'GuruLessonCreate', component: GuruLessonCreate },
      { path: 'learning/lesson/:id/edit', name: 'GuruLessonEdit', component: GuruLessonEdit },
      { path: 'learning/progress', name: 'GuruTrainingProgress', component: GuruTrainingProgress, meta: { requiresAdmin: true, allowManager: true } },
      { path: 'admin', name: 'AdminDashboard', component: AdminDashboard, meta: { requiresAdmin: true } },
      { path: 'admin/users', name: 'AdminUsers', component: AdminUsers, meta: { requiresAdmin: true, allowManager: true } },
      { path: 'admin/users/create', name: 'AdminUserCreate', component: AdminUserForm, meta: { requiresAdmin: true, allowManager: true } },
      { path: 'admin/users/:id/edit', name: 'AdminUserEdit', component: AdminUserForm, meta: { requiresAdmin: true, allowManager: true } },
      { path: 'admin/locations', name: 'AdminLocations', component: AdminLocations, meta: { requiresAdmin: true } },
      { path: 'admin/locations/create', name: 'AdminLocationCreate', component: AdminLocationForm, meta: { requiresAdmin: true } },
      { path: 'admin/locations/:id/edit', name: 'AdminLocationEdit', component: AdminLocationForm, meta: { requiresAdmin: true } },
      { path: 'admin/push', name: 'AdminPush', component: AdminPush, meta: { requiresAdmin: true } },
      { path: 'admin/news', name: 'AdminNews', component: AdminNews, meta: { requiresAdmin: true } },
      { path: 'admin/cards', name: 'AdminCardConstructor', component: AdminCardConstructor, meta: { requiresAdmin: true } },
      { path: 'admin/payroll', name: 'AdminPayrollSettings', component: AdminPayrollSettings, meta: { requiresAdmin: true } },
      { path: 'admin/analytics', name: 'Analytics', component: Analytics, meta: { requiresAnalyticsAccess: true } },
      { path: 'documentation', name: 'Documentation', component: Documentation },
      { path: 'supply', name: 'SupplyCreate', component: SupplyCreate },
      { path: 'stock', name: 'StockBalance', component: StockBalance, meta: { requiresStockAccess: true } },
      { path: 'checks', redirect: { name: 'Analytics', query: { tab: 'checks' } } },
      { path: 'sales-reports', redirect: { name: 'Analytics', query: { tab: 'reports' } } },
      { path: 'daily', name: 'Daily', component: Daily },
      { path: 'write-off', name: 'WriteOff', component: WriteOff },
      { path: 'payroll', name: 'Payroll', component: Payroll },
      { path: 'accounting-chat', name: 'AccountingChat', component: AccountingChat },
      { path: 'tech-service', name: 'TechService', component: TechService },
      { path: 'shift-tasks', name: 'ShiftTasks', component: ShiftTasks },
      { path: 'settings', name: 'Settings', component: Settings },
      { path: 'about', name: 'AboutProject', component: AboutProject },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

let userPromise = null
export function getCurrentUser() {
  if (!userPromise) userPromise = api.auth.user().then((r) => r.user).catch(() => null)
  return userPromise
}
export function resetUserCache() {
  userPromise = null
}

router.beforeEach(async (to) => {
  if (to.meta.guest) {
    try {
      const u = await getCurrentUser()
      if (u) return { name: 'Profile' }
    } catch {
      // not logged in
    }
    return true
  }
  if (to.meta.requiresAuth) {
    try {
      const u = await getCurrentUser()
      if (!u) return { name: 'Login', query: { redirect: to.fullPath } }
      if (to.meta.requiresAdmin) {
        const adminOnly = !to.meta.allowManager
        if (adminOnly && !hasAdminAccess(u)) {
          if (to.path === '/admin') return { name: 'AdminUsers' }
          return { name: 'Profile' }
        }
        if (!adminOnly && !hasAdminAccess(u) && u.position !== 'manager') return { name: 'Profile' }
      }
      if (to.meta.requiresAnalyticsAccess && !canAccessStock(u)) {
        return { name: 'Profile' }
      }
      if (to.meta.requiresStockAccess && !canAccessStock(u)) {
        return { name: 'Profile' }
      }
    } catch {
      userPromise = null
      return { name: 'Login', query: { redirect: to.fullPath } }
    }
  }
  return true
})

// Для редиректа после логина на дочерний маршрут
export function resolveRedirect(redirect) {
  if (typeof redirect !== 'string') return { name: 'Profile' }
  const safeRedirect = redirect.trim()
  if (!safeRedirect.startsWith('/') || safeRedirect.startsWith('//') || /[\r\n]/.test(safeRedirect)) {
    return { name: 'Profile' }
  }
  if (safeRedirect === '/' || safeRedirect === '/profile') return { name: 'Profile' }
  if (safeRedirect === '/schedule') return { name: 'Schedule' }
  if (safeRedirect === '/settings') return { name: 'Settings' }
  if (safeRedirect === '/learning' || safeRedirect === '/guru') return { name: 'Guru' }
  if (safeRedirect === '/learning/lessons') return { name: 'GuruLessonsList' }
  if (safeRedirect === '/admin') return { name: 'AdminDashboard' }
  if (safeRedirect === '/admin/analytics') return { name: 'Analytics' }
  if (safeRedirect.startsWith('/admin/analytics?')) return { path: safeRedirect }
  if (safeRedirect === '/checks') return { name: 'Analytics', query: { tab: 'checks' } }
  if (safeRedirect === '/sales-reports') return { name: 'Analytics', query: { tab: 'reports' } }
  if (safeRedirect === '/documentation') return { name: 'Documentation' }
  if (safeRedirect === '/supply') return { name: 'SupplyCreate' }
  if (safeRedirect === '/stock') return { name: 'StockBalance' }
  if (safeRedirect === '/daily') return { name: 'Daily' }
  if (safeRedirect === '/write-off') return { name: 'WriteOff' }
  if (safeRedirect === '/payroll') return { name: 'Payroll' }
  if (safeRedirect === '/about') return { name: 'AboutProject' }
  return { path: safeRedirect }
}

export default router
