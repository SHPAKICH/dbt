<template>
  <div class="space-y-5">
    <div>
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Чеки по дням</h4>
      <div ref="activityRef" class="h-40 w-full" />
    </div>
    <div>
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Выручка по дням</h4>
      <div ref="revenueRef" class="h-40 w-full" />
    </div>
    <div v-if="checksByHour.length">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Выручка по часам</h4>
      <div ref="hourRef" class="h-44 w-full" />
    </div>
    <div v-if="byPaymentType.length || byOrderType.length" class="grid grid-cols-1 gap-4">
      <div v-if="byPaymentType.length">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Типы оплаты</h4>
        <div ref="paymentRef" class="h-48 w-full" />
      </div>
      <div v-if="byOrderType.length">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Типы заказов</h4>
        <div ref="orderTypeRef" class="h-48 w-full" />
      </div>
    </div>
    <div v-if="productGroups.length">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Группы блюд</h4>
      <div ref="groupsRef" class="h-48 w-full" />
    </div>
    <div v-if="topProducts.length">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Топ блюд по выручке</h4>
      <div ref="productsRef" class="h-56 w-full" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import * as echarts from 'echarts/core'
import { BarChart, LineChart, PieChart } from 'echarts/charts'
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import type {
  BreakdownRow,
  ChartPoint,
  HourBreakdownRow,
  ProductGroupRow,
  TopProductRow,
} from '@/types/analytics'

echarts.use([
  LineChart,
  BarChart,
  PieChart,
  GridComponent,
  TooltipComponent,
  LegendComponent,
  CanvasRenderer,
])

const props = defineProps<{
  activity: ChartPoint[]
  revenue: ChartPoint[]
  checksByHour: HourBreakdownRow[]
  byPaymentType: BreakdownRow[]
  byOrderType: BreakdownRow[]
  topProducts: TopProductRow[]
  productGroups: ProductGroupRow[]
}>()

const activityRef = ref<HTMLElement | null>(null)
const revenueRef = ref<HTMLElement | null>(null)
const hourRef = ref<HTMLElement | null>(null)
const paymentRef = ref<HTMLElement | null>(null)
const orderTypeRef = ref<HTMLElement | null>(null)
const groupsRef = ref<HTMLElement | null>(null)
const productsRef = ref<HTMLElement | null>(null)

const charts: Record<string, echarts.ECharts | null> = {
  activity: null,
  revenue: null,
  hour: null,
  payment: null,
  orderType: null,
  groups: null,
  products: null,
}

function formatDateLabel(date: string) {
  const parts = date.split('-')
  if (parts.length === 3) return `${parts[2]}.${parts[1]}`
  return date
}

function moneyTooltip(value: unknown) {
  const n = Number(value)
  if (!Number.isFinite(n)) return '—'
  return `${n.toLocaleString('ru-RU', { maximumFractionDigits: 0 })} ₽`
}

function initChart(key: string, el: HTMLElement | null) {
  if (!el) return null
  if (charts[key]) {
    charts[key]?.dispose()
  }
  charts[key] = echarts.init(el)
  return charts[key]
}

function buildLineOption(data: ChartPoint[], color: string, isMoney = false) {
  return {
    animation: false,
    grid: { left: 44, right: 12, top: 12, bottom: 28 },
    tooltip: {
      trigger: 'axis',
      valueFormatter: (value: unknown) => {
        const n = Number(value)
        if (!Number.isFinite(n)) return '—'
        return isMoney
          ? `${n.toLocaleString('ru-RU', { maximumFractionDigits: 0 })} ₽`
          : n.toLocaleString('ru-RU')
      },
    },
    xAxis: {
      type: 'category',
      data: data.map((d) => formatDateLabel(d.date)),
      axisLabel: { fontSize: 10, color: '#6b7280' },
      axisLine: { lineStyle: { color: '#e5e7eb' } },
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        fontSize: 10,
        color: '#6b7280',
        formatter: (v: number) => (isMoney && v >= 1000 ? `${Math.round(v / 1000)}k` : String(v)),
      },
      splitLine: { lineStyle: { color: '#f3f4f6' } },
    },
    series: [
      {
        type: 'line',
        smooth: true,
        showSymbol: false,
        data: data.map((d) => d.value),
        lineStyle: { width: 2, color },
        areaStyle: { color: `${color}22` },
      },
    ],
  }
}

function buildHourBarOption(rows: HourBreakdownRow[]) {
  const sorted = [...rows].sort((a, b) => (a.hour ?? 99) - (b.hour ?? 99))
  return {
    animation: false,
    grid: { left: 44, right: 12, top: 12, bottom: 28 },
    tooltip: { trigger: 'axis', valueFormatter: moneyTooltip },
    xAxis: {
      type: 'category',
      data: sorted.map((r) => r.label || `${r.hour ?? '—'}:00`),
      axisLabel: { fontSize: 9, color: '#6b7280', rotate: sorted.length > 12 ? 45 : 0 },
    },
    yAxis: {
      type: 'value',
      axisLabel: { fontSize: 10, color: '#6b7280' },
      splitLine: { lineStyle: { color: '#f3f4f6' } },
    },
    series: [
      {
        type: 'bar',
        data: sorted.map((r) => r.sum),
        itemStyle: { color: '#2563eb', borderRadius: [4, 4, 0, 0] },
      },
    ],
  }
}

function buildPieOption(rows: BreakdownRow[]) {
  return {
    animation: false,
    tooltip: {
      trigger: 'item',
      formatter: (p: { name: string; value: number; percent: number }) =>
        `${p.name}<br/>${moneyTooltip(p.value)} (${p.percent}%)`,
    },
    legend: {
      type: 'scroll',
      orient: 'horizontal',
      bottom: 0,
      textStyle: { fontSize: 10 },
    },
    series: [
      {
        type: 'pie',
        radius: ['38%', '68%'],
        center: ['50%', '42%'],
        data: rows.map((r) => ({ name: r.label || '—', value: r.sum })),
        label: { show: false },
      },
    ],
  }
}

function buildGroupsBarOption(groups: ProductGroupRow[]) {
  return {
    animation: false,
    grid: { left: 44, right: 12, top: 12, bottom: 28 },
    tooltip: { trigger: 'axis', valueFormatter: moneyTooltip },
    xAxis: {
      type: 'category',
      data: groups.map((g) => g.label),
      axisLabel: { fontSize: 10, color: '#6b7280' },
    },
    yAxis: {
      type: 'value',
      axisLabel: { fontSize: 10, color: '#6b7280' },
      splitLine: { lineStyle: { color: '#f3f4f6' } },
    },
    series: [
      {
        type: 'bar',
        data: groups.map((g) => g.sum),
        itemStyle: {
          color: (params: { dataIndex: number }) =>
            ['#16a34a', '#2563eb', '#d97706', '#7c3aed', '#0891b2'][params.dataIndex % 5],
          borderRadius: [4, 4, 0, 0],
        },
      },
    ],
  }
}

function buildProductsBarOption(products: TopProductRow[]) {
  const names = products.map((p) => p.name).reverse()
  const sums = products.map((p) => p.sum).reverse()
  return {
    animation: false,
    grid: { left: 8, right: 44, top: 8, bottom: 8, containLabel: true },
    tooltip: { trigger: 'axis', valueFormatter: moneyTooltip },
    xAxis: {
      type: 'value',
      axisLabel: { fontSize: 10, color: '#6b7280' },
      splitLine: { lineStyle: { color: '#f3f4f6' } },
    },
    yAxis: {
      type: 'category',
      data: names,
      axisLabel: {
        fontSize: 10,
        color: '#374151',
        width: 120,
        overflow: 'truncate',
      },
    },
    series: [
      {
        type: 'bar',
        data: sums,
        itemStyle: { color: '#16a34a', borderRadius: [0, 4, 4, 0] },
      },
    ],
  }
}

function renderCharts() {
  const activity = initChart('activity', activityRef.value)
  activity?.setOption(buildLineOption(props.activity, '#2563eb'), true)

  const revenue = initChart('revenue', revenueRef.value)
  revenue?.setOption(buildLineOption(props.revenue, '#16a34a', true), true)

  if (props.checksByHour.length) {
    const hour = initChart('hour', hourRef.value)
    hour?.setOption(buildHourBarOption(props.checksByHour), true)
  }

  if (props.byPaymentType.length) {
    const payment = initChart('payment', paymentRef.value)
    payment?.setOption(buildPieOption(props.byPaymentType), true)
  }

  if (props.byOrderType.length) {
    const orderType = initChart('orderType', orderTypeRef.value)
    orderType?.setOption(buildPieOption(props.byOrderType), true)
  }

  if (props.productGroups.length) {
    const groups = initChart('groups', groupsRef.value)
    groups?.setOption(buildGroupsBarOption(props.productGroups), true)
  }

  if (props.topProducts.length) {
    const products = initChart('products', productsRef.value)
    products?.setOption(buildProductsBarOption(props.topProducts), true)
  }
}

function resizeCharts() {
  Object.values(charts).forEach((c) => c?.resize())
}

onMounted(() => {
  renderCharts()
  window.addEventListener('resize', resizeCharts)
})

watch(
  () => [
    props.activity,
    props.revenue,
    props.checksByHour,
    props.byPaymentType,
    props.byOrderType,
    props.topProducts,
    props.productGroups,
  ],
  () => renderCharts(),
  { deep: true },
)

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeCharts)
  Object.keys(charts).forEach((key) => {
    charts[key]?.dispose()
    charts[key] = null
  })
})

defineExpose({ resizeCharts })
</script>
