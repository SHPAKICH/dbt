<template>
  <div class="leaderboard-panel" :class="{ collapsed: !open }">
    <div class="leaderboard-header">
      <div class="min-w-0">
        <h3 class="text-sm font-bold text-gray-800">Лидерборд по выручке</h3>
        <p v-if="period" class="text-[11px] text-gray-500 mt-0.5">
          {{ formatPeriod(period) }}
          <span v-if="totals" class="ml-2 text-primary font-medium">
            {{ formatMoney(totals.revenue) }} · {{ totals.checks }} чеков
          </span>
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <button
          v-if="loading"
          type="button"
          class="text-xs text-gray-400"
          disabled
        >
          Загрузка…
        </button>
        <button
          type="button"
          class="toggle-btn"
          :aria-label="open ? 'Свернуть лидерборд' : 'Развернуть лидерборд'"
          @click="$emit('toggle')"
        >
          {{ open ? '▼' : '▲' }}
        </button>
      </div>
    </div>

    <div v-show="open" class="leaderboard-body">
      <p v-if="!iikoConfigured" class="px-4 py-3 text-xs text-amber-700 bg-amber-50 border-b border-amber-100">
        Укажите настройки iiko в разделе «Настройки», чтобы загрузить данные из чеков и отчётов по продажам.
      </p>

      <div v-else-if="loading && !entries.length" class="py-8 text-center text-sm text-gray-500">
        Загрузка лидерборда из iiko…
      </div>

      <div v-else-if="!entries.length" class="py-8 text-center text-sm text-gray-500">
        Нет данных за выбранный период
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-left text-gray-500 border-b border-gray-100">
              <th class="py-2 pl-4 pr-2 w-8">#</th>
              <th class="py-2 pr-3">Точка</th>
              <th class="py-2 pr-3 text-right">Выручка</th>
              <th class="py-2 pr-3 text-right hidden sm:table-cell">Чеки</th>
              <th class="py-2 pr-4 text-right hidden md:table-cell">Ср. чек</th>
              <th class="py-2 pr-4 hidden lg:table-cell w-28">Доля</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in entries"
              :key="row.locationId"
              class="border-b border-gray-50 hover:bg-primary/5 cursor-pointer transition-colors"
              :class="{ 'bg-primary/10': selectedId === row.locationId }"
              @click="$emit('select', row.locationId)"
            >
              <td class="py-2 pl-4 pr-2 tabular-nums font-semibold text-gray-500">
                <span
                  class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[11px]"
                  :class="rankClass(row.rank)"
                >
                  {{ row.rank }}
                </span>
              </td>
              <td class="py-2 pr-3">
                <div class="font-medium text-gray-800 truncate max-w-[140px] sm:max-w-none">{{ row.name }}</div>
                <div v-if="row.error" class="text-[10px] text-rose-600 mt-0.5">{{ row.error }}</div>
              </td>
              <td class="py-2 pr-3 text-right tabular-nums font-semibold text-primary whitespace-nowrap">
                {{ formatMoney(row.revenue) }}
              </td>
              <td class="py-2 pr-3 text-right tabular-nums hidden sm:table-cell">{{ row.checks }}</td>
              <td class="py-2 pr-4 text-right tabular-nums hidden md:table-cell whitespace-nowrap">
                {{ formatMoney(row.avgCheck) }}
              </td>
              <td class="py-2 pr-4 hidden lg:table-cell">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-primary rounded-full transition-all"
                      :style="{ width: `${sharePercent(row.revenue)}%` }"
                    />
                  </div>
                  <span class="text-[10px] text-gray-500 tabular-nums w-8 text-right">
                    {{ sharePercent(row.revenue) }}%
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AnalyticsPeriod, LeaderboardEntry } from '@/types/analytics'

const props = defineProps<{
  open: boolean
  loading: boolean
  iikoConfigured: boolean
  entries: LeaderboardEntry[]
  period: AnalyticsPeriod | null
  totals: { revenue: number; checks: number } | null
  selectedId: number | null
}>()

defineEmits<{
  toggle: []
  select: [locationId: number]
}>()

const maxRevenue = computed(() => {
  if (!props.entries.length) return 0
  return Math.max(...props.entries.map((e) => e.revenue), 0)
})

function formatMoney(value: number) {
  return `${Number(value || 0).toLocaleString('ru-RU', { maximumFractionDigits: 0 })} ₽`
}

function formatPeriod(period: AnalyticsPeriod) {
  const f = period.from.split('-').reverse().join('.')
  const t = period.to.split('-').reverse().join('.')
  return `${f} — ${t}`
}

function sharePercent(revenue: number) {
  const total = props.totals?.revenue || maxRevenue.value
  if (!total) return 0
  return Math.round((revenue / total) * 100)
}

function rankClass(rank: number) {
  if (rank === 1) return 'bg-amber-100 text-amber-800'
  if (rank === 2) return 'bg-gray-200 text-gray-700'
  if (rank === 3) return 'bg-orange-100 text-orange-800'
  return 'bg-gray-50 text-gray-500'
}
</script>

<style scoped>
.leaderboard-panel {
  @apply absolute bottom-4 left-4 z-[1100] w-[min(100vw-2rem,520px)] bg-white/95 backdrop-blur border border-gray-200 rounded-xl shadow-xl overflow-hidden;
}
.leaderboard-panel.collapsed {
  @apply w-auto min-w-[280px];
}
.leaderboard-header {
  @apply flex items-start justify-between gap-3 px-4 py-3 border-b border-gray-100 bg-white;
}
.leaderboard-body {
  @apply max-h-[min(50vh,360px)] overflow-y-auto;
}
.toggle-btn {
  @apply w-7 h-7 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 text-xs;
}
</style>
