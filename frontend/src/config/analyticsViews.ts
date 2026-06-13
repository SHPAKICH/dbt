import type { AnalyticsViewMode } from '@/types/analytics'

export const ANALYTICS_VIEW_MODES: Array<{ id: AnalyticsViewMode; label: string }> = [
  { id: 'map', label: 'Карта' },
  { id: 'checks', label: 'Чеки' },
  { id: 'reports', label: 'Отчёты' },
]

export function parseAnalyticsViewMode(value: unknown): AnalyticsViewMode {
  if (value === 'checks' || value === 'reports' || value === 'map') {
    return value
  }
  return 'map'
}
