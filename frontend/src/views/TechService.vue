<template>
  <div class="max-w-5xl mx-auto px-4 py-6 space-y-5">
    <div class="flex items-center gap-3 mb-2">
      <span class="text-2xl">🔧</span>
      <div>
        <h1 class="text-xl font-bold text-gray-900">Тех. Обслуживание</h1>
        <p class="text-sm text-gray-500">Управление заявками и фотоотчёты</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="flex-1 py-2.5 px-3 rounded-lg text-sm font-medium transition-all truncate"
        :class="activeTab === tab.key
          ? 'bg-white text-gray-900 shadow-sm'
          : 'text-gray-500 hover:text-gray-700'"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Заявки -->
    <div v-if="activeTab === 'requests'" class="space-y-4">
      <div
        v-if="loadingLocations"
        class="flex justify-center py-12"
      >
        <div class="animate-spin h-8 w-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <IssueCreateForm v-else :locations="locations" />
    </div>

    <!-- Tab: Фотоотчёты -->
    <div v-if="activeTab === 'photos'" class="space-y-4">
      <!-- Location selector -->
      <div v-if="locations.length > 1" class="flex gap-2 flex-wrap">
        <button
          v-for="loc in locations"
          :key="loc.id"
          class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-all"
          :class="selectedLocationId === loc.id
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300'"
          @click="selectLocation(loc.id)"
        >
          {{ loc.name }}
        </button>
      </div>
      <div v-else-if="locations.length === 1" class="text-sm text-gray-500">
        Точка: <span class="font-medium text-gray-800">{{ locations[0].name }}</span>
      </div>

      <div class="max-w-xs">
        <label class="block text-xs text-gray-500 mb-1">Дата смены</label>
        <input
          v-model="selectedReportDate"
          type="date"
          class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200"
        />
      </div>

      <!-- Items list -->
      <div v-if="selectedLocationId && !loadingPhotos" class="space-y-3">
        <div
          v-for="item in items"
          :key="item.key"
          class="rounded-xl border bg-white shadow-sm overflow-hidden"
          :class="photosMap[item.key]?.length ? 'border-green-200' : 'border-gray-200'"
        >
          <div
            class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none"
            @click="toggleItem(item.key)"
          >
            <div
              class="h-8 w-8 rounded-lg flex items-center justify-center text-sm shrink-0"
              :class="photosMap[item.key]?.length ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'"
            >
              <span v-if="photosMap[item.key]?.length">✅</span>
              <span v-else>📷</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-800 truncate">{{ item.label }}</div>
              <div class="text-xs text-gray-400">
                {{ photosMap[item.key]?.length || 0 }} / 5 фото
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform shrink-0"
              :class="{ 'rotate-180': expandedItems[item.key] }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>

          <!-- Expanded area -->
          <div v-if="expandedItems[item.key]" class="border-t border-gray-100 px-4 py-3 space-y-3">
            <!-- Existing photos -->
            <div v-if="photosMap[item.key]?.length" class="flex gap-2 flex-wrap">
              <div
                v-for="photo in photosMap[item.key]"
                :key="photo.id"
                class="relative group"
              >
                <img
                  :src="photo.url"
                  :alt="photo.file_name"
                  class="h-24 w-24 object-cover rounded-lg border border-gray-200 cursor-pointer"
                  @click="openPreview(photo)"
                />
                <button
                  class="absolute -top-1.5 -right-1.5 h-5 w-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                  @click.stop="deletePhoto(photo.id, item.key)"
                  title="Удалить"
                >&times;</button>
                <div class="text-[10px] text-gray-400 mt-0.5 text-center truncate max-w-[96px]">
                  {{ photo.author }}
                </div>
              </div>
            </div>

            <!-- Upload -->
            <div v-if="(photosMap[item.key]?.length || 0) < 5">
              <label
                class="flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-blue-400 hover:text-blue-500 cursor-pointer transition-colors"
                :class="{ 'opacity-50 pointer-events-none': uploading[item.key] }"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                {{ uploading[item.key] ? 'Загрузка...' : 'Добавить фото' }}
                <input
                  type="file"
                  multiple
                  accept="image/jpeg,image/png,image/webp,image/heic,image/heif,image/*"
                  class="hidden"
                  @change="e => handleUpload(e, item.key)"
                />
              </label>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loadingPhotos" class="flex justify-center py-10">
        <div class="animate-spin h-8 w-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <div v-if="selectedLocationId === null && !loadingLocations" class="text-center py-10 text-gray-400 text-sm">
        Выберите точку для просмотра фотоотчётов
      </div>
    </div>

    <!-- Tab: Обзор (admin) -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
      <div class="max-w-xs">
        <label class="block text-xs text-gray-500 mb-1">Дата обзора</label>
        <input
          v-model="selectedReportDate"
          type="date"
          class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200"
        />
      </div>

      <div v-if="loadingOverview" class="flex justify-center py-10">
        <div class="animate-spin h-8 w-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <div v-if="!loadingOverview && overviewData.length === 0" class="text-center py-10 text-gray-400 text-sm">
        Нет данных для обзора
      </div>

      <div v-for="entry in overviewData" :key="entry.location.id" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div
          class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none"
          @click="toggleOverviewLocation(entry.location.id)"
        >
          <div class="h-9 w-9 rounded-xl bg-blue-50 flex items-center justify-center text-lg shrink-0">📍</div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-800">{{ entry.location.name }}</div>
            <div class="text-xs text-gray-400 mt-0.5">
              Закрытие: {{ entry.closing.filled }}/{{ entry.closing.total }}
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <div class="h-2 w-20 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="overviewProgress(entry) >= 80 ? 'bg-green-500' : overviewProgress(entry) >= 40 ? 'bg-yellow-500' : 'bg-red-400'"
                :style="{ width: overviewProgress(entry) + '%' }"
              ></div>
            </div>
            <span class="text-xs text-gray-500 w-8 text-right">{{ overviewProgress(entry) }}%</span>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-180': expandedOverview[entry.location.id] }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>

        <div v-if="expandedOverview[entry.location.id]" class="border-t border-gray-100 px-5 py-4 space-y-4">
          <!-- Closing -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Фотоотчёт закрытия</h4>
            <div class="space-y-2">
              <div
                v-for="(label, key) in overviewClosingItems"
                :key="key"
                class="flex items-start gap-2 text-sm"
              >
                <span :class="entry.closing.photos[key]?.length ? 'text-green-500' : 'text-gray-300'">
                  {{ entry.closing.photos[key]?.length ? '✅' : '⬜' }}
                </span>
                <div class="flex-1 min-w-0">
                  <span class="text-gray-700">{{ label }}</span>
                  <span class="text-gray-400 ml-1">({{ entry.closing.photos[key]?.length || 0 }})</span>
                  <div v-if="entry.closing.photos[key]?.length" class="flex gap-1 mt-1 flex-wrap">
                    <img
                      v-for="p in entry.closing.photos[key]"
                      :key="p.id"
                      :src="p.url"
                      class="h-12 w-12 object-cover rounded border border-gray-200 cursor-pointer"
                      @click="openPreview(p)"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Photo preview modal -->
    <Teleport to="body">
      <div
        v-if="previewPhoto"
        class="fixed inset-0 z-[2000] bg-black/80 flex items-center justify-center p-4"
        @click.self="previewPhoto = null"
      >
        <div class="relative max-w-3xl max-h-[90vh] w-full">
          <button
            class="absolute -top-3 -right-3 h-8 w-8 bg-white rounded-full shadow flex items-center justify-center text-gray-700 hover:bg-gray-100 z-10"
            @click="previewPhoto = null"
          >&times;</button>
          <img
            :src="previewPhoto.url"
            :alt="previewPhoto.file_name"
            class="w-full h-auto max-h-[85vh] object-contain rounded-xl"
          />
          <div class="text-center mt-2 text-white/80 text-sm">
            {{ previewPhoto.author }} &middot; {{ formatDate(previewPhoto.created_at) }}
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { api } from '@/api/client'
import { getCurrentUser } from '@/router'
import IssueCreateForm from '@/components/techservice/IssueCreateForm.vue'
import { compressImagesInList } from '@/utils/imageUpload'

const user = ref(null)
const activeTab = ref('photos')
const locations = ref([])
const selectedLocationId = ref(null)
const selectedReportDate = ref(getLocalToday())
const items = ref([])
const photosMap = ref({})
const expandedItems = reactive({})
const uploading = reactive({})
const loadingLocations = ref(false)
const loadingPhotos = ref(false)
const previewPhoto = ref(null)

const overviewData = ref([])
const overviewClosingItems = ref({})
const expandedOverview = reactive({})
const loadingOverview = ref(false)

const isAdmin = computed(() => user.value?.isAdmin)

const tabs = computed(() => {
  const t = [
    { key: 'photos', label: 'Фотоотчёты' },
    { key: 'requests', label: 'Заявки' },
  ]
  if (isAdmin.value) {
    t.push({ key: 'overview', label: 'Обзор (все точки)' })
  }
  return t
})

onMounted(async () => {
  user.value = await getCurrentUser()
  await loadLocations()
})

watch(activeTab, (val) => {
  if (val === 'overview' && isAdmin.value && overviewData.value.length === 0) {
    loadOverview()
  }
})

watch(selectedReportDate, () => {
  if (activeTab.value === 'photos') {
    loadItemsAndPhotos()
  } else if (activeTab.value === 'overview' && isAdmin.value) {
    loadOverview()
  }
})

async function loadLocations() {
  loadingLocations.value = true
  try {
    const res = await api.photoReport.locations()
    locations.value = res.locations || []
    if (locations.value.length === 1) {
      selectLocation(locations.value[0].id)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingLocations.value = false
  }
}

function selectLocation(id) {
  selectedLocationId.value = id
  loadItemsAndPhotos()
}

async function loadItemsAndPhotos() {
  if (!selectedLocationId.value) return
  loadingPhotos.value = true
  try {
    const [itemsRes, photosRes] = await Promise.all([
      api.photoReport.items('closing'),
      api.photoReport.photos(selectedLocationId.value, 'closing', selectedReportDate.value),
    ])
    items.value = itemsRes.items || []
    photosMap.value = photosRes.photos || {}
  } catch (e) {
    console.error(e)
  } finally {
    loadingPhotos.value = false
  }
}

function toggleItem(key) {
  expandedItems[key] = !expandedItems[key]
}

async function handleUpload(event, itemKey) {
  const files = Array.from(event.target.files || [])
  if (!files.length) return

  const currentCount = photosMap.value[itemKey]?.length || 0
  const maxNew = 5 - currentCount
  if (maxNew <= 0) return

  const toUpload = files.slice(0, maxNew)
  uploading[itemKey] = true

  try {
    const prepared = await compressImagesInList(toUpload)
    const formData = new FormData()
    formData.append('location_id', selectedLocationId.value)
    formData.append('report_type', 'closing')
    formData.append('item_key', itemKey)
    formData.append('report_date', selectedReportDate.value)
    prepared.forEach(f => formData.append('photos[]', f))
    await api.photoReport.upload(formData)
    await loadItemsAndPhotos()
  } catch (e) {
    alert(e.data?.message || e.message || 'Ошибка загрузки')
  } finally {
    uploading[itemKey] = false
    event.target.value = ''
  }
}

async function deletePhoto(id, itemKey) {
  if (!confirm('Удалить фото?')) return
  try {
    await api.photoReport.deletePhoto(id)
    await loadItemsAndPhotos()
  } catch (e) {
    alert(e.data?.message || e.message || 'Ошибка удаления')
  }
}

function openPreview(photo) {
  previewPhoto.value = photo
}

async function loadOverview() {
  loadingOverview.value = true
  try {
    const res = await api.photoReport.overview(selectedReportDate.value)
    overviewData.value = res.overview || []
    overviewClosingItems.value = res.closingItems || {}
  } catch (e) {
    console.error(e)
  } finally {
    loadingOverview.value = false
  }
}

function toggleOverviewLocation(id) {
  expandedOverview[id] = !expandedOverview[id]
}

function overviewProgress(entry) {
  if (entry.closing.total === 0) return 0
  return Math.round((entry.closing.filled / entry.closing.total) * 100)
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function getLocalToday() {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}
</script>
