import { getToken, baseURL } from '@/api/client'
import type {
  AnalyticsLeaderboard,
  AnalyticsPoint,
  AnalyticsPointDetail,
  AnalyticsPeriod,
  IikoCredentials,
} from '@/types/analytics'

interface ApiSuccess<T> {
  success: boolean
  message?: string
}

async function analyticsGet<T>(url: string): Promise<T> {
  const token = getToken()
  const fullUrl = `${baseURL.replace(/\/$/, '')}${url}`
  const res = await fetch(fullUrl, {
    headers: {
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    credentials: 'include',
  })

  const data = await res.json().catch(() => ({} as ApiSuccess<never>))
  if (!res.ok) {
    throw new Error(data.message || res.statusText || 'Ошибка API аналитики')
  }

  return data as T
}

async function analyticsPost<T>(url: string, body: Record<string, unknown>): Promise<T> {
  const token = getToken()
  const fullUrl = `${baseURL.replace(/\/$/, '')}${url}`
  const res = await fetch(fullUrl, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    credentials: 'include',
    body: JSON.stringify(body),
  })

  const data = await res.json().catch(() => ({} as ApiSuccess<never>))
  if (!res.ok) {
    throw new Error(data.message || res.statusText || 'Ошибка API аналитики')
  }

  return data as T
}

export async function fetchAnalyticsPoints(): Promise<AnalyticsPoint[]> {
  const data = await analyticsGet<{ success: boolean; points: AnalyticsPoint[] }>(
    '/api/v1/analytics/points',
  )
  return data.points || []
}

export async function fetchAnalyticsPointDetail(
  id: number,
  period: AnalyticsPeriod,
  iiko: IikoCredentials | null,
): Promise<AnalyticsPointDetail> {
  if (iiko) {
    const data = await analyticsPost<{ success: boolean; point: AnalyticsPointDetail }>(
      `/api/v1/analytics/points/${id}`,
      { iiko, from: period.from, to: period.to },
    )
    if (!data.point) {
      throw new Error('Данные точки не получены')
    }
    return data.point
  }

  const data = await analyticsGet<{ success: boolean; point: AnalyticsPointDetail }>(
    `/api/v1/analytics/points/${id}`,
  )
  if (!data.point) {
    throw new Error('Данные точки не получены')
  }
  return data.point
}

export async function fetchAnalyticsLeaderboard(
  period: AnalyticsPeriod,
  iiko: IikoCredentials,
): Promise<AnalyticsLeaderboard> {
  const data = await analyticsPost<
    AnalyticsLeaderboard & { success: boolean }
  >('/api/v1/analytics/leaderboard', {
    iiko,
    from: period.from,
    to: period.to,
  })

  return {
    period: data.period,
    leaderboard: data.leaderboard || [],
    totals: data.totals || { revenue: 0, checks: 0, avgCheck: 0, locations: 0 },
    warnings: data.warnings || [],
  }
}
