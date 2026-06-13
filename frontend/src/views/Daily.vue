<template>
  <div class="max-w-full mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-4">
      <router-link to="/documentation" class="text-gray-500 hover:text-gray-700">← Документация</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Дейли</h1>
    </div>

    <p v-if="noAccess" class="p-4 rounded-lg bg-amber-50 text-amber-800">У вас нет доступа ни к одной точке.</p>
    <template v-else>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-4">
        <form @submit.prevent="loadReports" class="flex flex-wrap items-end gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
            <select v-model="locationId" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Месяц</label>
            <input v-model="month" type="month" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>
          <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">Показать</button>
          <button type="button" @click="downloadExport" class="px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
            📄 Экспорт в XLSX
          </button>
        </form>
      </div>

      <p class="text-sm text-gray-500 mb-2">ТО/План — только тер.управ+. БАР/ДОСТАВКА/САМОВЫВОЗ/БОНУСЫ, Чеки — закрывающий смену. Часы — из графика.</p>

      <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
      <p v-else-if="loadError" class="p-4 rounded-lg bg-red-50 text-red-700">{{ loadError }}</p>
      <div v-else class="overflow-x-auto border border-gray-200 rounded-lg bg-white shadow-sm">
        <table class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-2 font-medium text-gray-700">Дата</th>
              <th class="px-2 py-2 font-medium text-gray-700">День</th>
              <th class="px-2 py-2 font-medium text-gray-700">План</th>
              <th class="px-2 py-2 font-medium text-gray-700">ТО</th>
              <th class="px-2 py-2 font-medium text-gray-700">DELTA</th>
              <th class="px-2 py-2 font-medium text-gray-700">БАР</th>
              <th class="px-2 py-2 font-medium text-gray-700">ДОСТ</th>
              <th class="px-2 py-2 font-medium text-gray-700">САМ</th>
              <th class="px-2 py-2 font-medium text-gray-700">БОНУСЫ</th>
              <th class="px-2 py-2 font-medium text-gray-700">Чеки БАР</th>
              <th class="px-2 py-2 font-medium text-gray-700">Чеки ДОСТ</th>
              <th class="px-2 py-2 font-medium text-gray-700">Чеки САМ</th>
              <th class="px-2 py-2 font-medium text-gray-700">Заказов</th>
              <th class="px-2 py-2 font-medium text-gray-700">Ср.чек БАР</th>
              <th class="px-2 py-2 font-medium text-gray-700">Ср.чек ДОСТ</th>
              <th class="px-2 py-2 font-medium text-gray-700">Ср.чек САМ</th>
              <th class="px-2 py-2 font-medium text-gray-700">Часы</th>
              <th class="px-2 py-2 font-medium text-gray-700">Произв. зак</th>
              <th class="px-2 py-2 font-medium text-gray-700">Произв. ₽</th>
              <th class="px-2 py-2 font-medium text-gray-700">Менеджер</th>
              <th class="px-2 py-2 font-medium text-gray-700 w-12"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in reports" :key="r.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-2 py-1.5 text-gray-800">{{ formatDate(r.reportDate) }}</td>
              <td class="px-2 py-1.5 text-gray-600">{{ r.dayOfWeek }}</td>
              <td class="px-2 py-1.5">
                <input v-if="canEditPlan" v-model.number="r.planDaily" type="text" inputmode="decimal" class="w-20 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
                <span v-else class="text-gray-800">{{ num(r.planDaily) }}</span>
              </td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.toRevenue) }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.deltaPlan) }}</td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.bar" type="text" inputmode="decimal" class="w-20 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.delivery" type="text" inputmode="decimal" class="w-20 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.selfPickup" type="text" inputmode="decimal" class="w-20 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.bonuses" type="text" inputmode="decimal" class="w-20 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.checksBar" type="text" inputmode="numeric" class="w-14 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.checksDelivery" type="text" inputmode="numeric" class="w-14 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5">
                <input v-model.number="r.checksSelfPickup" type="text" inputmode="numeric" class="w-14 px-1.5 py-0.5 border rounded text-end text-sm" @blur="saveRow(r)" />
              </td>
              <td class="px-2 py-1.5 text-gray-800">{{ r.ordersCount != null ? r.ordersCount : '' }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.avgCheckBar) }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.avgCheckDelivery) }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.avgCheckSelfPickup) }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.workerHours) }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.productivityOrders) }}</td>
              <td class="px-2 py-1.5 text-gray-800">{{ num(r.productivityMoney) }}</td>
              <td class="px-2 py-1.5">
                <select v-model="r.managerId" class="w-28 px-1.5 py-0.5 border rounded text-sm" @change="saveRow(r)">
                  <option value="">—</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.fullName }}</option>
                </select>
              </td>
              <td class="px-2 py-1.5">
                <button type="button" :disabled="savingId === r.id" class="text-primary hover:underline text-xs" @click="saveRow(r)">
                  {{ savingId === r.id ? '...' : '✓' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="reports.length === 0 && !loading" class="p-4 text-gray-500 text-center">Нет данных за выбранный месяц.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api, getToken } from '@/api/client'

const locations = ref([])
const users = ref([])
const locationId = ref(null)
const month = ref(new Date().toISOString().slice(0, 7))
const reports = ref([])
const canEditPlan = ref(false)
const noAccess = ref(false)
const loading = ref(false)
const loadError = ref('')
const savingId = ref(null)

function num(v) {
  if (v == null || v === '') return ''
  return Number(v).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return ''
  const [y, m, day] = d.split('-')
  return `${day}.${m}.${y}`
}

async function loadReports() {
  if (!locationId.value) return
  loading.value = true
  loadError.value = ''
  try {
    const res = await api.daily.reports({ location_id: locationId.value, month: month.value })
    // Нормализуем managerId: null → '' для корректной работы select (option value="" для «—»)
    reports.value = (res.reports || []).map((row) => ({
      ...row,
      managerId: row.managerId == null ? '' : row.managerId,
    }))
    canEditPlan.value = res.canEditPlan || false
  } catch (e) {
    loadError.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

async function saveRow(r) {
  savingId.value = r.id
  const num = (v) => (v != null && v !== '' && Number.isFinite(Number(v)) ? Number(v) : null)
  const numInt = (v) => (v != null && v !== '' && Number.isFinite(Number(v)) ? Math.floor(Number(v)) : null)
  // Все поля явно передаём, чтобы undefined/NaN не затирали значения на бэкенде
  const payload = {
    id: r.id,
    planDaily: num(r.planDaily),
    bar: num(r.bar),
    delivery: num(r.delivery),
    selfPickup: num(r.selfPickup),
    bonuses: num(r.bonuses),
    checksBar: numInt(r.checksBar),
    checksDelivery: numInt(r.checksDelivery),
    checksSelfPickup: numInt(r.checksSelfPickup),
    managerId: r.managerId === '' || r.managerId == null ? null : Number(r.managerId),
  }
  try {
    const updated = await api.daily.update(payload)
    // Обновляем только вычисляемые поля, чтобы не затирать ввод пользователя ответом сервера
    const computedOnly = [
      'toRevenue', 'deltaPlan', 'ordersCount', 'avgCheckBar', 'avgCheckDelivery', 'avgCheckSelfPickup',
      'workerHours', 'productivityOrders', 'productivityMoney',
    ]
    computedOnly.forEach((k) => {
      if (updated[k] !== undefined) r[k] = updated[k]
    })
  } catch (_) {
    // оставляем введённые пользователем значения без изменений
  } finally {
    savingId.value = null
  }
}

async function downloadExport() {
  if (!locationId.value) return
  const url = api.daily.getExportUrl(locationId.value, month.value)
  const token = getToken()
  try {
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
    a.download = `daily_${month.value}.xlsx`
    a.click()
    URL.revokeObjectURL(a.href)
  } catch (e) {
    alert(e.message || 'Ошибка экспорта')
  }
}

onMounted(async () => {
  try {
    const [locRes, userRes] = await Promise.all([api.daily.locations(), api.daily.users()])
    locations.value = locRes.locations || []
    users.value = userRes.users || []
    if (locations.value.length) {
      locationId.value = locations.value[0].id
      await loadReports()
    } else {
      noAccess.value = true
    }
  } catch (e) {
    noAccess.value = true
  }
})
</script>
