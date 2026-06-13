export type PointStatus = 'active' | 'inactive'

export type StatusFilter = 'all' | PointStatus

export type PeriodPreset = 'today' | 'week' | 'month'

export type AnalyticsViewMode = 'map' | 'checks' | 'reports'

export interface AnalyticsPeriod {
  from: string
  to: string
}

export interface AnalyticsPoint {
  id: number
  name: string
  lat: number
  lng: number
  status: PointStatus
  description: string
}

export interface ChartPoint {
  date: string
  value: number
}

export interface BreakdownRow {
  label: string
  sum: number
  checks: number
  share?: number
  hour?: number | null
}

export interface HourBreakdownRow extends BreakdownRow {
  hour: number | null
}

export interface TopProductRow {
  name: string
  sum: number
  qty: number
  sharePercent?: number
  avgPrice?: number
}

export interface ProductGroupRow {
  id: string
  label: string
  sum: number
  qty: number
}

export interface AnalyticsPointDetail extends AnalyticsPoint {
  address: string
  dataSource?: 'iiko' | 'daily_report'
  period?: AnalyticsPeriod
  departmentId?: string | null
  departmentName?: string | null
  lastUpdatedAt: string | null
  operationsDay: number
  operationsWeek: number
  operationsMonth: number
  totalRevenue: number
  avgCheck: number
  conversion: number | null
  activityChart: ChartPoint[]
  revenueChart: ChartPoint[]
  checksByHour: HourBreakdownRow[]
  byPaymentType: BreakdownRow[]
  byOrderType: BreakdownRow[]
  topProducts: TopProductRow[]
  productGroups: ProductGroupRow[]
  productsSummary?: { totalSum: number; totalQty: number; productCount: number } | null
  warning?: string | null
}

export interface LeaderboardEntry {
  rank: number
  locationId: number
  name: string
  status: PointStatus
  departmentId: string | null
  departmentName: string | null
  matched: boolean
  revenue: number
  checks: number
  avgCheck: number
  error: string | null
}

export interface AnalyticsLeaderboard {
  period: AnalyticsPeriod
  leaderboard: LeaderboardEntry[]
  totals: {
    revenue: number
    checks: number
    avgCheck: number
    locations: number
  }
  warnings: string[]
}

export interface AnalyticsMapConfig {
  center: [number, number]
  zoom: number
  minZoom: number
  maxZoom: number
}

/**
 * Заготовки для будущих расширений карты (кластеризация, тепловая карта).
 */
export interface AnalyticsMapExtensions {
  clusteringEnabled: boolean
  heatmapEnabled: boolean
  maxPointsBeforeClustering: number
}

export interface IikoCredentials {
  baseUrl: string
  login: string
  password: string
}
