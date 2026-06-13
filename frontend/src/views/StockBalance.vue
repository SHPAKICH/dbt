<template>
  <div class="max-w-7xl mx-auto px-4 pb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Остатки на складе</h1>
    <p class="text-sm text-gray-500 mb-6">
      Выберите склад и товар (поиск или клик по строке) — откроются все акты, как в iikoChain:
      реализация, списание, инвентаризация, приход.
    </p>

    <div v-if="!settingsReady" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 max-w-xl text-center">
      <h2 class="text-xl font-semibold text-gray-800 mb-2">Сначала настройте подключение к iiko</h2>
      <p class="text-sm text-gray-500 mb-4">URL сервера, логин и пароль — в разделе «Настройки».</p>
      <router-link to="/settings" class="inline-flex px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">
        Перейти к настройкам
      </router-link>
    </div>

    <template v-else>
      <div class="flex flex-wrap items-end gap-4 mb-4">
        <div class="min-w-[240px]">
          <label class="block text-xs font-medium text-gray-600 mb-1">Точка / склад</label>
          <select
            v-model="storeId"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            :disabled="loadingStores || stores.length === 0"
          >
            <option v-if="stores.length === 0" value="">
              {{ loadingStores ? 'Загрузка…' : 'Складов не найдено' }}
            </option>
            <option v-for="s in stores" :key="s.id" :value="s.id">
              {{ s.name }}{{ s.code ? ` · ${s.code}` : '' }}
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <div class="flex flex-wrap items-end gap-2">
            <label class="text-xs text-gray-500">Период с</label>
            <input v-model="docPeriodFrom" type="date" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" />
            <label class="text-xs text-gray-500">по</label>
            <input v-model="docPeriodTo" type="date" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" />
          </div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="preset in periodPresets"
              :key="preset.id"
              type="button"
              class="period-chip"
              :class="{ active: periodPreset === preset.id }"
              :disabled="loadingRows"
              @click="setPeriodPreset(preset.id)"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>
        <button
          type="button"
          class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium hover:bg-gray-50 disabled:opacity-50"
          :disabled="loadingRows || !storeId"
          @click="loadRows"
        >
          {{ loadingRows ? 'Обновление…' : 'Обновить остатки' }}
        </button>
        <span v-if="generatedAt" class="text-xs text-gray-500 ml-auto">
          Остатки: {{ new Date(generatedAt).toLocaleString('ru-RU') }}
        </span>
      </div>

      <p v-if="movementWarning" class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-4">
        Расход и приход за период: {{ movementWarning }}
      </p>

      <div class="relative mb-4 max-w-xl">
        <label class="block text-xs font-medium text-gray-600 mb-1">Поиск товара</label>
        <div class="relative">
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="search"
            autocomplete="off"
            placeholder="Начните вводить название или артикул…"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-9 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
            @focus="onSearchFocus"
            @blur="onSearchBlur"
            @input="onSearchInput"
            @keydown.escape="clearSearch"
            @keydown.down.prevent="moveSuggestion(1)"
            @keydown.up.prevent="moveSuggestion(-1)"
            @keydown.enter.prevent="pickHighlightedSuggestion"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg leading-none"
            title="Очистить поиск"
            @mousedown.prevent="clearSearch"
          >
            ×
          </button>
        </div>
        <ul
          v-if="showSuggestions"
          class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-72 overflow-y-auto"
        >
          <li v-if="searchQuery.trim() && suggestions.length === 0" class="px-4 py-3 text-sm text-gray-500">
            Ничего не найдено
          </li>
          <li v-for="(item, idx) in suggestions" :key="`${item.storeId}::${item.productId}`">
            <button
              type="button"
              class="w-full text-left px-4 py-2.5 border-b border-gray-50 last:border-0 transition-colors"
              :class="idx === highlightedIndex ? 'bg-primary/10' : 'hover:bg-primary/5'"
              @mousedown.prevent="pickSuggestion(item)"
            >
              <div class="font-medium text-gray-800">{{ item.productName }}</div>
              <div class="text-xs text-gray-500 mt-0.5 flex gap-2">
                <span v-if="item.productNum || item.productCode">{{ item.productNum || item.productCode }}</span>
                <span>Остаток: {{ formatNum(item.amount) }} {{ item.unit || '' }}</span>
              </div>
            </button>
          </li>
        </ul>
        <p v-if="searchQuery.trim() && rows.length" class="text-xs text-gray-500 mt-1.5">
          В таблице: {{ tableRows.length }} из {{ rows.length }}
        </p>
      </div>

      <div v-if="error" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 mb-4">
        {{ error }}
      </div>

      <div
        v-if="selectedProduct"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-6 overflow-hidden"
      >
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex flex-wrap items-center gap-3">
          <div class="flex-1 min-w-[200px]">
            <h2 class="font-semibold text-gray-800">{{ selectedProduct.productName }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">
              Остаток: <span class="font-medium text-gray-700">{{ formatNum(selectedProduct.amount) }}</span>
              {{ selectedProduct.unit || '' }}
              <span v-if="selectedProduct.productNum || selectedProduct.productCode" class="ml-2">
                · {{ selectedProduct.productNum || selectedProduct.productCode }}
              </span>
            </p>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <label class="text-gray-500 text-xs">Период с</label>
            <input v-model="docPeriodFrom" type="date" class="rounded border border-gray-300 px-2 py-1 text-sm" />
            <label class="text-gray-500 text-xs">по</label>
            <input v-model="docPeriodTo" type="date" class="rounded border border-gray-300 px-2 py-1 text-sm" />
            <button
              type="button"
              class="px-3 py-1 rounded-lg border border-gray-300 text-xs hover:bg-white disabled:opacity-50"
              :disabled="loadingDocs"
              @click="loadProductDocuments"
            >
              {{ loadingDocs ? '…' : 'Обновить' }}
            </button>
          </div>
          <button type="button" class="text-gray-400 hover:text-gray-600 text-xl leading-none" title="Закрыть" @click="clearSelection">
            ×
          </button>
        </div>

        <div v-if="loadingDocs" class="px-4 py-10 text-center text-gray-500 text-sm">
          Загрузка документов из iiko…
        </div>
        <div v-else-if="docError" class="px-4 py-4 text-sm text-rose-600">
          {{ docError }}
        </div>
        <p v-if="docWarnings.length" class="px-4 py-2 text-xs text-amber-800 bg-amber-50 border-b border-amber-100">
          {{ docWarnings.join(' ') }}
        </p>
        <div
          v-else-if="!loadingDocs && !docError && docGroups.every((g) => !g.entries?.length)"
          class="px-4 py-8 text-center text-sm text-gray-500"
        >
          За выбранный период актов по этому товару не найдено. Расширьте период или проверьте права учётной записи
          на экспорт документов в iiko.
        </div>
        <div v-else-if="!loadingDocs && !docError" class="divide-y divide-gray-100">
          <section v-for="group in docGroups" :key="group.id" class="px-4 py-3">
            <div class="flex items-baseline justify-between mb-2">
              <h3 class="text-sm font-semibold text-gray-800">{{ group.title }}</h3>
              <div class="text-xs tabular-nums">
                <span v-if="group.totalOut > 0" class="text-rose-600 mr-2">расх. {{ formatNum(group.totalOut) }}</span>
                <span v-if="group.totalIn > 0" class="text-emerald-600">прих. {{ formatNum(group.totalIn) }}</span>
                <span v-if="group.totalOut === 0 && group.totalIn === 0" class="text-gray-400">нет движений</span>
              </div>
            </div>
            <div v-if="group.entries.length === 0" class="text-xs text-gray-400 py-2">
              За период документов нет
            </div>
            <div v-else class="overflow-x-auto rounded-lg border border-gray-100">
              <table class="min-w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase">
                  <tr>
                    <th class="text-left px-2 py-1.5 font-medium">Дата</th>
                    <th class="text-left px-2 py-1.5 font-medium">Документ</th>
                    <th class="text-left px-2 py-1.5 font-medium">Корреспонденция</th>
                    <th class="text-right px-2 py-1.5 font-medium text-rose-600">Расход</th>
                    <th class="text-right px-2 py-1.5 font-medium text-emerald-600">Приход</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(entry, idx) in group.entries"
                    :key="`${group.id}-${idx}`"
                    class="border-t border-gray-50"
                  >
                    <td class="px-2 py-1.5 whitespace-nowrap">{{ formatDocDate(entry.date) }}</td>
                    <td class="px-2 py-1.5">
                      <span class="font-medium text-gray-800">{{ entry.documentLabel || entry.documentNumber || '—' }}</span>
                    </td>
                    <td class="px-2 py-1.5 text-gray-600 max-w-[200px] truncate" :title="entry.correspondence || ''">
                      {{ entry.correspondence || '—' }}
                    </td>
                    <td class="px-2 py-1.5 text-right tabular-nums text-rose-700">
                      {{ entry.direction === 'out' ? formatNum(entry.amount) : '—' }}
                    </td>
                    <td class="px-2 py-1.5 text-right tabular-nums text-emerald-700">
                      {{ entry.direction === 'in' ? formatNum(entry.amount) : '—' }}
                    </td>
                  </tr>
                </tbody>
                <tfoot v-if="group.totalOut > 0 || group.totalIn > 0" class="bg-gray-50 font-semibold">
                  <tr>
                    <td colspan="3" class="px-2 py-1.5 text-gray-700">Итого</td>
                    <td class="px-2 py-1.5 text-right text-rose-700 tabular-nums">{{ formatNum(group.totalOut) }}</td>
                    <td class="px-2 py-1.5 text-right text-emerald-700 tabular-nums">{{ formatNum(group.totalIn) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </section>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="text-xs text-gray-500 uppercase">Позиций на складе</div>
          <div class="text-2xl font-semibold mt-1">{{ rows.length }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="text-xs text-gray-500 uppercase">К заказу</div>
          <div class="text-2xl font-semibold mt-1 text-amber-700">{{ summary.toOrder }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="text-xs text-gray-500 uppercase">Критично</div>
          <div class="text-2xl font-semibold mt-1 text-rose-700">{{ summary.critical }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="text-xs text-gray-500 uppercase">Объём заказа</div>
          <div class="text-2xl font-semibold mt-1 text-primary">{{ formatNum(summary.totalUnits) }}</div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2 mb-3">
        <button type="button" class="filter-chip" :class="{ active: tableFilter === 'all' }" @click="tableFilter = 'all'">Все</button>
        <button type="button" class="filter-chip" :class="{ active: tableFilter === 'to-order' }" @click="tableFilter = 'to-order'">К заказу</button>
        <button type="button" class="filter-chip" :class="{ active: tableFilter === 'critical' }" @click="tableFilter = 'critical'">Критичные</button>
      </div>

      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th class="text-left px-3 py-2">Артикул</th>
                <th class="text-left px-3 py-2">Наименование</th>
                <th class="text-left px-3 py-2 w-14">Ед.</th>
                <th class="text-right px-3 py-2">Остаток</th>
                <th
                  class="text-right px-3 py-2 text-rose-600"
                  title="Акты реализации и списания за выбранный период"
                >
                  Расход
                </th>
                <th
                  class="text-right px-3 py-2 text-emerald-600"
                  title="Приходные накладные за выбранный период"
                >
                  Приход
                </th>
                <th class="text-right px-3 py-2">Резерв</th>
                <th class="text-right px-3 py-2">К заказу</th>
                <th class="text-left px-3 py-2">Статус</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loadingRows && rows.length === 0">
                <td colspan="9" class="px-3 py-8 text-center text-gray-500">Загрузка…</td>
              </tr>
              <tr v-else-if="tableRows.length === 0">
                <td colspan="9" class="px-3 py-8 text-center text-gray-500">
                  {{ searchQuery.trim() ? `Ничего не найдено по запросу «${searchQuery.trim()}»` : 'Нет позиций' }}
                </td>
              </tr>
              <tr
                v-for="row in tableRows"
                :key="`${row.storeId}::${row.productId}`"
                class="border-t border-gray-100 hover:bg-gray-50/60 cursor-pointer"
                :class="{ 'bg-primary/5': selectedProduct?.productId === row.productId }"
                @click="selectProduct(row)"
              >
                <td class="px-3 py-2 text-gray-500">{{ row.productNum || row.productCode || '—' }}</td>
                <td class="px-3 py-2 font-medium text-gray-800">{{ row.productName }}</td>
                <td class="px-3 py-2 text-gray-600">{{ row.unit || '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums font-medium">{{ formatNum(row.amount) }}</td>
                <td class="px-3 py-2 text-right tabular-nums text-rose-700">
                  <span v-if="row.periodOut > 0">{{ formatNum(row.periodOut) }}</span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-3 py-2 text-right tabular-nums text-emerald-700">
                  <span v-if="row.periodIn > 0">{{ formatNum(row.periodIn) }}</span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-3 py-2 text-right tabular-nums" @click.stop>
                  <input
                    v-if="editingKey === `${row.storeId}::${row.productId}`"
                    ref="reserveInputRef"
                    v-model="editReserveValue"
                    type="text"
                    class="w-20 ml-auto text-right rounded border border-primary/40 px-2 py-1 text-sm"
                    @blur="commitReserve(row)"
                    @keydown.enter="commitReserve(row)"
                  />
                  <button
                    v-else
                    type="button"
                    class="hover:text-primary decoration-dotted underline-offset-2 hover:underline"
                    @click.stop="startEdit(row)"
                  >
                    {{ formatNum(row.reserve) }}
                  </button>
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                  <span v-if="row.toOrder > 0" class="text-primary font-medium">{{ formatNum(row.toOrder) }}</span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-3 py-2">
                  <span class="px-2 py-0.5 rounded-full text-xs border" :class="statusBadge[row.status]">
                    {{ statusText[row.status] }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { api } from '@/api/client'
import {
  iikoSettingsComplete,
  getIikoCredentialsPayload,
  saveReserve,
  reservesForStore,
  loadIikoSettings,
} from '@/utils/iikoSettings'
import { pickIikoStoreForLocation } from '@/utils/iikoStoreMatch'
import { getCurrentUser } from '@/router'

const settingsReady = computed(() => iikoSettingsComplete())

function isoDateOnly(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function localDateAtNoon(daysOffset = 0) {
  const d = new Date()
  d.setHours(12, 0, 0, 0)
  d.setDate(d.getDate() + daysOffset)
  return d
}

function monthStartIso() {
  const d = localDateAtNoon()
  return isoDateOnly(new Date(d.getFullYear(), d.getMonth(), 1, 12, 0, 0))
}

function todayIso() {
  return isoDateOnly(localDateAtNoon())
}

const periodPresets = [
  { id: 'today', label: 'Сегодня' },
  { id: 'yesterday', label: 'Вчера' },
  { id: 'week', label: 'Неделя' },
  { id: 'month', label: 'Месяц' },
]

function formatNum(n) {
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 3 }).format(n)
}

function formatDocDate(s) {
  if (!s) return '—'
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return String(s).slice(0, 10)
  return d.toLocaleDateString('ru-RU')
}

const statusText = {
  ok: 'В норме',
  low: 'Низкий',
  critical: 'Критично',
  'no-reserve': 'Нет резерва',
}
const statusBadge = {
  ok: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  low: 'bg-amber-50 text-amber-700 border-amber-200',
  critical: 'bg-rose-50 text-rose-700 border-rose-200',
  'no-reserve': 'bg-gray-100 text-gray-600 border-gray-200',
}

const stores = ref([])
const storeId = ref('')
const rows = ref([])
const loadingStores = ref(false)
const loadingRows = ref(false)
const error = ref(null)
const generatedAt = ref(null)
const tableFilter = ref('all')

const searchQuery = ref('')
const searchFocused = ref(false)
const highlightedIndex = ref(-1)
const searchInputRef = ref(null)
const selectedProduct = ref(null)
const docGroups = ref([])
const loadingDocs = ref(false)
const docError = ref(null)
const docPeriodFrom = ref(monthStartIso())
const docPeriodTo = ref(todayIso())
const periodPreset = ref('month')
const docWarnings = ref([])
const movementWarning = ref(null)

const editingKey = ref('')
const editReserveValue = ref('')
const reserveInputRef = ref(null)

function matchesSearch(row, query) {
  const hay = `${row.productName} ${row.productCode || ''} ${row.productNum || ''}`.toLowerCase()
  return hay.includes(query)
}

const searchTokens = computed(() => searchQuery.value.trim().toLowerCase())

const suggestions = computed(() => {
  const q = searchTokens.value
  if (q.length < 1) return []
  return rows.value.filter((r) => matchesSearch(r, q)).slice(0, 12)
})

const showSuggestions = computed(() => searchFocused.value && searchTokens.value.length > 0)

const tableRows = computed(() => rows.value.filter((r) => {
  if (tableFilter.value === 'to-order' && r.toOrder <= 0) return false
  if (tableFilter.value === 'critical' && r.status !== 'critical') return false
  if (searchTokens.value && !matchesSearch(r, searchTokens.value)) return false
  return true
}))

const summary = computed(() => ({
  toOrder: rows.value.filter((r) => r.toOrder > 0).length,
  critical: rows.value.filter((r) => r.status === 'critical').length,
  totalUnits: rows.value.reduce((s, r) => s + (r.toOrder || 0), 0),
}))

function onSearchFocus() {
  searchFocused.value = true
  highlightedIndex.value = suggestions.value.length ? 0 : -1
}

function onSearchInput() {
  highlightedIndex.value = suggestions.value.length ? 0 : -1
  clearSelection()
}

function onSearchBlur() {
  setTimeout(() => {
    searchFocused.value = false
    highlightedIndex.value = -1
  }, 150)
}

function syncPeriodPreset() {
  const today = todayIso()
  const yesterday = isoDateOnly(localDateAtNoon(-1))
  const weekStart = isoDateOnly(localDateAtNoon(-6))
  const monthStart = monthStartIso()

  if (docPeriodFrom.value === today && docPeriodTo.value === today) {
    periodPreset.value = 'today'
  } else if (docPeriodFrom.value === yesterday && docPeriodTo.value === yesterday) {
    periodPreset.value = 'yesterday'
  } else if (docPeriodFrom.value === weekStart && docPeriodTo.value === today) {
    periodPreset.value = 'week'
  } else if (docPeriodFrom.value === monthStart && docPeriodTo.value === today) {
    periodPreset.value = 'month'
  } else {
    periodPreset.value = null
  }
}

function setPeriodPreset(preset) {
  const today = todayIso()
  if (preset === 'today') {
    docPeriodFrom.value = today
    docPeriodTo.value = today
  } else if (preset === 'yesterday') {
    const day = isoDateOnly(localDateAtNoon(-1))
    docPeriodFrom.value = day
    docPeriodTo.value = day
  } else if (preset === 'week') {
    docPeriodFrom.value = isoDateOnly(localDateAtNoon(-6))
    docPeriodTo.value = today
  } else if (preset === 'month') {
    docPeriodFrom.value = monthStartIso()
    docPeriodTo.value = today
  }
  periodPreset.value = preset
  if (storeId.value) loadRows()
}

function clearSearch() {
  searchQuery.value = ''
  highlightedIndex.value = -1
  searchFocused.value = false
  searchInputRef.value?.blur()
}

function moveSuggestion(delta) {
  if (!suggestions.value.length) return
  if (highlightedIndex.value < 0) {
    highlightedIndex.value = delta > 0 ? 0 : suggestions.value.length - 1
    return
  }
  highlightedIndex.value = (highlightedIndex.value + delta + suggestions.value.length) % suggestions.value.length
}

function pickHighlightedSuggestion() {
  if (highlightedIndex.value >= 0 && suggestions.value[highlightedIndex.value]) {
    pickSuggestion(suggestions.value[highlightedIndex.value])
    return
  }
  if (suggestions.value.length === 1) {
    pickSuggestion(suggestions.value[0])
  }
}

async function pickSuggestion(item) {
  searchQuery.value = item.productName
  searchFocused.value = false
  highlightedIndex.value = -1
  await selectProduct(item)
}

function clearSelection() {
  selectedProduct.value = null
  docGroups.value = []
  docError.value = null
  docWarnings.value = []
}

async function selectProduct(row) {
  selectedProduct.value = row
  searchQuery.value = row.productName
  searchFocused.value = false
  highlightedIndex.value = -1
  await loadProductDocuments()
}

async function loadStores() {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  loadingStores.value = true
  error.value = null
  try {
    const data = await api.iikoStock.stores({ iiko: creds })
    if (!data.ok) {
      error.value = data.message || 'Ошибка'
      return
    }
    stores.value = data.stores || []
    const saved = loadIikoSettings()
    if (stores.value.length && !storeId.value) {
      let preferredId = null
      const user = await getCurrentUser().catch(() => null)
      if (user?.locationId && user?.locationName) {
        preferredId = pickIikoStoreForLocation(stores.value, user.locationName, user.locationIikoName)
      }
      if (preferredId && stores.value.some((s) => s.id === preferredId)) {
        storeId.value = preferredId
      } else if (saved.defaultStoreId && stores.value.some((s) => s.id === saved.defaultStoreId)) {
        storeId.value = saved.defaultStoreId
      } else {
        storeId.value = stores.value[0].id
      }
    }
  } catch (e) {
    error.value = e.data?.message || e.message
  } finally {
    loadingStores.value = false
  }
}

async function loadRows() {
  if (!storeId.value) return
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  loadingRows.value = true
  error.value = null
  movementWarning.value = null
  clearSelection()
  clearSearch()
  try {
    const data = await api.iikoStock.balance({
      iiko: creds,
      storeId: storeId.value,
      reserves: reservesForStore(storeId.value),
      from: docPeriodFrom.value,
      to: docPeriodTo.value,
    })
    if (!data.ok) {
      error.value = data.message || 'Ошибка'
      rows.value = []
      return
    }
    rows.value = data.rows || []
    generatedAt.value = data.generatedAt
    movementWarning.value = data.movementWarning || null
  } catch (e) {
    error.value = e.data?.message || e.message
    rows.value = []
  } finally {
    loadingRows.value = false
  }
}

async function loadProductDocuments() {
  if (!selectedProduct.value || !storeId.value) return
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  loadingDocs.value = true
  docError.value = null
  docWarnings.value = []
  try {
    const data = await api.iikoStock.productDocuments({
      iiko: creds,
      storeId: storeId.value,
      productId: selectedProduct.value.productId,
      productNum: selectedProduct.value.productNum || undefined,
      productCode: selectedProduct.value.productCode || undefined,
      from: docPeriodFrom.value,
      to: docPeriodTo.value,
    })
    if (!data.ok) {
      docError.value = data.message || 'Ошибка загрузки документов'
      docGroups.value = []
      return
    }
    docGroups.value = data.groups || []
    docWarnings.value = data.warnings || []
  } catch (e) {
    docError.value = e.data?.message || e.message
    docGroups.value = []
  } finally {
    loadingDocs.value = false
  }
}

function startEdit(row) {
  editingKey.value = `${row.storeId}::${row.productId}`
  editReserveValue.value = String(row.reserve)
  nextTick(() => reserveInputRef.value?.focus())
}

function commitReserve(row) {
  const n = parseFloat(editReserveValue.value.replace(',', '.'))
  editingKey.value = ''
  if (!Number.isFinite(n) || n === row.reserve) return
  saveReserve({
    storeId: row.storeId,
    productId: row.productId,
    reserve: n,
    productName: row.productName,
    productCode: row.productCode,
    productNum: row.productNum,
    unit: row.unit,
  })
  const shortage = Math.max(0, n - row.amount)
  const step = row.multiplicity ?? 0
  const toOrder = step > 0 ? Math.ceil(shortage / step) * step : shortage
  let status = 'ok'
  if (n <= 0) status = 'no-reserve'
  else if (row.amount <= 0) status = 'critical'
  else if (row.amount / n < 0.5) status = 'critical'
  else if (row.amount / n < 1) status = 'low'
  const idx = rows.value.findIndex((r) => r.productId === row.productId && r.storeId === row.storeId)
  if (idx >= 0) {
    const updated = { ...rows.value[idx], reserve: n, shortage, toOrder, status }
    rows.value[idx] = updated
    if (selectedProduct.value?.productId === row.productId) {
      selectedProduct.value = updated
    }
  }
}

onMounted(() => {
  syncPeriodPreset()
  if (settingsReady.value) loadStores()
})

watch([docPeriodFrom, docPeriodTo], syncPeriodPreset)

watch(storeId, (id) => {
  if (id) loadRows()
})
</script>

<style scoped>
.filter-chip {
  @apply px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 bg-white text-gray-600 hover:border-primary/40;
}
.filter-chip.active {
  @apply bg-primary text-white border-primary;
}
.period-chip {
  @apply px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-50;
}
.period-chip.active {
  @apply border-primary bg-primary/10 text-primary;
}
</style>
