<template>
  <div class="flex min-h-screen bg-gray-50">
    <!-- Overlay для мобильного -->
    <div
      class="fixed inset-0 z-[999] bg-black/50 md:hidden"
      :class="{ hidden: !sidebarOpen }"
      @click="sidebarOpen = false"
    />

    <!-- Кнопка меню (мобильный) -->
    <button
      type="button"
      class="fixed top-3 left-3 z-[1001] flex md:hidden h-10 w-10 items-center justify-center rounded-lg bg-gray-800 text-white shadow-lg"
      aria-label="Меню"
      @click="sidebarOpen = !sidebarOpen"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Сайдбар -->
    <aside
      class="fixed top-0 left-0 z-[1000] h-full w-[250px] flex flex-col bg-gradient-solar-coral text-white overflow-y-auto transition-transform duration-300 md:translate-x-0 -translate-x-full"
      :class="{ 'translate-x-0': sidebarOpen }"
    >
      <div
        class="mx-3 mt-4 mb-3 shrink-0 transition-all duration-300 rounded-xl"
        :class="[
          hasCardStyle ? cardEffectClass(user.selectedCard.styleConfig) : (user?.selectedCard?.cssClass || 'user-card_default'),
          hasCardStyle ? '' : 'p-3 bg-white/[0.06]'
        ]"
        :style="hasCardStyle ? cardConfigToStyle(user.selectedCard.styleConfig) : {}"
      >
        <div class="flex items-center gap-3">
          <img
            v-if="user?.avatar"
            :src="user.avatar"
            alt=""
            class="shrink-0 object-cover"
            :style="hasCardStyle ? { ...cardAvatarStyle(user.selectedCard.styleConfig), borderRadius: '50%' } : {}"
            :class="hasCardStyle ? '' : 'h-10 w-10 rounded-full ring-2 ring-white/20'"
          />
          <div
            v-else
            class="flex items-center justify-center text-lg font-bold shrink-0"
            :style="hasCardStyle ? { ...cardAvatarStyle(user.selectedCard.styleConfig), borderRadius: '50%', backgroundColor: 'rgba(255,255,255,0.15)' } : {}"
            :class="hasCardStyle ? '' : 'h-10 w-10 rounded-full bg-white/10 ring-2 ring-white/10'"
          >
            <span :style="hasCardStyle ? cardNameStyle(user.selectedCard.styleConfig) : { color: 'rgba(255,255,255,0.8)' }">{{ (user?.firstName || user?.email || '?')[0]?.toUpperCase() }}</span>
          </div>
          <div class="min-w-0">
            <div class="font-semibold truncate text-white" :style="hasCardStyle ? cardNameStyle(user.selectedCard.styleConfig) : {}">{{ user?.fullName || 'Загрузка...' }}</div>
            <div class="text-sm truncate" :style="hasCardStyle ? cardStatusStyle(user.selectedCard.styleConfig) : { color: 'rgba(255,255,255,0.5)' }">{{ user?.positionLabel || '' }}</div>
          </div>
        </div>
      </div>

      <nav class="flex-1 py-3 space-y-0.5">
        <router-link
          to="/news"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/news') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">🔔</span>
          Уведомления
        </router-link>
        <router-link
          to="/"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path === '/' || $route.path === '/profile' }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">👤</span>
          Личный кабинет
        </router-link>
        <router-link
          to="/schedule"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/schedule') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">📅</span>
          График смен
        </router-link>
        <router-link
          to="/availability"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/availability') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">🧩</span>
          Карта возможностей
        </router-link>
        <router-link
          to="/payroll"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/payroll') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">💰</span>
          Зарплата
        </router-link>
        <router-link
          to="/accounting-chat"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/accounting-chat') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">💼</span>
          Бухгалтерия
        </router-link>
        <router-link
          to="/tech-service"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/tech-service') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">🔧</span>
          Тех. Обслуживание
        </router-link>
        <router-link
          to="/shift-tasks"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/shift-tasks') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">✅</span>
          Задачи
        </router-link>
        <router-link
          to="/learning"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/learning') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">📖</span>
          DBT.INFO
        </router-link>
        <router-link
          v-if="canSeeSupply"
          to="/supply"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/supply') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">📦</span>
          Заказ поставки
        </router-link>
        <router-link
          v-if="canSeeStock"
          to="/stock"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/stock') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">📊</span>
          Остатки на складе
        </router-link>
        <router-link
          v-if="canSeeStock"
          to="/admin/analytics"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': isAnalyticsRoute }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">📈</span>
          Аналитика
        </router-link>
        <router-link
          v-if="canSeeDocs"
          to="/documentation"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/documentation') || $route.path.startsWith('/daily') || $route.path.startsWith('/write-off') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">📋</span>
          Документация
        </router-link>
        <router-link
          v-if="hasAdminAccess(user) || user?.position === 'manager'"
          :to="hasAdminAccess(user) ? '/admin' : '/admin/users'"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/admin') && !isAnalyticsRoute }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">🛡️</span>
          Админка
        </router-link>
        <router-link
          to="/settings"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/settings') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">⚙️</span>
          Настройки
        </router-link>
        <router-link
          to="/about"
          class="sidebar-link"
          :class="{ 'sidebar-link-active': $route.path.startsWith('/about') }"
          @click="sidebarOpen = false"
        >
          <span class="sidebar-icon">ℹ️</span>
          О проекте
        </router-link>
      </nav>

      <div class="p-3 border-t border-white/5">
        <button
          type="button"
          class="w-full py-2 rounded-lg border border-white/10 text-white/50 hover:text-white/80 hover:bg-white/5 text-sm font-medium transition-colors"
          @click="logout"
        >
          Выход
        </button>
      </div>
    </aside>

    <!-- Основной контент -->
    <main class="flex-1 min-h-screen w-full md:ml-[250px] pt-14 md:pt-6 pb-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api, clearToken } from '@/api/client'
import { getCurrentUser, resetUserCache } from '@/router'
import { cardConfigToStyle, cardNameStyle, cardStatusStyle, cardAvatarStyle, cardEffectClass } from '@/utils/cardStyleRenderer'
import { canAccessStock, hasAdminAccess } from '@/utils/access'

const router = useRouter()
const route = useRoute()
const user = ref(null)
const sidebarOpen = ref(false)

const hasCardStyle = computed(() => {
  const sc = user.value?.selectedCard?.styleConfig
  return sc && typeof sc === 'object' && Object.keys(sc).length > 0
})

const canSeeSupply = computed(() => {
  const u = user.value
  if (!u) return false
  return u.isAdmin || ['manager', 'location_manager', 'senior_teamaker'].includes(u.position)
})
const canSeeDocs = computed(() => {
  const u = user.value
  if (!u) return false
  return u.isAdmin || ['manager', 'location_manager', 'senior_teamaker'].includes(u.position)
})
const canSeeStock = computed(() => canAccessStock(user.value))

const isAnalyticsRoute = computed(() => {
  const path = route.path
  return path.startsWith('/admin/analytics') || path.startsWith('/checks') || path.startsWith('/sales-reports')
})

async function refreshUser() {
  try {
    user.value = await getCurrentUser()
  } catch {
    user.value = null
  }
}

onMounted(refreshUser)
// Обновить юзера (и карточку в сайдбаре) при входе/выходе из профиля
watch(() => route.path, (newPath, oldPath) => {
  if (oldPath === '/profile' || newPath === '/profile' || oldPath === '/' || newPath === '/') {
    resetUserCache()
    refreshUser()
  }
})

async function logout() {
  try {
    await api.auth.logout()
  } catch (_) {}
  clearToken()
  resetUserCache()
  router.push({ name: 'Login' })
}
</script>
