<template>
  <div class="max-w-6xl mx-auto px-4 py-6 pb-12">
    <div class="flex flex-wrap items-center gap-4 mb-4">
      <router-link to="/documentation" class="text-gray-500 hover:text-gray-700">← Документация</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Списание</h1>
    </div>

    <p class="text-sm text-gray-600 mb-6">
      Учёт списания продуктов по категориям. Продукт подставляется из iiko, сумма считается как вес × цена за единицу.
    </p>

    <div v-if="!settingsReady" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 max-w-xl text-center">
      <h2 class="text-xl font-semibold text-gray-800 mb-2">Сначала настройте подключение к iiko</h2>
      <p class="text-sm text-gray-500 mb-4">URL сервера, логин и пароль — в разделе «Настройки».</p>
      <router-link to="/settings" class="inline-flex px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">
        Перейти к настройкам
      </router-link>
    </div>

    <template v-else>
      <p v-if="noAccess" class="p-4 rounded-lg bg-amber-50 text-amber-800">У вас нет доступа ни к одной точке.</p>

      <template v-else>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
          <div class="flex flex-wrap items-end gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
              <select
                v-model.number="locationId"
                class="min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                @change="loadEntries"
              >
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Дата</label>
              <input
                v-model="entryDate"
                type="date"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                @change="loadEntries"
              />
            </div>
            <button
              type="button"
              class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
              :disabled="loading"
              @click="loadEntries"
            >
              {{ loading ? 'Загрузка…' : 'Обновить' }}
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50"
              :disabled="saving"
              @click="saveAll"
            >
              {{ saving ? 'Сохранение…' : 'Сохранить' }}
            </button>
          </div>
          <p v-if="saveMessage" class="mt-3 text-sm text-green-700">{{ saveMessage }}</p>
          <p v-if="error" class="mt-3 text-sm text-rose-700">{{ error }}</p>
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/20 bg-primary/5 px-4 py-3">
          <span class="text-sm font-medium text-gray-700">Итого списание за {{ formatDateLabel(entryDate) }}</span>
          <span class="text-xl font-bold text-primary tabular-nums">{{ formatMoney(grandTotal) }}</span>
        </div>

        <div
          v-for="category in WRITE_OFF_CATEGORIES"
          :key="category.id"
          class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6"
        >
          <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2 bg-gray-50">
            <h2 class="text-base font-semibold text-gray-800">{{ category.label }}</h2>
            <span class="text-sm font-medium text-primary tabular-nums">
              {{ formatMoney(categoryTotal(category.id)) }}
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-white border-b border-gray-100">
                <tr class="text-left text-xs text-gray-500">
                  <th class="px-3 py-2 font-medium min-w-[280px]">Продукт</th>
                  <th class="px-3 py-2 font-medium w-28">Вес</th>
                  <th class="px-3 py-2 font-medium w-32">Цена/ед.</th>
                  <th class="px-3 py-2 font-medium w-32 text-right">Сумма</th>
                  <th class="px-3 py-2 w-10" />
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="row in rowsByCategory[category.id]" :key="row.localId">
                  <td class="px-3 py-2 align-top">
                    <div class="relative">
                      <input
                        v-model="row.productName"
                        type="text"
                        placeholder="Начните вводить название из iiko…"
                        class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm"
                        @input="onProductInput(row)"
                        @focus="onProductInput(row)"
                        @blur="() => hideSuggestions(row)"
                      />
                      <ul
                        v-if="row.showSuggestions && row.suggestions.length"
                        class="absolute z-20 left-0 right-0 mt-1 max-h-44 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg text-sm"
                      >
                        <li
                          v-for="product in row.suggestions"
                          :key="product.id"
                          class="px-3 py-2 hover:bg-primary/5 cursor-pointer border-b border-gray-50 last:border-0"
                          @mousedown.prevent="selectProduct(row, product)"
                        >
                          <div class="font-medium text-gray-800 truncate">{{ product.name }}</div>
                          <div v-if="product.mainUnit || product.unitCost" class="text-[11px] text-gray-500 mt-0.5">
                            <span v-if="product.mainUnit">{{ product.mainUnit }}</span>
                            <span v-if="product.unitCost"> · {{ formatMoney(product.unitCost) }}/ед.</span>
                          </div>
                        </li>
                      </ul>
                      <p v-if="row.searching" class="text-[11px] text-gray-400 mt-1">Поиск в iiko…</p>
                    </div>
                  </td>
                  <td class="px-3 py-2 align-top">
                    <div class="flex items-center gap-1">
                      <input
                        v-model.number="row.weight"
                        type="text"
                        inputmode="decimal"
                        placeholder="0"
                        class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm text-right tabular-nums"
                        @input="recalcRow(row)"
                      />
                      <span v-if="row.unit" class="text-[11px] text-gray-400 shrink-0">{{ row.unit }}</span>
                    </div>
                  </td>
                  <td class="px-3 py-2 align-top">
                    <input
                      v-model.number="row.unitCost"
                      type="text"
                      inputmode="decimal"
                      placeholder="0"
                      class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm text-right tabular-nums"
                      @input="recalcRow(row)"
                    />
                  </td>
                  <td class="px-3 py-2 align-top text-right font-semibold text-gray-800 tabular-nums">
                    {{ formatMoney(rowAmount(row)) }}
                  </td>
                  <td class="px-3 py-2 align-top text-center">
                    <button
                      type="button"
                      class="text-gray-400 hover:text-rose-600 text-lg leading-none"
                      title="Удалить строку"
                      @click="removeRow(category.id, row.localId)"
                    >
                      ×
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="px-4 py-3 border-t border-gray-100">
            <button
              type="button"
              class="text-sm text-primary font-medium hover:underline"
              @click="addRow(category.id)"
            >
              + Добавить продукт
            </button>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { api } from '@/api/client'
import { WRITE_OFF_CATEGORIES } from '@/config/writeOffCategories'
import { getIikoCredentialsPayload, iikoSettingsComplete } from '@/utils/iikoSettings'

const settingsReady = computed(() => iikoSettingsComplete())

const locations = ref([])
const locationId = ref(null)
const entryDate = ref(isoToday())
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const saveMessage = ref('')
const noAccess = ref(false)
const grandTotal = ref(0)
const totals = ref({})

const rowsByCategory = reactive(
  Object.fromEntries(WRITE_OFF_CATEGORIES.map((c) => [c.id, []])),
)

const searchTimers = new Map()

function isoToday() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function newLocalId() {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
}

function emptyRow(categoryId) {
  return {
    localId: newLocalId(),
    category: categoryId,
    productId: null,
    productName: '',
    weight: '',
    unit: '',
    unitCost: '',
    suggestions: [],
    showSuggestions: false,
    searching: false,
  }
}

function ensureCategoryRows(categoryId, items = []) {
  if (items.length) {
    rowsByCategory[categoryId] = items.map((item) => ({
      localId: newLocalId(),
      category: categoryId,
      productId: item.productId || null,
      productName: item.productName || '',
      weight: item.weight ?? '',
      unit: item.unit || '',
      unitCost: item.unitCost ?? '',
      suggestions: [],
      showSuggestions: false,
      searching: false,
    }))
  } else {
    rowsByCategory[categoryId] = [emptyRow(categoryId)]
  }
}

function formatMoney(value) {
  return `${Number(value || 0).toLocaleString('ru-RU', { maximumFractionDigits: 2 })} ₽`
}

function formatDateLabel(date) {
  if (!date) return '—'
  const parts = date.split('-')
  if (parts.length === 3) return `${parts[2]}.${parts[1]}.${parts[0]}`
  return date
}

function rowAmount(row) {
  const weight = Number(row.weight) || 0
  const unitCost = Number(row.unitCost) || 0
  return Math.round(weight * unitCost * 100) / 100
}

function recalcRow(row) {
  row.amount = rowAmount(row)
}

function categoryTotal(categoryId) {
  return (rowsByCategory[categoryId] || []).reduce((sum, row) => sum + rowAmount(row), 0)
}

function addRow(categoryId) {
  rowsByCategory[categoryId].push(emptyRow(categoryId))
}

function removeRow(categoryId, localId) {
  const rows = rowsByCategory[categoryId]
  const next = rows.filter((r) => r.localId !== localId)
  rowsByCategory[categoryId] = next.length ? next : [emptyRow(categoryId)]
}

async function searchProducts(row) {
  const query = String(row.productName || '').trim()
  if (query.length < 2) {
    row.suggestions = []
    row.showSuggestions = false
    return
  }

  const creds = getIikoCredentialsPayload()
  if (!creds) return

  row.searching = true
  try {
    const data = await api.iikoSalesReports.nomenclature({ iiko: creds, query })
    row.suggestions = (data.products || []).slice(0, 12)
    row.showSuggestions = row.suggestions.length > 0
  } catch {
    row.suggestions = []
    row.showSuggestions = false
  } finally {
    row.searching = false
  }
}

function onProductInput(row) {
  row.productId = null
  if (searchTimers.has(row.localId)) {
    clearTimeout(searchTimers.get(row.localId))
  }
  searchTimers.set(
    row.localId,
    setTimeout(() => searchProducts(row), 300),
  )
}

function hideSuggestions(row) {
  setTimeout(() => {
    row.showSuggestions = false
  }, 150)
}

function selectProduct(row, product) {
  row.productId = product.id
  row.productName = product.name
  row.unit = product.mainUnit || row.unit || ''
  if (product.unitCost != null && Number(product.unitCost) > 0) {
    row.unitCost = Number(product.unitCost)
  }
  row.suggestions = []
  row.showSuggestions = false
  recalcRow(row)
}

function collectItems() {
  const items = []
  for (const category of WRITE_OFF_CATEGORIES) {
    for (const row of rowsByCategory[category.id] || []) {
      const name = String(row.productName || '').trim()
      if (!name) continue
      items.push({
        category: category.id,
        productId: row.productId,
        productName: name,
        weight: Number(row.weight) || 0,
        unit: row.unit || null,
        unitCost: Number(row.unitCost) || 0,
      })
    }
  }
  return items
}

async function loadLocations() {
  const res = await api.writeOff.locations()
  locations.value = res.locations || []
  noAccess.value = locations.value.length === 0
  if (locations.value.length && !locationId.value) {
    locationId.value = locations.value[0].id
  }
}

async function loadEntries() {
  if (!locationId.value) return
  loading.value = true
  error.value = ''
  saveMessage.value = ''
  try {
    const res = await api.writeOff.entries({
      location_id: locationId.value,
      date: entryDate.value,
    })
    totals.value = res.totals || {}
    grandTotal.value = res.grandTotal || 0

    const grouped = Object.fromEntries(WRITE_OFF_CATEGORIES.map((c) => [c.id, []]))
    for (const item of res.items || []) {
      const cat = item.category
      if (!grouped[cat]) grouped[cat] = []
      grouped[cat].push(item)
    }
    for (const category of WRITE_OFF_CATEGORIES) {
      ensureCategoryRows(category.id, grouped[category.id] || [])
    }
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

async function saveAll() {
  if (!locationId.value) return
  saving.value = true
  error.value = ''
  saveMessage.value = ''
  try {
    const res = await api.writeOff.save({
      locationId: locationId.value,
      date: entryDate.value,
      items: collectItems(),
    })
    totals.value = res.totals || {}
    grandTotal.value = res.grandTotal || 0
    saveMessage.value = res.message || 'Сохранено'

    const grouped = Object.fromEntries(WRITE_OFF_CATEGORIES.map((c) => [c.id, []]))
    for (const item of res.items || []) {
      if (!grouped[item.category]) grouped[item.category] = []
      grouped[item.category].push(item)
    }
    for (const category of WRITE_OFF_CATEGORIES) {
      ensureCategoryRows(category.id, grouped[category.id] || [])
    }
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  for (const category of WRITE_OFF_CATEGORIES) {
    ensureCategoryRows(category.id, [])
  }
  try {
    await loadLocations()
    if (locationId.value) await loadEntries()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  }
})
</script>
