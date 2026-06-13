<template>
  <div class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Заказ поставки</h1>
    <p v-if="error" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">{{ error }}</p>
    <p v-if="loading" class="text-gray-500">Загрузка...</p>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Точка *</label>
            <select v-model="form.locationId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
              <option :value="null">Выберите точку...</option>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий (опционально)</label>
            <textarea v-model="form.comment" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Дополнительная информация"></textarea>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <input
          v-model="productSearch"
          type="text"
          placeholder="Поиск по названию..."
          class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm"
        />
      </div>

      <div v-for="cat in filteredCategories" :key="cat.name" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <button
          type="button"
          class="w-full flex items-center justify-between px-5 py-3.5 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
          @click="toggleCategory(cat.name)"
        >
          <div class="flex items-center gap-3">
            <svg
              class="w-4 h-4 text-gray-500 transition-transform duration-200"
              :class="{ 'rotate-90': openCategories[cat.name] }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-semibold text-gray-800">{{ cat.name }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span v-if="categorySelectedCount(cat) > 0" class="text-xs font-medium bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
              {{ categorySelectedCount(cat) }} поз.
            </span>
            <span v-if="categoryTotal(cat) > 0" class="text-xs font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
              {{ formatPrice(categoryTotal(cat)) }}
            </span>
            <span class="text-xs text-gray-400">{{ cat.products.length }} товаров</span>
          </div>
        </button>

        <div v-show="openCategories[cat.name]" class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-t border-gray-200">
              <tr>
                <th class="px-4 py-2 font-medium text-gray-600 w-8">#</th>
                <th class="px-4 py-2 font-medium text-gray-600">Наименование</th>
                <th class="px-4 py-2 font-medium text-gray-600">Фасовка</th>
                <th class="px-4 py-2 font-medium text-gray-600 text-right">Цена, руб.</th>
                <th class="px-4 py-2 font-medium text-gray-600 w-28 text-center">Кол-во</th>
                <th class="px-4 py-2 font-medium text-gray-600 text-right w-28">Итого</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(p, idx) in cat.products"
                :key="p.id"
                class="border-t border-gray-100 transition-colors"
                :class="form.items[p.id] > 0 ? 'bg-green-50/60' : 'hover:bg-gray-50/50'"
              >
                <td class="px-4 py-2 text-gray-400 text-xs">{{ idx + 1 }}</td>
                <td class="px-4 py-2 text-gray-800">{{ p.name }}</td>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ p.packageDescription || '—' }}</td>
                <td class="px-4 py-2 text-gray-700 text-right tabular-nums">{{ p.pricePerUnit != null ? formatPrice(p.pricePerUnit) : '—' }}</td>
                <td class="px-4 py-2 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      type="button"
                      class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-lg leading-none transition-colors"
                      :disabled="!form.items[p.id] || form.items[p.id] <= 0"
                      @click="decrement(p.id)"
                    >&minus;</button>
                    <input
                      v-model.number="form.items[p.id]"
                      type="number"
                      step="1"
                      min="0"
                      max="9999"
                      class="w-16 px-1.5 py-1 border border-gray-300 rounded text-center text-sm tabular-nums"
                      placeholder="0"
                    />
                    <button
                      type="button"
                      class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-lg leading-none transition-colors"
                      @click="increment(p.id)"
                    >+</button>
                  </div>
                </td>
                <td class="px-4 py-2 text-right tabular-nums font-medium"
                    :class="lineTotal(p) > 0 ? 'text-green-700' : 'text-gray-300'"
                >
                  {{ lineTotal(p) > 0 ? formatPrice(lineTotal(p)) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="totalPositions > 0" class="sticky bottom-0 z-10 bg-white/95 backdrop-blur border border-gray-200 rounded-xl p-4 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-4 text-sm">
          <span class="text-gray-600">Позиций: <strong class="text-gray-800">{{ totalPositions }}</strong></span>
          <span class="text-gray-600">Сумма: <strong class="text-gray-800 text-base">{{ formatPrice(grandTotal) }}</strong></span>
        </div>
        <button type="submit" :disabled="saving" class="px-6 py-2.5 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700 disabled:opacity-50 transition-colors">
          {{ saving ? 'Формирование...' : 'Сформировать заказ' }}
        </button>
      </div>

      <div v-else class="text-center py-6">
        <p class="text-sm text-gray-400">Укажите количество хотя бы для одного товара</p>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { api, getToken } from '@/api/client'

const locations = ref([])
const categories = ref([])
const productSearch = ref('')
const openCategories = reactive({})
const form = reactive({
  locationId: null,
  comment: '',
  items: {},
})
const loading = ref(true)
const error = ref('')
const saving = ref(false)

function formatPrice(val) {
  if (val == null) return '—'
  return Number(val).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function lineTotal(product) {
  const qty = Number(form.items[product.id]) || 0
  if (qty <= 0 || product.pricePerUnit == null) return 0
  return qty * product.pricePerUnit
}

function categorySelectedCount(cat) {
  return cat.products.filter(p => (Number(form.items[p.id]) || 0) > 0).length
}

function categoryTotal(cat) {
  return cat.products.reduce((sum, p) => sum + lineTotal(p), 0)
}

const totalPositions = computed(() => {
  let count = 0
  for (const qty of Object.values(form.items)) {
    if ((Number(qty) || 0) > 0) count++
  }
  return count
})

const grandTotal = computed(() => {
  let sum = 0
  for (const cat of categories.value) {
    sum += categoryTotal(cat)
  }
  return sum
})

const filteredCategories = computed(() => {
  const q = (productSearch.value || '').trim().toLowerCase()
  if (!q) return categories.value
  return categories.value
    .map(cat => ({
      ...cat,
      products: cat.products.filter(p => p.name && p.name.toLowerCase().includes(q)),
    }))
    .filter(cat => cat.products.length > 0)
})

function toggleCategory(name) {
  openCategories[name] = !openCategories[name]
}

function increment(productId) {
  form.items[productId] = (Number(form.items[productId]) || 0) + 1
}

function decrement(productId) {
  const val = (Number(form.items[productId]) || 0) - 1
  form.items[productId] = val < 0 ? 0 : val
}

onMounted(async () => {
  try {
    const [locRes, prodRes] = await Promise.all([api.supply.locations(), api.supply.products()])
    locations.value = locRes.locations || []
    categories.value = prodRes.categories || []
    for (const cat of categories.value) {
      openCategories[cat.name] = true
      for (const p of cat.products) {
        form.items[p.id] = 0
      }
    }
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})

async function submit() {
  const items = {}
  let hasAny = false
  for (const [productId, qty] of Object.entries(form.items)) {
    const q = Number(qty)
    if (q > 0) {
      items[productId] = q
      hasAny = true
    }
  }
  if (!hasAny) {
    error.value = 'Укажите количество хотя бы для одного товара.'
    return
  }
  error.value = ''
  saving.value = true
  try {
    const res = await api.supply.createOrder({
      locationId: form.locationId,
      comment: form.comment || undefined,
      items,
    })
    const url = api.supply.getExportUrl(res.orderId)
    const token = getToken()
    const response = await fetch(url, {
      credentials: 'include',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
    if (!response.ok) {
      const text = await response.text()
      let msg = 'Ошибка скачивания'
      try {
        const j = JSON.parse(text)
        if (j?.message) msg = j.message
      } catch (_) {}
      throw new Error(msg)
    }
    const blob = await response.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `supply_order_${res.orderId}.xlsx`
    a.click()
    URL.revokeObjectURL(a.href)
    form.comment = ''
    Object.keys(form.items).forEach((k) => { form.items[k] = 0 })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка создания заказа'
  } finally {
    saving.value = false
  }
}
</script>
