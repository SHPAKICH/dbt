<template>
  <div class="analytics-page flex flex-col h-[calc(100vh-3.5rem)] md:h-[calc(100vh-1.5rem)] -mx-4 md:-mx-6 -mt-4 md:-mt-6 -mb-8">
    <header class="shrink-0 px-4 md:px-6 py-3 border-b border-gray-200 bg-white">
      <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-0">
          <h1 class="text-xl font-bold text-gray-800">Аналитика</h1>
          <p class="text-xs text-gray-500">
            Карта выручки, чеки и отчёты по продажам из iiko
          </p>
        </div>

        <div class="flex flex-wrap gap-1 ml-auto">
          <button
            v-for="mode in viewModes"
            :key="mode.id"
            type="button"
            class="view-tab"
            :class="{ active: activeView === mode.id }"
            @click="setView(mode.id)"
          >
            {{ mode.label }}
          </button>
        </div>
      </div>

      <div
        v-if="activeView === 'map'"
        class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100"
      >
        <div class="flex flex-wrap gap-1">
          <button
            v-for="preset in periodPresets"
            :key="preset.id"
            type="button"
            class="filter-chip"
            :class="{ active: store.periodPreset === preset.id }"
            :disabled="store.loadingLeaderboard || store.loadingDetails"
            @click="onPeriodChange(preset.id)"
          >
            {{ preset.label }}
          </button>
        </div>

        <button
          type="button"
          class="filter-chip"
          :disabled="store.loadingLeaderboard"
          @click="store.refreshAll()"
        >
          {{ store.loadingLeaderboard ? 'Обновление…' : 'Обновить' }}
        </button>

        <input
          :value="store.searchQuery"
          type="search"
          placeholder="Поиск точки…"
          class="w-40 md:w-52 rounded-lg border border-gray-300 px-3 py-2 text-sm"
          @input="store.setSearchQuery(($event.target as HTMLInputElement).value)"
        />

        <div class="flex flex-wrap gap-1">
          <button
            v-for="filter in statusFilters"
            :key="filter.id"
            type="button"
            class="filter-chip"
            :class="{ active: store.statusFilter === filter.id }"
            @click="store.setStatusFilter(filter.id)"
          >
            {{ filter.label }}
          </button>
        </div>

        <span class="text-xs text-gray-500 tabular-nums">
          {{ store.filteredPoints.length }} / {{ store.points.length }}
        </span>
      </div>
    </header>

    <p
      v-if="store.error && activeView === 'map'"
      class="shrink-0 px-4 md:px-6 py-2 text-sm text-rose-700 bg-rose-50 border-b border-rose-100"
    >
      {{ store.error }}
    </p>

    <div v-show="activeView === 'map'" class="relative flex-1 min-h-0">
      <AnalyticsMap
        :points="store.filteredPoints"
        :selected-point-id="store.selectedPointId"
        :loading="store.loadingPoints"
        :error="store.error"
        :heat-points="heatPoints"
        @select="onSelectPoint"
      />

      <AnalyticsLeaderboard
        :open="store.leaderboardOpen"
        :loading="store.loadingLeaderboard"
        :iiko-configured="store.iikoConfigured"
        :entries="store.leaderboard?.leaderboard || []"
        :period="store.leaderboard?.period || store.period"
        :totals="store.leaderboard?.totals || null"
        :selected-id="store.selectedPointId"
        @toggle="store.toggleLeaderboard()"
        @select="onSelectPoint"
      />

      <AnalyticsSidebar
        :open="store.sidebarOpen"
        :loading="store.loadingDetails"
        :detail="store.pointDetails"
        @close="store.closeSidebar()"
      />
    </div>

    <div v-if="activeView === 'checks'" class="flex-1 min-h-0 overflow-hidden">
      <IikoChecks embedded />
    </div>

    <div v-if="activeView === 'reports'" class="flex-1 min-h-0 overflow-hidden">
      <SalesReports embedded />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AnalyticsMap from '@/components/analytics/AnalyticsMap.vue'
import AnalyticsLeaderboard from '@/components/analytics/AnalyticsLeaderboard.vue'
import AnalyticsSidebar from '@/components/analytics/AnalyticsSidebar.vue'
import IikoChecks from '@/views/IikoChecks.vue'
import SalesReports from '@/views/SalesReports.vue'
import { ANALYTICS_VIEW_MODES, parseAnalyticsViewMode } from '@/config/analyticsViews'
import { useAnalyticsStore } from '@/stores/analyticsStore'
import type { AnalyticsViewMode, PeriodPreset, StatusFilter } from '@/types/analytics'

const store = useAnalyticsStore()
const route = useRoute()
const router = useRouter()

const viewModes = ANALYTICS_VIEW_MODES

const activeView = computed(() => parseAnalyticsViewMode(route.query.tab))

const periodPresets: Array<{ id: PeriodPreset; label: string }> = [
  { id: 'today', label: 'Сегодня' },
  { id: 'week', label: 'Неделя' },
  { id: 'month', label: 'Месяц' },
]

const statusFilters: Array<{ id: StatusFilter; label: string }> = [
  { id: 'all', label: 'Все' },
  { id: 'active', label: 'Активные' },
  { id: 'inactive', label: 'Неактивные' },
]

const heatPoints = computed(() => {
  const revenueMap = store.revenueByLocationId
  return store.filteredPoints.map((point) => ({
    id: point.id,
    lat: point.lat,
    lng: point.lng,
    revenue: revenueMap.get(point.id) ?? 0,
  }))
})

function setView(mode: AnalyticsViewMode) {
  router.replace({
    path: route.path,
    query: mode === 'map' ? {} : { tab: mode },
  })
}

function onSelectPoint(id: number) {
  store.selectPoint(id)
}

async function onPeriodChange(preset: PeriodPreset) {
  store.setPeriodPreset(preset)
  await store.refreshAll()
}

function onEscape(event: KeyboardEvent) {
  if (event.key === 'Escape' && store.sidebarOpen && activeView.value === 'map') {
    store.closeSidebar()
  }
}

watch(activeView, (view) => {
  if (view === 'map') {
    setTimeout(() => {
      window.dispatchEvent(new Event('resize'))
    }, 50)
  }
})

onMounted(async () => {
  store.refreshIikoStatus()
  await store.loadPoints()
  await store.loadLeaderboard()
  window.addEventListener('keydown', onEscape)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onEscape)
})
</script>

<style scoped>
.view-tab {
  @apply px-3 py-2 rounded-lg border text-sm font-medium border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors;
}
.view-tab.active {
  @apply border-primary bg-primary text-white hover:bg-primary;
}
.filter-chip {
  @apply px-2.5 py-1.5 rounded-lg border text-xs font-medium border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50;
}
.filter-chip.active {
  @apply border-primary bg-primary/10 text-primary;
}
</style>
