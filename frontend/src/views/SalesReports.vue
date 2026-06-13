<template>
  <div :class="embedded ? 'h-full overflow-y-auto px-4 py-4 md:px-6' : 'max-w-6xl mx-auto px-4 pb-10'">
    <template v-if="!embedded">
      <h1 class="text-2xl font-bold text-gray-800 mb-1">Отчёты по продажам</h1>
      <p class="text-sm text-gray-500 mb-6">
        Выберите одно или несколько блюд и посмотрите долю в общей выручке и количестве продаж за период.
      </p>
    </template>

    <div v-if="!settingsReady" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 max-w-xl text-center">
      <h2 class="text-xl font-semibold text-gray-800 mb-2">Сначала настройте подключение к iiko</h2>
      <router-link to="/settings" class="inline-flex px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 mt-4">
        Перейти к настройкам
      </router-link>
    </div>

    <template v-else>
      <div class="flex flex-wrap items-end gap-3 mb-4">
        <div class="min-w-[220px] flex-1">
          <label class="block text-xs font-medium text-gray-600 mb-1">Точка iiko</label>
          <select
            v-model="departmentId"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            :disabled="loadingDepartments"
          >
            <option value="">Все точки</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">
              {{ d.name }}{{ d.code ? ` · ${d.code}` : '' }}
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <div class="flex flex-wrap items-end gap-2">
            <label class="text-xs text-gray-500">с</label>
            <input v-model="periodFrom" type="date" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" />
            <label class="text-xs text-gray-500">по</label>
            <input v-model="periodTo" type="date" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" />
          </div>
          <div class="flex flex-wrap gap-1.5">
            <button
              type="button"
              class="px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors"
              :class="periodPreset === 'today'
                ? 'border-primary bg-primary/10 text-primary'
                : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              :disabled="loading"
              @click="setPeriodPreset('today')"
            >
              Сегодня
            </button>
            <button
              type="button"
              class="px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors"
              :class="periodPreset === 'yesterday'
                ? 'border-primary bg-primary/10 text-primary'
                : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              :disabled="loading"
              @click="setPeriodPreset('yesterday')"
            >
              Вчера
            </button>
          </div>
        </div>
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
          :disabled="loading"
          @click="loadReport"
        >
          {{ loading ? 'Загрузка…' : 'Загрузить отчёт' }}
        </button>
      </div>

      <div v-if="error" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 mb-4">
        {{ error }}
      </div>

      <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase">Общая выручка</div>
          <div class="text-2xl font-semibold mt-1 text-primary">{{ formatMoney(summary.totalSum) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase">Продано, шт</div>
          <div class="text-2xl font-semibold mt-1">{{ formatNum(summary.totalQty) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase">Позиций в отчёте</div>
          <div class="text-2xl font-semibold mt-1">{{ summary.productCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
          <div class="text-xs text-gray-500 uppercase">Период</div>
          <div class="text-sm font-medium mt-2 text-gray-700">{{ formatPeriodLabel }}</div>
        </div>
      </div>

      <!-- Выбранная группа блюд -->
      <section
        v-if="selectedNames.length"
        class="bg-gradient-to-br from-primary/10 to-white rounded-2xl border border-primary/20 shadow-sm p-5 mb-6"
      >
        <div class="flex flex-wrap items-center gap-2 mb-4">
          <h2 class="text-sm font-semibold text-gray-800">Выбранная группа ({{ selectedNames.length }})</h2>
          <button
            type="button"
            class="ml-auto text-xs text-gray-500 hover:text-gray-700 underline"
            @click="clearSelection"
          >
            Сбросить выбор
          </button>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-4">
          <span
            v-for="name in selectedNames"
            :key="name"
            class="text-xs px-2 py-1 rounded-full bg-white border border-primary/30 text-gray-700"
          >
            {{ name }}
          </span>
        </div>
        <div v-if="selectedBlock" class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div class="rounded-xl bg-white/80 border border-gray-100 p-3">
            <div class="text-xs text-gray-500">Доля выручки</div>
            <div class="text-3xl font-bold text-primary mt-1">{{ formatShare(selectedBlock.sharePercent) }}</div>
          </div>
          <div class="rounded-xl bg-white/80 border border-gray-100 p-3">
            <div class="text-xs text-gray-500">Выручка группы</div>
            <div class="text-xl font-semibold mt-1 tabular-nums">{{ formatMoney(selectedBlock.sum) }}</div>
          </div>
          <div class="rounded-xl bg-white/80 border border-gray-100 p-3">
            <div class="text-xs text-gray-500">Доля по количеству</div>
            <div class="text-xl font-semibold mt-1 tabular-nums">{{ formatShare(selectedBlock.qtySharePercent) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ formatNum(selectedBlock.qty) }} шт</div>
          </div>
          <div class="rounded-xl bg-white/80 border border-gray-100 p-3">
            <div class="text-xs text-gray-500">Остальные блюда</div>
            <div class="text-xl font-semibold mt-1 tabular-nums">{{ formatShare(selectedBlock.restSharePercent) }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ formatMoney(selectedBlock.restSum) }}</div>
          </div>
        </div>
        <p v-else-if="loadedOnce" class="text-sm text-amber-800">
          Выбранные блюда не продавались за период (или названия не совпали с iiko).
        </p>
        <div v-if="selectedBlock?.items?.length" class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                <th class="pb-2 font-medium">Блюдо</th>
                <th class="pb-2 font-medium text-right">Выручка</th>
                <th class="pb-2 font-medium text-right">Кол-во</th>
                <th class="pb-2 font-medium text-right">% выручки</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in selectedBlock.items" :key="row.name">
                <td class="py-2 text-gray-800">{{ row.name }}</td>
                <td class="py-2 text-right font-medium tabular-nums">{{ formatMoney(row.sum) }}</td>
                <td class="py-2 text-right tabular-nums">{{ formatNum(row.qty) }}</td>
                <td class="py-2 text-right text-primary font-medium tabular-nums">{{ formatShare(row.sharePercent) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div class="mb-4">
        <div class="text-xs font-medium text-gray-600 mb-2">Быстрые группы</div>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="group in quickGroups"
            :key="group.id"
            type="button"
            class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
            :class="activeQuickGroup === group.id
              ? 'border-primary bg-primary/10 text-primary'
              : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
            :disabled="loading"
            @click="applyQuickGroup(group)"
          >
            {{ group.label }}
          </button>
          <button
            v-if="activeQuickGroup"
            type="button"
            class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-500 hover:bg-gray-50"
            :disabled="loading"
            @click="clearQuickGroup"
          >
            Сбросить группу
          </button>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 mb-4">
        <input
          v-model="tableFilter"
          type="search"
          placeholder="Поиск по названию блюда…"
          class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
        />
        <button
          type="button"
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50"
          :disabled="!filteredProducts.length"
          @click="selectAllVisible"
        >
          Выбрать видимые
        </button>
      </div>

      <!-- Поиск в справочнике -->
      <details class="mb-4 bg-white rounded-xl border border-gray-200">
        <summary class="px-4 py-3 text-sm font-medium text-gray-700 cursor-pointer select-none">
          Добавить блюдо из справочника iiko
        </summary>
        <div class="px-4 pb-4 border-t border-gray-100 pt-3">
          <div class="flex gap-2 mb-2">
            <input
              v-model="nomenclatureQuery"
              type="search"
              placeholder="Название или код…"
              class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm"
              @keydown.enter.prevent="searchNomenclature"
            />
            <button
              type="button"
              class="px-3 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50"
              :disabled="nomenclatureLoading"
              @click="searchNomenclature"
            >
              {{ nomenclatureLoading ? '…' : 'Найти' }}
            </button>
          </div>
          <ul v-if="nomenclatureHits.length" class="max-h-40 overflow-y-auto divide-y divide-gray-50 text-sm">
            <li
              v-for="p in nomenclatureHits"
              :key="p.id"
              class="py-2 flex items-center gap-2"
            >
              <span class="flex-1 min-w-0 truncate">{{ p.name }}</span>
              <button
                type="button"
                class="text-xs text-primary font-medium shrink-0"
                @click="toggleProductName(p.name)"
              >
                {{ isSelected(p.name) ? 'Убрать' : 'Добавить' }}
              </button>
            </li>
          </ul>
          <p v-else-if="nomenclatureSearched" class="text-xs text-gray-500">Ничего не найдено.</p>
        </div>
      </details>

      <div v-if="loading && !products.length" class="py-16 text-center text-gray-500 text-sm">
        Загрузка продаж из iiko…
      </div>
      <div v-else-if="loadedOnce && !products.length" class="py-16 text-center text-gray-500 text-sm">
        За период продаж по блюдам не найдено.
      </div>

      <div v-else-if="products.length" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr class="text-left text-xs text-gray-500">
              <th class="px-3 py-3 w-10" />
              <th class="px-3 py-3 font-medium">Блюдо</th>
              <th class="px-3 py-3 font-medium text-right">Выручка</th>
              <th class="px-3 py-3 font-medium text-right">Кол-во</th>
              <th class="px-3 py-3 font-medium text-right">% от общей</th>
              <th class="px-3 py-3 font-medium text-right">Ср. цена</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="row in filteredProducts"
              :key="row.name"
              class="hover:bg-gray-50/80 cursor-pointer"
              :class="{ 'bg-primary/5': isSelected(row.name) }"
              @click="toggleProductName(row.name)"
            >
              <td class="px-3 py-2.5" @click.stop>
                <input
                  type="checkbox"
                  class="rounded border-gray-300 text-primary focus:ring-primary/30"
                  :checked="isSelected(row.name)"
                  @change="toggleProductName(row.name)"
                />
              </td>
              <td class="px-3 py-2.5 font-medium text-gray-800">{{ row.name }}</td>
              <td class="px-3 py-2.5 text-right tabular-nums">{{ formatMoney(row.sum) }}</td>
              <td class="px-3 py-2.5 text-right tabular-nums text-gray-600">{{ formatNum(row.qty) }}</td>
              <td class="px-3 py-2.5 text-right tabular-nums">
                <span class="inline-flex items-center gap-1">
                  <span
                    class="inline-block h-2 rounded-full bg-primary/60"
                    :style="{ width: `${Math.max(4, row.sharePercent || 0)}px`, maxWidth: '48px' }"
                  />
                  {{ formatShare(row.sharePercent) }}
                </span>
              </td>
              <td class="px-3 py-2.5 text-right tabular-nums text-gray-600">{{ formatMoney(row.avgPrice) }}</td>
            </tr>
          </tbody>
        </table>
        <p v-if="!filteredProducts.length" class="px-4 py-6 text-sm text-gray-500 text-center">
          Нет блюд по фильтру «{{ tableFilter }}».
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  embedded: { type: Boolean, default: false },
})

import { ref, computed, onMounted, watch } from 'vue'
import { api } from '@/api/client'
import { iikoSettingsComplete, getIikoCredentialsPayload } from '@/utils/iikoSettings'
import { pickIikoDepartmentForLocation } from '@/utils/iikoStoreMatch'
import { getCurrentUser } from '@/router'

const settingsReady = computed(() => iikoSettingsComplete())

function isoDateOnly(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function localDateOffset(days) {
  const d = new Date()
  d.setHours(12, 0, 0, 0)
  d.setDate(d.getDate() + days)
  return isoDateOnly(d)
}

function formatMoney(n) {
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 2 }).format(n)
}

function formatNum(n) {
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 3 }).format(n)
}

function formatShare(n) {
  if (!Number.isFinite(n)) return '—'
  return `${n.toFixed(1)}%`
}

const departments = ref([])
const departmentId = ref('')
const periodFrom = ref(isoDateOnly(new Date(Date.now() - 7 * 86400000)))
const periodTo = ref(isoDateOnly(new Date()))
const periodPreset = ref(null)

const products = ref([])
const summary = ref(null)
const selectedBlock = ref(null)
const selectedNames = ref([])
const tableFilter = ref('')

const loadingDepartments = ref(false)
const loading = ref(false)
const loadedOnce = ref(false)
const error = ref(null)

const nomenclatureQuery = ref('')
const nomenclatureHits = ref([])
const nomenclatureLoading = ref(false)
const nomenclatureSearched = ref(false)
const activeQuickGroup = ref(null)
const activeKeywords = ref([])

const quickGroups = [
  { id: 'waffles', label: 'Вафли', keyword: 'вафл' },
  { id: 'vibes', label: 'Вайбы', keyword: 'вайб' },
  { id: 'classic', label: 'Классика', keyword: 'классик' },
  { id: 'breezes', label: 'Бризы', keyword: 'бриз' },
  { id: 'ice-tea', label: 'Айс ти', keyword: 'айс ти' },
]

const formatPeriodLabel = computed(() => {
  const f = periodFrom.value
  const t = periodTo.value
  if (!f || !t) return '—'
  return `${f.split('-').reverse().join('.')} — ${t.split('-').reverse().join('.')}`
})

const filteredProducts = computed(() => {
  const q = tableFilter.value.trim().toLowerCase()
  if (!q) return products.value
  return products.value.filter((p) => String(p.name || '').toLowerCase().includes(q))
})

function syncPeriodPreset() {
  const today = localDateOffset(0)
  const yesterday = localDateOffset(-1)
  if (periodFrom.value === today && periodTo.value === today) {
    periodPreset.value = 'today'
  } else if (periodFrom.value === yesterday && periodTo.value === yesterday) {
    periodPreset.value = 'yesterday'
  } else {
    periodPreset.value = null
  }
}

function setPeriodPreset(preset) {
  const day = preset === 'yesterday' ? localDateOffset(-1) : localDateOffset(0)
  periodFrom.value = day
  periodTo.value = day
  periodPreset.value = preset
  loadReport()
}

watch([periodFrom, periodTo], syncPeriodPreset)

function isSelected(name) {
  return selectedNames.value.includes(name)
}

function toggleProductName(name) {
  activeQuickGroup.value = null
  activeKeywords.value = []
  const idx = selectedNames.value.indexOf(name)
  if (idx >= 0) {
    selectedNames.value = selectedNames.value.filter((n) => n !== name)
  } else {
    selectedNames.value = [...selectedNames.value, name]
  }
  if (loadedOnce.value) {
    loadReport()
  }
}

function clearSelection() {
  selectedNames.value = []
  activeQuickGroup.value = null
  activeKeywords.value = []
  if (loadedOnce.value) loadReport()
}

function applyQuickGroup(group) {
  activeQuickGroup.value = group.id
  activeKeywords.value = [group.keyword]
  selectedNames.value = []
  loadReport()
}

function clearQuickGroup() {
  activeQuickGroup.value = null
  activeKeywords.value = []
  selectedNames.value = []
  if (loadedOnce.value) loadReport()
}

function selectAllVisible() {
  const names = filteredProducts.value.map((p) => p.name)
  const set = new Set([...selectedNames.value, ...names])
  selectedNames.value = [...set]
  if (loadedOnce.value) loadReport()
}

async function loadDepartments() {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  loadingDepartments.value = true
  try {
    const data = await api.iikoSalesReports.departments({ iiko: creds })
    if (!data.ok) {
      error.value = data.message || 'Ошибка загрузки точек'
      return
    }
    departments.value = data.departments || []
    if (!departmentId.value) {
      const user = await getCurrentUser().catch(() => null)
      if (user?.locationName) {
        const preferred = pickIikoDepartmentForLocation(
          departments.value,
          user.locationName,
          user.locationIikoName,
        )
        if (preferred && departments.value.some((d) => d.id === preferred)) {
          departmentId.value = preferred
        }
      }
    }
  } catch (e) {
    error.value = e.data?.message || e.message
  } finally {
    loadingDepartments.value = false
  }
}

async function loadReport() {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  loading.value = true
  error.value = null
  try {
    const payload = {
      iiko: creds,
      from: periodFrom.value,
      to: periodTo.value,
      departmentId: departmentId.value || undefined,
    }
    if (activeKeywords.value.length) {
      payload.productKeywords = activeKeywords.value
    } else if (selectedNames.value.length) {
      payload.productNames = selectedNames.value
    }
    const data = await api.iikoSalesReports.report(payload)
    loadedOnce.value = true
    if (!data.ok) {
      error.value = data.message || 'Ошибка'
      products.value = []
      summary.value = null
      selectedBlock.value = null
      return
    }
    products.value = data.products || []
    summary.value = data.summary || null
    selectedBlock.value = data.selected || null
    if (activeKeywords.value.length && data.matchedProductNames?.length) {
      selectedNames.value = data.matchedProductNames
    }
  } catch (e) {
    error.value = e.data?.message || e.message
    products.value = []
  } finally {
    loading.value = false
  }
}

async function searchNomenclature() {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  nomenclatureLoading.value = true
  nomenclatureSearched.value = true
  try {
    const data = await api.iikoSalesReports.nomenclature({
      iiko: creds,
      query: nomenclatureQuery.value.trim(),
    })
    nomenclatureHits.value = data.products || []
  } catch {
    nomenclatureHits.value = []
  } finally {
    nomenclatureLoading.value = false
  }
}

onMounted(() => {
  syncPeriodPreset()
  if (settingsReady.value) {
    loadDepartments().then(() => loadReport())
  }
})
</script>
