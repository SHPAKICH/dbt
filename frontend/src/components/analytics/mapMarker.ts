import L from 'leaflet'
import type { AnalyticsPoint, PointStatus } from '@/types/analytics'

const MARKER_COLORS: Record<PointStatus, string> = {
  active: '#16a34a',
  inactive: '#dc2626',
}

export function getMarkerColor(status: PointStatus): string {
  return MARKER_COLORS[status] || MARKER_COLORS.inactive
}

export function createMarkerIcon(status: PointStatus, highlighted = false): L.DivIcon {
  const color = getMarkerColor(status)
  const size = highlighted ? 18 : 14
  const ring = highlighted ? '0 0 0 3px rgba(59,130,246,0.35)' : 'none'

  return L.divIcon({
    className: 'analytics-marker-icon',
    html: `<span style="
      display:block;
      width:${size}px;
      height:${size}px;
      border-radius:50%;
      background:${color};
      border:2px solid #fff;
      box-shadow:0 1px 4px rgba(0,0,0,.35), ${ring};
    "></span>`,
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2],
  })
}

export interface MarkerHandle {
  marker: L.Marker
  pointId: number
}

export function createPointMarker(
  point: AnalyticsPoint,
  onSelect: (id: number) => void,
  highlighted = false,
): MarkerHandle {
  const marker = L.marker([point.lat, point.lng], {
    icon: createMarkerIcon(point.status, highlighted),
    title: point.name,
  })

  marker.bindTooltip(point.name, {
    direction: 'top',
    offset: [0, -10],
    opacity: 0.95,
  })

  marker.on('click', () => onSelect(point.id))

  return { marker, pointId: point.id }
}

export function updateMarkerHighlight(handle: MarkerHandle, point: AnalyticsPoint, highlighted: boolean) {
  handle.marker.setIcon(createMarkerIcon(point.status, highlighted))
  if (highlighted) {
    handle.marker.openTooltip()
  }
}
