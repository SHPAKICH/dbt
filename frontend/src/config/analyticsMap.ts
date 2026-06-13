import type { AnalyticsMapConfig, AnalyticsMapExtensions } from '@/types/analytics'

/** Центр и масштаб карты задаются здесь. */
export const ANALYTICS_MAP_CONFIG: AnalyticsMapConfig = {
  center: [59.9343, 30.3351],
  zoom: 11,
  minZoom: 5,
  maxZoom: 18,
}

/** Порог и флаги для будущей кластеризации / heatmap без смены архитектуры. */
export const ANALYTICS_MAP_EXTENSIONS: AnalyticsMapExtensions = {
  clusteringEnabled: false,
  heatmapEnabled: true,
  maxPointsBeforeClustering: 100,
}
