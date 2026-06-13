<template>
  <aside
    class="analytics-sidebar"
    :class="{ open: open }"
    role="complementary"
    aria-label="Аналитика точки"
  >
    <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-gray-200">
      <div class="min-w-0">
        <h2 class="text-lg font-bold text-gray-800 truncate">
          {{ detail?.name || 'Точка' }}
        </h2>
        <p v-if="detail" class="text-sm mt-1 flex flex-wrap items-center gap-2">
          <span
            class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
            :class="detail.status === 'active'
              ? 'bg-emerald-50 text-emerald-700'
              : 'bg-rose-50 text-rose-700'"
          >
            {{ detail.status === 'active' ? 'Активна' : 'Неактивна' }}
          </span>
          <span
            v-if="detail.dataSource === 'iiko'"
            class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"
          >
            iiko
          </span>
        </p>
        <p v-if="detail?.departmentName" class="text-[11px] text-gray-500 mt-1 truncate">
          {{ detail.departmentName }}
        </p>
      </div>
      <button
        type="button"
        class="text-gray-400 hover:text-gray-600 text-2xl leading-none shrink-0"
        aria-label="Закрыть панель"
        @click="$emit('close')"
      >
        ×
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-4">
      <div v-if="loading" class="py-16 text-center text-sm text-gray-500">
        Загрузка аналитики из iiko…
      </div>

      <template v-else-if="detail">
        <p
          v-if="detail.warning"
          class="mb-4 text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2"
        >
          {{ detail.warning }}
        </p>

        <dl class="grid grid-cols-2 gap-3 text-sm mb-5">
          <div class="col-span-2">
            <dt class="text-gray-500 text-xs">Адрес</dt>
            <dd class="font-medium text-gray-800 mt-0.5">{{ detail.address }}</dd>
          </div>
          <div class="col-span-2" v-if="detail.period">
            <dt class="text-gray-500 text-xs">Период</dt>
            <dd class="font-medium text-gray-800 mt-0.5">{{ formatPeriod(detail.period) }}</dd>
          </div>
          <div class="col-span-2">
            <dt class="text-gray-500 text-xs">Обновлено</dt>
            <dd class="font-medium text-gray-800 mt-0.5">{{ formatDateTime(detail.lastUpdatedAt) }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs">Чеков за день</dt>
            <dd class="font-semibold text-gray-900 mt-0.5 tabular-nums">{{ detail.operationsDay }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs">За неделю</dt>
            <dd class="font-semibold text-gray-900 mt-0.5 tabular-nums">{{ detail.operationsWeek }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs">За период</dt>
            <dd class="font-semibold text-gray-900 mt-0.5 tabular-nums">{{ detail.operationsMonth }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs">Выручка</dt>
            <dd class="font-semibold text-primary mt-0.5 tabular-nums">{{ formatMoney(detail.totalRevenue) }}</dd>
          </div>
          <div>
            <dt class="text-gray-500 text-xs">Средний чек</dt>
            <dd class="font-semibold text-gray-900 mt-0.5 tabular-nums">{{ formatMoney(detail.avgCheck) }}</dd>
          </div>
          <div v-if="detail.productsSummary">
            <dt class="text-gray-500 text-xs">Позиций в отчёте</dt>
            <dd class="font-semibold text-gray-900 mt-0.5 tabular-nums">{{ detail.productsSummary.productCount }}</dd>
          </div>
          <div v-if="detail.productsSummary">
            <dt class="text-gray-500 text-xs">Продано (шт.)</dt>
            <dd class="font-semibold text-gray-900 mt-0.5 tabular-nums">
              {{ Number(detail.productsSummary.totalQty).toLocaleString('ru-RU', { maximumFractionDigits: 0 }) }}
            </dd>
          </div>
        </dl>

        <AnalyticsCharts
          :activity="detail.activityChart"
          :revenue="detail.revenueChart"
          :checks-by-hour="detail.checksByHour || []"
          :by-payment-type="detail.byPaymentType || []"
          :by-order-type="detail.byOrderType || []"
          :top-products="detail.topProducts || []"
          :product-groups="detail.productGroups || []"
        />
      </template>

      <p v-else class="py-10 text-center text-sm text-gray-500">
        Выберите точку на карте или в лидерборде
      </p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import AnalyticsCharts from '@/components/analytics/AnalyticsCharts.vue'
import type { AnalyticsPeriod, AnalyticsPointDetail } from '@/types/analytics'

defineProps<{
  open: boolean
  loading: boolean
  detail: AnalyticsPointDetail | null
}>()

defineEmits<{
  close: []
}>()

function formatMoney(value: number) {
  return `${Number(value || 0).toLocaleString('ru-RU', { maximumFractionDigits: 0 })} ₽`
}

function formatDateTime(value: string | null) {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString('ru-RU')
}

function formatPeriod(period: AnalyticsPeriod) {
  const f = period.from.split('-').reverse().join('.')
  const t = period.to.split('-').reverse().join('.')
  return `${f} — ${t}`
}
</script>

<style scoped>
.analytics-sidebar {
  @apply fixed top-0 right-0 z-[1200] h-full w-full max-w-xl bg-white border-l border-gray-200 shadow-2xl flex flex-col translate-x-full transition-transform duration-300;
}
.analytics-sidebar.open {
  @apply translate-x-0;
}
</style>
