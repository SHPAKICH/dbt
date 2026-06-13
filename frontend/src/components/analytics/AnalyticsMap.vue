<template>
  <div class="relative w-full h-full min-h-0">
    <div ref="mapContainer" class="absolute inset-0 z-0" />

    <div
      v-if="loading"
      class="absolute inset-0 z-10 flex items-center justify-center bg-white/80"
    >
      <div class="text-sm text-gray-600">Загрузка карты…</div>
    </div>

    <div
      v-if="error"
      class="absolute top-3 left-3 right-3 z-20 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
    >
      {{ error }}
    </div>

    <div
      v-if="(heatPoints?.length || 0) > 0"
      class="absolute bottom-4 right-4 z-[1000] rounded-lg border border-gray-200 bg-white/95 backdrop-blur px-3 py-2 shadow-lg pointer-events-none"
    >
      <div class="text-[10px] font-semibold text-gray-500 uppercase mb-1.5">Выручка за период</div>
      <div class="h-2 w-36 rounded-full overflow-hidden flex">
        <div class="flex-1 bg-gradient-to-r from-green-500 via-yellow-400 to-red-500" />
      </div>
      <div class="flex justify-between text-[10px] text-gray-600 mt-1 tabular-nums">
        <span>{{ formatMoney(heatStats.max > 0 ? heatStats.min : 0) }}</span>
        <span>{{ formatMoney(heatStats.max > 0 ? heatStats.max : 0) }}</span>
      </div>
      <p v-if="heatStats.max <= 0" class="text-[10px] text-gray-400 mt-1">Загрузите лидерборд</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { ANALYTICS_MAP_CONFIG } from '@/config/analyticsMap'
import {
  createPointMarker,
  updateMarkerHighlight,
  type MarkerHandle,
} from '@/components/analytics/mapMarker'
import type { AnalyticsPoint } from '@/types/analytics'
import {
  buildHeatStats,
  revenueHeatColor,
  revenueHeatRadius,
  revenueRatio,
  type RevenueHeatPoint,
} from '@/utils/revenueHeat'

const props = defineProps<{
  points: AnalyticsPoint[]
  selectedPointId: number | null
  loading: boolean
  error: string | null
  heatPoints?: RevenueHeatPoint[]
}>()

const emit = defineEmits<{
  select: [id: number]
}>()

const mapContainer = ref<HTMLElement | null>(null)

let map: L.Map | null = null
let markerLayer: L.LayerGroup | null = null
let heatLayer: L.LayerGroup | null = null

const markerHandles = new Map<number, MarkerHandle>()
const heatCircles = new Map<number, L.Circle>()

const heatStats = computed(() => buildHeatStats(props.heatPoints || []))

function formatMoney(value: number) {
  return `${Number(value || 0).toLocaleString('ru-RU', { maximumFractionDigits: 0 })} ₽`
}

function initMap() {
  if (!mapContainer.value || map) return

  map = L.map(mapContainer.value, {
    center: ANALYTICS_MAP_CONFIG.center,
    zoom: ANALYTICS_MAP_CONFIG.zoom,
    minZoom: ANALYTICS_MAP_CONFIG.minZoom,
    maxZoom: ANALYTICS_MAP_CONFIG.maxZoom,
    zoomControl: true,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: ANALYTICS_MAP_CONFIG.maxZoom,
  }).addTo(map)

  heatLayer = L.layerGroup().addTo(map)
  markerLayer = L.layerGroup().addTo(map)
}

function clearMarkers() {
  markerHandles.forEach((handle) => {
    markerLayer?.removeLayer(handle.marker)
  })
  markerHandles.clear()
}

function clearHeatZones() {
  heatCircles.forEach((circle) => {
    heatLayer?.removeLayer(circle)
  })
  heatCircles.clear()
}

function syncHeatZones(points: RevenueHeatPoint[]) {
  if (!heatLayer) return

  const stats = buildHeatStats(points)
  const nextIds = new Set(points.map((p) => p.id))

  heatCircles.forEach((circle, id) => {
    if (!nextIds.has(id)) {
      heatLayer?.removeLayer(circle)
      heatCircles.delete(id)
    }
  })

  points.forEach((point) => {
    const ratio = revenueRatio(point.revenue, stats.min, stats.max)
    const color = revenueHeatColor(ratio)
    const radius = revenueHeatRadius(ratio)
    const existing = heatCircles.get(point.id)

    if (existing) {
      existing.setLatLng([point.lat, point.lng])
      existing.setStyle({
        fillColor: color,
        color,
        fillOpacity: 0.38,
        opacity: 0.55,
        weight: 1,
      })
      existing.setRadius(radius)
      return
    }

    const circle = L.circle([point.lat, point.lng], {
      radius,
      fillColor: color,
      color,
      fillOpacity: 0.38,
      opacity: 0.55,
      weight: 1,
      interactive: false,
    })
    heatLayer.addLayer(circle)
    heatCircles.set(point.id, circle)
  })
}

function syncMarkers(points: AnalyticsPoint[]) {
  if (!map || !markerLayer) return

  const nextIds = new Set(points.map((p) => p.id))

  markerHandles.forEach((handle, id) => {
    if (!nextIds.has(id)) {
      markerLayer?.removeLayer(handle.marker)
      markerHandles.delete(id)
    }
  })

  points.forEach((point) => {
    const existing = markerHandles.get(point.id)
    const highlighted = props.selectedPointId === point.id

    if (existing) {
      existing.marker.setLatLng([point.lat, point.lng])
      updateMarkerHighlight(existing, point, highlighted)
      return
    }

    const handle = createPointMarker(point, (id) => emit('select', id), highlighted)
    markerHandles.set(point.id, handle)
    markerLayer.addLayer(handle.marker)
  })

  if (points.length > 0 && !props.selectedPointId) {
    const bounds = L.latLngBounds(points.map((p) => [p.lat, p.lng] as [number, number]))
    map.fitBounds(bounds.pad(0.15))
  }
}

function focusSelectedPoint(points: AnalyticsPoint[], selectedId: number | null) {
  markerHandles.forEach((handle, id) => {
    const point = points.find((p) => p.id === id)
    if (!point) return
    updateMarkerHighlight(handle, point, selectedId === id)
  })

  if (!map || selectedId == null) return
  const point = points.find((p) => p.id === selectedId)
  if (point) {
    map.panTo([point.lat, point.lng], { animate: true })
  }
}

onMounted(() => {
  initMap()
  syncHeatZones(props.heatPoints || [])
  syncMarkers(props.points)
  focusSelectedPoint(props.points, props.selectedPointId)
  setTimeout(() => map?.invalidateSize(), 0)
})

watch(
  () => props.heatPoints,
  (points) => {
    syncHeatZones(points || [])
  },
  { deep: true },
)

watch(
  () => props.points,
  (points) => {
    syncMarkers(points)
    focusSelectedPoint(points, props.selectedPointId)
  },
  { deep: true },
)

watch(
  () => props.selectedPointId,
  (selectedId) => {
    focusSelectedPoint(props.points, selectedId)
  },
)

onBeforeUnmount(() => {
  clearMarkers()
  clearHeatZones()
  heatLayer = null
  markerLayer = null
  map?.remove()
  map = null
})

defineExpose({
  invalidateSize: () => map?.invalidateSize(),
})
</script>
