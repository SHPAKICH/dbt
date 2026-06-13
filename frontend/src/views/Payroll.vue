<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Зарплата</h1>
    <p class="text-sm text-gray-600 mb-4">
      Зарплата = Ставка + КРО + Премия. Часы из графика, выручка из дейли. При работе на другой точке расчёт по данным этой точки.
    </p>

    <div v-if="noAccess" class="p-4 rounded-lg bg-amber-50 text-amber-800">
      Нет доступа к расчётам.
    </div>

    <template v-else>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
        <form @submit.prevent="loadCalculations" class="flex flex-wrap items-end gap-4">
          <div v-if="locations.length" class="flex-shrink-0">
            <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
            <select v-model="locationId" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary min-w-[180px]">
              <option :value="null">Все точки</option>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
            </select>
          </div>
          <div class="flex-shrink-0">
            <label class="block text-sm font-medium text-gray-700 mb-1">Период</label>
            <input v-model="month" type="month" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>
          <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">
            Показать
          </button>
        </form>
      </div>

      <section v-if="formula" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Формула расчёта</h2>
        <div class="formula-block text-sm text-gray-800 space-y-4 font-serif">
          <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Почасовая ставка</div>
            <div class="formula-line">S<sub>час</sub> = B<sub>роль</sub> + K<sub>КРО</sub></div>
          </div>
          <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Оклад за смену</div>
            <div class="formula-line">З<sub>оклад</sub> = S<sub>час</sub> × h</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Премия за час (если выручка выше порога)</div>
            <div class="formula-line">
              P<sub>час</sub> =
              <span class="formula-brace">{</span>
              <span class="formula-piecewise">
                <span>0, если R ≤ n × T</span>
                <span class="formula-frac">
                  <span class="formula-num">(R − n × T) × {{ bonusSharePercent }}%</span>
                  <span class="formula-den">H<sub>всего</sub></span>
                </span>
              </span>
            </div>
          </div>
          <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Итого за день</div>
            <div class="formula-line">Итого = S<sub>час</sub> × h + P<sub>час</sub> × h</div>
          </div>
        </div>
        <dl class="mt-4 grid gap-2 sm:grid-cols-2 text-sm text-gray-600 border-t border-gray-100 pt-4">
          <div><dt class="inline text-gray-500">B<sub>роль</sub> — </dt><dd class="inline">базовая ставка должности (₽/час)</dd></div>
          <div><dt class="inline text-gray-500">K<sub>КРО</sub> — </dt><dd class="inline">надбавка за прохождение КРО точки (₽/час)</dd></div>
          <div><dt class="inline text-gray-500">h — </dt><dd class="inline">часы сотрудника за день</dd></div>
          <div><dt class="inline text-gray-500">R — </dt><dd class="inline">выручка точки за день (из дейли)</dd></div>
          <div><dt class="inline text-gray-500">n — </dt><dd class="inline">число сотрудников на смене</dd></div>
          <div><dt class="inline text-gray-500">T — </dt><dd class="inline">порог выручки на человека = {{ formatMoney(formula.revenuePerPersonThreshold) }} ₽</dd></div>
          <div><dt class="inline text-gray-500">H<sub>всего</sub> — </dt><dd class="inline">суммарные часы всех на точке за день</dd></div>
        </dl>
        <div v-if="formula.positionRates?.length" class="mt-4 pt-4 border-t border-gray-100">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Ставки по ролям</h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="rate in formula.positionRates"
              :key="rate.code"
              class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700"
            >
              {{ rate.name }}: {{ rate.hourly_rate }} ₽/ч
            </span>
          </div>
        </div>
        <div v-if="formula.kroBands?.length" class="mt-3">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Надбавки КРО</h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="(band, idx) in sortedKroBands"
              :key="idx"
              class="text-xs px-2 py-1 rounded-full bg-primary/10 text-primary"
            >
              ≥ {{ band.min_percent }}% → +{{ band.hourly_bonus }} ₽/ч
            </span>
          </div>
        </div>
      </section>

      <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка…</p>
      <p v-else-if="loadError" class="p-4 rounded-lg bg-red-50 text-red-700">{{ loadError }}</p>

      <template v-else>
        <div v-if="result.byDays && result.byDays.length" class="space-y-4">
          <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
            <span>Часов за период: <strong class="text-gray-800">{{ result.totalHours }}</strong></span>
            <span>Итого к начислению: <strong class="text-gray-800">{{ formatMoney(result.totalAmount) }} ₽</strong></span>
          </div>

          <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
            <div
              v-for="day in result.byDays"
              :key="day.date + '_' + day.location_id"
              class="border-b border-gray-100 last:border-b-0"
            >
              <button
                type="button"
                class="w-full flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-left hover:bg-gray-50 transition-colors"
                @click="toggleDay(day.date, day.location_id)"
              >
                <span class="font-medium text-gray-800">{{ formatDate(day.date) }}</span>
                <span class="text-gray-600">{{ day.location_name }}</span>
                <span class="text-sm text-gray-500">Выручка: {{ formatMoney(day.revenue) }} ₽ · {{ day.n_people }} чел. · {{ day.total_hours }} ч</span>
                <span class="text-primary font-medium">{{ formatMoney(day.employees.reduce((s, e) => s + e.total, 0)) }} ₽</span>
                <span class="text-gray-400">{{ expandedDay === day.date + '_' + day.location_id ? '▼' : '▶' }}</span>
              </button>
              <div v-if="expandedDay === day.date + '_' + day.location_id" class="bg-gray-50/80 px-4 pb-3 pt-1">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-gray-600 border-b border-gray-200">
                      <th class="py-2 text-left font-medium">Сотрудник</th>
                      <th class="py-2 text-right font-medium">Часы</th>
                      <th class="py-2 text-right font-medium">Ставка</th>
                      <th class="py-2 text-right font-medium">КРО</th>
                      <th class="py-2 text-right font-medium">Премия/ч</th>
                      <th class="py-2 text-right font-medium">Премия</th>
                      <th class="py-2 text-right font-medium">Итого</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="emp in day.employees" :key="emp.user_id" class="border-b border-gray-100 last:border-b-0">
                      <td class="py-2 text-gray-800">{{ emp.user_name }} <span class="text-gray-500">({{ emp.position_label }})</span></td>
                      <td class="py-2 text-right">{{ emp.hours }}</td>
                      <td class="py-2 text-right">{{ emp.base_rate }} ₽</td>
                      <td class="py-2 text-right">+{{ emp.kro_add }} ₽</td>
                      <td class="py-2 text-right">{{ emp.bonus_per_hour }} ₽</td>
                      <td class="py-2 text-right">{{ formatMoney(emp.bonus) }} ₽</td>
                      <td class="py-2 text-right font-medium">{{ formatMoney(emp.total) }} ₽</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <p v-else class="p-6 text-center text-gray-500 rounded-xl border border-gray-200 bg-white">
          Нет смен за выбранный период.
        </p>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/api/client'
import { getCurrentUser } from '@/router'

const user = ref(null)
const locations = ref([])
const locationId = ref(null)
const month = ref('')
const loading = ref(false)
const loadError = ref('')
const result = ref({ byDays: [], totalHours: 0, totalAmount: 0 })
const expandedDay = ref(null)
const formula = ref(null)

const noAccess = computed(() => !user.value)

const bonusSharePercent = computed(() => {
  const share = Number(formula.value?.bonusShare ?? 0.05)
  return Math.round(share * 100)
})

const sortedKroBands = computed(() => {
  if (!formula.value?.kroBands) return []
  return [...formula.value.kroBands]
    .filter((b) => Number(b.min_percent) > 0)
    .sort((a, b) => Number(b.min_percent) - Number(a.min_percent))
})

function formatDate(d) {
  if (!d) return ''
  const [y, m, day] = d.split('-')
  const date = new Date(+y, +m - 1, +day)
  const days = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']
  return `${day}.${m}.${y} (${days[date.getDay()]})`
}

function formatMoney(v) {
  if (v == null || v === '') return '—'
  const n = Number(v)
  return isNaN(n) ? '—' : n.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function toggleDay(date, locId) {
  const key = date + '_' + locId
  expandedDay.value = expandedDay.value === key ? null : key
}

async function loadLocations() {
  try {
    const res = await api.payroll.locations()
    if (res.success && res.locations) {
      locations.value = res.locations
      if (locations.value.length && locationId.value === null) {
        locationId.value = locations.value[0].id
      }
    }
  } catch (_) {
    locations.value = []
  }
}

async function loadFormula() {
  try {
    const res = await api.payroll.formula()
    if (res.success) {
      formula.value = res
    }
  } catch (_) {
    formula.value = null
  }
}

async function loadCalculations() {
  if (!month.value) {
    const d = new Date()
    month.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  }
  const [y, m] = month.value.split('-')
  const from = `${y}-${m}-01`
  const to = new Date(+y, +m, 0).getDate()
  const toStr = `${y}-${m}-${String(to).padStart(2, '0')}`

  loading.value = true
  loadError.value = ''
  try {
    const params = { from, to: toStr }
    if (locationId.value != null && locationId.value !== '') params.location_id = locationId.value
    const res = await api.payroll.calculations(params)
    if (res.success) {
      result.value = {
        byDays: res.byDays || [],
        totalHours: res.totalHours ?? 0,
        totalAmount: res.totalAmount ?? 0,
      }
    } else {
      loadError.value = res.message || 'Ошибка загрузки'
    }
  } catch (e) {
    loadError.value = e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  user.value = await getCurrentUser()
  if (!user.value) return
  const d = new Date()
  month.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  await Promise.all([loadLocations(), loadFormula()])
  await loadCalculations()
})
</script>

<style scoped>
.formula-block {
  line-height: 1.8;
}

.formula-line {
  font-size: 1.05rem;
  letter-spacing: 0.01em;
}

.formula-piecewise {
  display: inline-grid;
  grid-template-columns: auto auto;
  gap: 0.25rem 0.75rem;
  vertical-align: middle;
  margin-left: 0.25rem;
}

.formula-frac {
  display: inline-grid;
  text-align: center;
  vertical-align: middle;
}

.formula-num {
  border-bottom: 1px solid currentColor;
  padding: 0 0.25rem 0.1rem;
}

.formula-den {
  padding-top: 0.1rem;
}

.formula-brace {
  font-size: 1.4rem;
  line-height: 1;
  vertical-align: middle;
  margin-right: 0.15rem;
}
</style>
