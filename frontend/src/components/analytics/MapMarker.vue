<script lang="ts">
export {
  createMarkerIcon,
  createPointMarker,
  getMarkerColor,
  updateMarkerHighlight,
} from './mapMarker'
export type { MarkerHandle } from './mapMarker'
</script>

<template>
  <span class="hidden" aria-hidden="true" />
</template>

<style>
.analytics-marker-icon {
  background: transparent !important;
  border: none !important;
}
</style>
