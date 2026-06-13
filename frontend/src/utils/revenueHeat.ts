/**
 * Интерполяция цвета тепловой карты: зелёный (низкая выручка) → красный (высокая).
 */
export function revenueRatio(value: number, min: number, max: number): number {
  if (!Number.isFinite(value) || value <= 0) {
    return 0
  }
  if (max <= min) {
    return 1
  }
  return Math.max(0, Math.min(1, (value - min) / (max - min)))
}

function mixChannel(a: number, b: number, t: number): number {
  return Math.round(a + (b - a) * t)
}

export function revenueHeatColor(ratio: number): string {
  const t = Math.max(0, Math.min(1, ratio))
  // 0 → green, 0.5 → yellow, 1 → red
  if (t <= 0.5) {
    const local = t / 0.5
    return `rgb(${mixChannel(34, 234, local)}, ${mixChannel(197, 179, local)}, ${mixChannel(94, 8, local)})`
  }
  const local = (t - 0.5) / 0.5
  return `rgb(${mixChannel(234, 239, local)}, ${mixChannel(179, 68, local)}, ${mixChannel(8, 68, local)})`
}

export function revenueHeatRadius(ratio: number): number {
  return 280 + ratio * 520
}

export interface RevenueHeatPoint {
  id: number
  lat: number
  lng: number
  revenue: number
}

export function buildHeatStats(points: RevenueHeatPoint[]) {
  const values = points.map((p) => p.revenue).filter((v) => v > 0)
  const min = values.length ? Math.min(...values) : 0
  const max = values.length ? Math.max(...values) : 0
  return { min, max }
}
