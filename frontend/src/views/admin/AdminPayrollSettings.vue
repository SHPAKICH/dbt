<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex flex-wrap items-center gap-3 mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Настройки зарплаты</h1>
      <router-link to="/admin" class="text-sm text-primary hover:underline">← Админ-панель</router-link>
    </div>

    <p v-if="loading" class="text-gray-500">Загрузка…</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>

    <div v-else class="space-y-8">
      <!-- Ставки по ролям -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Ставки по ролям</h2>
        <p class="text-sm text-gray-500 mb-4">Базовая почасовая ставка (₽/час) для каждой должности.</p>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
              <th class="pb-2 font-medium">Должность</th>
              <th class="pb-2 font-medium text-right">Ставка, ₽/час</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="rate in rates" :key="rate.code">
              <td class="py-2.5 text-gray-800">{{ rate.name }}</td>
              <td class="py-2.5 text-right">
                <input
                  v-model.number="rate.hourly_rate"
                  type="number"
                  min="0"
                  step="1"
                  class="w-28 px-2 py-1.5 rounded-lg border border-gray-300 text-right"
                />
              </td>
            </tr>
          </tbody>
        </table>
        <button
          type="button"
          class="mt-4 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
          :disabled="savingRates"
          @click="saveRates"
        >
          {{ savingRates ? 'Сохранение…' : 'Сохранить ставки' }}
        </button>
        <span v-if="ratesSaved" class="ml-3 text-sm text-green-600">Сохранено</span>
      </section>

      <!-- Надбавки КРО по % прохождения -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Надбавки за прохождение КРО</h2>
        <p class="text-sm text-gray-500 mb-4">
          При достижении указанного % прохождения проверки к ставке добавляется надбавка (₽/час).
        </p>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
              <th class="pb-2 font-medium">Мин. % прохождения</th>
              <th class="pb-2 font-medium text-right">Надбавка, ₽/час</th>
              <th class="pb-2 w-10" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(band, idx) in bands" :key="idx">
              <td class="py-2.5">
                <input
                  v-model.number="band.min_percent"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="w-28 px-2 py-1.5 rounded-lg border border-gray-300"
                />
              </td>
              <td class="py-2.5 text-right">
                <input
                  v-model.number="band.hourly_bonus"
                  type="number"
                  min="0"
                  step="1"
                  class="w-28 px-2 py-1.5 rounded-lg border border-gray-300 text-right"
                />
              </td>
              <td class="py-2.5 text-right">
                <button
                  type="button"
                  class="text-xs text-rose-600 hover:underline"
                  :disabled="bands.length <= 1"
                  @click="removeBand(idx)"
                >
                  Удалить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="flex flex-wrap gap-2 mt-4">
          <button
            type="button"
            class="px-3 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50"
            @click="addBand"
          >
            Добавить порог
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
            :disabled="savingBands"
            @click="saveBands"
          >
            {{ savingBands ? 'Сохранение…' : 'Сохранить надбавки' }}
          </button>
          <span v-if="bandsSaved" class="self-center text-sm text-green-600">Сохранено</span>
        </div>
      </section>

      <!-- КРО по точкам -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Прохождение КРО по точкам</h2>
        <p class="text-sm text-gray-500 mb-4">Процент прохождения проверки точки за месяц.</p>
        <div class="flex flex-wrap items-end gap-3 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
            <select v-model="kroLocationId" class="px-3 py-2 border border-gray-300 rounded-lg min-w-[200px]">
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Месяц</label>
            <input v-model="kroMonth" type="month" class="px-3 py-2 border border-gray-300 rounded-lg" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Проход, %</label>
            <input
              v-model.number="kroPercent"
              type="number"
              min="0"
              max="100"
              step="0.01"
              class="w-28 px-3 py-2 border border-gray-300 rounded-lg"
            />
          </div>
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 disabled:opacity-50"
            :disabled="kroSaving || !kroLocationId"
            @click="saveLocationKro"
          >
            {{ kroSaving ? 'Сохранение…' : 'Сохранить КРО' }}
          </button>
        </div>
        <p v-if="kroPreview != null" class="text-sm text-gray-600">
          Надбавка к ставке за этот месяц: <strong>+{{ kroPreview }} ₽/час</strong>
        </p>
        <span v-if="kroSaved" class="text-sm text-green-600">Сохранено</span>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from '@/api/client'

const loading = ref(true)
const error = ref('')
const rates = ref([])
const bands = ref([])
const locations = ref([])
const savingRates = ref(false)
const savingBands = ref(false)
const ratesSaved = ref(false)
const bandsSaved = ref(false)
const kroLocationId = ref(null)
const kroMonth = ref('')
const kroPercent = ref(null)
const kroSaving = ref(false)
const kroSaved = ref(false)

const kroPreview = computed(() => {
  if (kroPercent.value == null || kroPercent.value === '') return null
  const p = Number(kroPercent.value)
  if (!Number.isFinite(p)) return null
  const sorted = [...bands.value].sort((a, b) => Number(b.min_percent) - Number(a.min_percent))
  for (const band of sorted) {
    const min = Number(band.min_percent)
    if (min <= 0) return Number(band.hourly_bonus)
    if (p >= min) return Number(band.hourly_bonus)
  }
  return 0
})

function addBand() {
  bands.value = [...bands.value, { min_percent: 0, hourly_bonus: 0 }]
}

function removeBand(idx) {
  if (bands.value.length <= 1) return
  bands.value = bands.value.filter((_, i) => i !== idx)
}

async function loadAll() {
  loading.value = true
  error.value = ''
  try {
    const [ratesRes, bandsRes, locRes] = await Promise.all([
      api.admin.positionRates(),
      api.admin.kroBands(),
      api.admin.locations(),
    ])
    rates.value = (ratesRes.rates || []).map((r) => ({ ...r }))
    bands.value = (bandsRes.bands || []).map((b) => ({
      min_percent: Number(b.min_percent),
      hourly_bonus: Number(b.hourly_bonus),
    }))
    if (!bands.value.length) {
      bands.value = [{ min_percent: 0, hourly_bonus: 0 }]
    }
    locations.value = locRes.locations || []
    if (locations.value.length && !kroLocationId.value) {
      kroLocationId.value = locations.value[0].id
    }
    const d = new Date()
    if (!kroMonth.value) {
      kroMonth.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    }
    await loadLocationKro()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

async function saveRates() {
  savingRates.value = true
  ratesSaved.value = false
  try {
    await api.admin.updatePositionRates({
      rates: rates.value.map((r) => ({ code: r.code, hourly_rate: r.hourly_rate })),
    })
    ratesSaved.value = true
    setTimeout(() => { ratesSaved.value = false }, 2000)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения ставок'
  } finally {
    savingRates.value = false
  }
}

async function saveBands() {
  savingBands.value = true
  bandsSaved.value = false
  try {
    const sorted = [...bands.value].sort((a, b) => Number(b.min_percent) - Number(a.min_percent))
    await api.admin.updateKroBands({ bands: sorted })
    bands.value = sorted
    bandsSaved.value = true
    setTimeout(() => { bandsSaved.value = false }, 2000)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения надбавок'
  } finally {
    savingBands.value = false
  }
}

async function loadLocationKro() {
  if (!kroLocationId.value || !kroMonth.value) return
  const [y, m] = kroMonth.value.split('-')
  try {
    const res = await api.admin.locationKro({
      location_id: kroLocationId.value,
      year: y,
      month: m,
    })
    kroPercent.value = res.pass_percent != null ? res.pass_percent : null
  } catch (_) {
    kroPercent.value = null
  }
}

async function saveLocationKro() {
  if (kroPercent.value == null || kroPercent.value === '' || !kroLocationId.value || !kroMonth.value) return
  const [y, m] = kroMonth.value.split('-')
  kroSaving.value = true
  kroSaved.value = false
  try {
    await api.admin.setLocationKro({
      location_id: kroLocationId.value,
      year: parseInt(y, 10),
      month: parseInt(m, 10),
      pass_percent: parseFloat(kroPercent.value),
    })
    kroSaved.value = true
    setTimeout(() => { kroSaved.value = false }, 2000)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения КРО'
  } finally {
    kroSaving.value = false
  }
}

watch([kroLocationId, kroMonth], loadLocationKro)

onMounted(loadAll)
</script>
