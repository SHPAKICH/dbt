import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  fetchAnalyticsLeaderboard,
  fetchAnalyticsPointDetail,
  fetchAnalyticsPoints,
} from '@/services/analyticsApi'
import type {
  AnalyticsLeaderboard,
  AnalyticsPoint,
  AnalyticsPointDetail,
  AnalyticsPeriod,
  PeriodPreset,
  StatusFilter,
} from '@/types/analytics'
import { getIikoCredentialsPayload } from '@/utils/iikoSettings'

function localDateOffset(days: number): string {
  const d = new Date()
  d.setDate(d.getDate() + days)
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function periodForPreset(preset: PeriodPreset): AnalyticsPeriod {
  const today = localDateOffset(0)
  if (preset === 'today') {
    return { from: today, to: today }
  }
  if (preset === 'week') {
    return { from: localDateOffset(-6), to: today }
  }
  const d = new Date()
  const from = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
  return { from, to: today }
}

export const useAnalyticsStore = defineStore('analytics', () => {
  const points = ref<AnalyticsPoint[]>([])
  const selectedPointId = ref<number | null>(null)
  const pointDetails = ref<AnalyticsPointDetail | null>(null)
  const leaderboard = ref<AnalyticsLeaderboard | null>(null)

  const loadingPoints = ref(false)
  const loadingDetails = ref(false)
  const loadingLeaderboard = ref(false)
  const error = ref<string | null>(null)
  const iikoConfigured = ref(false)

  const statusFilter = ref<StatusFilter>('all')
  const searchQuery = ref('')
  const periodPreset = ref<PeriodPreset>('month')
  const period = ref<AnalyticsPeriod>(periodForPreset('month'))
  const leaderboardOpen = ref(true)

  const sidebarOpen = computed(() => selectedPointId.value !== null)

  const revenueByLocationId = computed(() => {
    const map = new Map<number, number>()
    for (const row of leaderboard.value?.leaderboard || []) {
      map.set(row.locationId, row.revenue)
    }
    return map
  })

  const filteredPoints = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    return points.value.filter((point) => {
      if (statusFilter.value !== 'all' && point.status !== statusFilter.value) {
        return false
      }
      if (!q) return true
      const haystack = `${point.name} ${point.description}`.toLowerCase()
      return haystack.includes(q)
    })
  })

  function refreshIikoStatus() {
    iikoConfigured.value = getIikoCredentialsPayload() !== null
  }

  function setPeriodPreset(preset: PeriodPreset) {
    periodPreset.value = preset
    period.value = periodForPreset(preset)
  }

  async function loadPoints() {
    loadingPoints.value = true
    error.value = null
    try {
      points.value = await fetchAnalyticsPoints()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Не удалось загрузить точки'
      points.value = []
    } finally {
      loadingPoints.value = false
    }
  }

  async function loadLeaderboard() {
    refreshIikoStatus()
    const iiko = getIikoCredentialsPayload()
    if (!iiko) {
      leaderboard.value = null
      return
    }

    loadingLeaderboard.value = true
    error.value = null
    try {
      leaderboard.value = await fetchAnalyticsLeaderboard(period.value, iiko)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Не удалось загрузить лидерборд'
      leaderboard.value = null
    } finally {
      loadingLeaderboard.value = false
    }
  }

  async function loadPointDetails(id: number) {
    loadingDetails.value = true
    error.value = null
    refreshIikoStatus()
    const iiko = getIikoCredentialsPayload()
    try {
      pointDetails.value = await fetchAnalyticsPointDetail(id, period.value, iiko)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Не удалось загрузить аналитику точки'
      pointDetails.value = null
    } finally {
      loadingDetails.value = false
    }
  }

  async function refreshAll() {
    await Promise.all([
      loadLeaderboard(),
      selectedPointId.value != null ? loadPointDetails(selectedPointId.value) : Promise.resolve(),
    ])
  }

  async function selectPoint(id: number) {
    if (selectedPointId.value === id && pointDetails.value?.id === id && !loadingDetails.value) {
      return
    }
    selectedPointId.value = id
    await loadPointDetails(id)
  }

  function closeSidebar() {
    selectedPointId.value = null
    pointDetails.value = null
    loadingDetails.value = false
  }

  function setStatusFilter(filter: StatusFilter) {
    statusFilter.value = filter
  }

  function setSearchQuery(value: string) {
    searchQuery.value = value
  }

  function toggleLeaderboard() {
    leaderboardOpen.value = !leaderboardOpen.value
  }

  return {
    points,
    selectedPointId,
    pointDetails,
    leaderboard,
    loadingPoints,
    loadingDetails,
    loadingLeaderboard,
    error,
    iikoConfigured,
    statusFilter,
    searchQuery,
    periodPreset,
    period,
    leaderboardOpen,
    sidebarOpen,
    revenueByLocationId,
    filteredPoints,
    refreshIikoStatus,
    setPeriodPreset,
    loadPoints,
    loadLeaderboard,
    loadPointDetails,
    refreshAll,
    selectPoint,
    closeSidebar,
    setStatusFilter,
    setSearchQuery,
    toggleLeaderboard,
  }
})
