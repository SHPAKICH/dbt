<template>
  <div class="max-w-full mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">График смен</h1>

    <div class="flex flex-wrap items-center gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
        <select
          v-model="locationId"
          class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary/20 focus:border-primary"
          @change="loadGrid"
        >
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Период</label>
        <select
          v-model="period"
          class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary/20 focus:border-primary"
          @change="loadGrid"
        >
          <option value="week">Неделя</option>
          <option value="month">Месяц</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Дата</label>
        <input
          v-model="date"
          type="date"
          class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary/20 focus:border-primary"
          @change="loadGrid"
        />
      </div>
    </div>

    <p v-if="grid" class="text-sm text-gray-500 mb-2">
      <span v-if="grid.availability">🔹 Рамка/точка — день отмечен в карте возможностей сотрудника</span>
      <span v-if="grid.availability && grid.dates?.some(isHoliday)" class="mx-2">·</span>
      <span v-if="grid.dates?.some(isHoliday)">🔴 Красный столбец — официальный праздник РФ</span>
    </p>
    <div v-if="loading" class="py-12 text-center text-gray-500">Загрузка графика...</div>
    <div v-else-if="gridError" class="p-4 rounded-lg bg-red-50 text-red-700">{{ gridError }}</div>
    <div v-else-if="grid" class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
      <table class="w-full min-w-[600px] text-sm">
        <thead>
          <tr class="bg-gray-100 border-b border-gray-200">
            <th class="text-left p-3 font-semibold text-gray-800 sticky left-0 bg-gray-100 z-10 min-w-[180px]">Сотрудник</th>
            <th
              v-for="d in grid.dates"
              :key="d"
              class="p-2 text-center font-medium whitespace-nowrap"
              :class="[
                isHoliday(d) ? 'schedule-cell-holiday schedule-date-cell' : 'text-gray-700',
                canEditSchedule ? 'cursor-pointer hover:bg-gray-200' : ''
              ]"
              :title="isHoliday(d) ? 'Официальный праздник РФ' : ''"
              @click="canEditSchedule && openHelpers(d)"
            >
              {{ formatDate(d) }}
            </th>
            <th class="p-2 text-center font-semibold text-gray-800">Итого</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="userId in userOrder" :key="userId">
          <tr
            v-if="grid.users[userId]"
            class="border-b border-gray-100 hover:bg-gray-50/50"
          >
            <td
              class="p-3 sticky left-0 z-10 border-r border-gray-100"
              :class="[hasCardStyle(grid.users[userId]) ? '' : 'bg-white', scheduleCardClass(grid.users[userId])]"
              :style="scheduleCardStyle(grid.users[userId])"
            >
              <span class="font-medium" :class="hasCardStyle(grid.users[userId]) ? '' : 'text-gray-800'" :style="scheduleNameStyle(grid.users[userId])">{{ grid.users[userId].fullName }}</span>
              <span class="block text-xs" :class="hasCardStyle(grid.users[userId]) ? '' : 'text-gray-500'" :style="scheduleStatusStyle(grid.users[userId])">{{ grid.users[userId].positionLabel }}</span>
            </td>
            <td
              v-for="d in grid.dates"
              :key="d"
              class="p-1 text-center align-middle"
              :class="{ 'schedule-cell-holiday': isHoliday(d) }"
            >
              <span
                v-if="grid.shifts[userId] && grid.shifts[userId][d]"
                class="inline-block px-2 py-1 rounded text-gray-800"
                :class="{
                  'bg-gray-200': grid.shifts[userId][d].isDayOff,
                  'bg-amber-100': grid.shifts[userId][d].isNight,
                  'bg-primary/20': !grid.shifts[userId][d].isDayOff && !grid.shifts[userId][d].isNight,
                  'ring-1 ring-blue-300 ring-dashed': hasAvailability(userId, d)
                }"
                :title="cellTitle(userId, d)"
                @click="canEditSchedule && onCellClick(userId, d, grid.shifts[userId][d])"
              >
                {{ cellDisplayText(grid.shifts[userId][d]) }}
              </span>
              <span
                v-else
                class="inline-block px-2 py-1 rounded min-w-[3rem]"
                :class="[
                  hasAvailability(userId, d) ? 'bg-blue-50/80 ring-1 ring-blue-200 ring-dashed text-blue-700' : 'text-gray-400',
                  canEditSchedule ? 'cursor-pointer hover:bg-gray-100' : ''
                ]"
                :title="cellTitle(userId, d)"
                @click="canEditSchedule && onCellClick(userId, d, null)"
              >
                {{ hasAvailability(userId, d) ? 'ж' : '—' }}
              </span>
            </td>
            <td class="p-2 text-center font-medium text-gray-800">
              {{ (grid.totalsByUser[userId] ?? 0).toFixed(1) }}
            </td>
          </tr>
          </template>
          <tr class="bg-gray-50 font-semibold">
            <td class="p-3 sticky left-0 bg-gray-50 z-10 border-r border-gray-200">Итого</td>
            <td
              v-for="d in grid.dates"
              :key="d"
              class="p-2 text-center text-gray-800"
              :class="{ 'schedule-cell-holiday': isHoliday(d) }"
            >
              {{ (grid.totalsByDate[d] ?? 0).toFixed(1) }}
            </td>
            <td class="p-2 text-center text-gray-800">—</td>
          </tr>
        </tbody>
        <!-- Таблица усиления: сотрудники не с этой точки, но с сменами на ней -->
        <tbody v-if="grid.usersReinforcement && Object.keys(grid.usersReinforcement).length > 0">
          <tr class="bg-blue-50/80 border-t-2 border-blue-200">
            <td colspan="100" class="p-2 text-sm font-semibold text-blue-800 sticky left-0 bg-blue-50/80 z-10">
              Усиление (сотрудники других точек)
            </td>
          </tr>
          <template v-for="userId in reinforcementUserOrder" :key="'r-' + userId">
          <tr
            v-if="grid.usersReinforcement[userId]"
            class="border-b border-gray-100 hover:bg-blue-50/30"
          >
            <td
              class="p-3 sticky left-0 z-10 border-r border-gray-100"
              :class="[hasCardStyle(grid.usersReinforcement[userId]) ? '' : 'bg-white', scheduleCardClass(grid.usersReinforcement[userId])]"
              :style="scheduleCardStyle(grid.usersReinforcement[userId])"
            >
              <span class="font-medium" :class="hasCardStyle(grid.usersReinforcement[userId]) ? '' : 'text-gray-800'" :style="scheduleNameStyle(grid.usersReinforcement[userId])">{{ grid.usersReinforcement[userId].fullName }}</span>
              <span class="block text-xs" :class="hasCardStyle(grid.usersReinforcement[userId]) ? '' : 'text-gray-500'" :style="scheduleStatusStyle(grid.usersReinforcement[userId])">{{ grid.usersReinforcement[userId].positionLabel }}</span>
            </td>
            <td
              v-for="d in grid.dates"
              :key="d"
              class="p-1 text-center align-middle"
              :class="{ 'schedule-cell-holiday': isHoliday(d) }"
            >
              <span
                v-if="grid.shifts[userId] && grid.shifts[userId][d]"
                class="inline-block px-2 py-1 rounded text-gray-800"
                :class="{
                  'bg-gray-200': grid.shifts[userId][d].isDayOff,
                  'bg-amber-100': grid.shifts[userId][d].isNight,
                  'bg-primary/20': !grid.shifts[userId][d].isDayOff && !grid.shifts[userId][d].isNight,
                }"
                :title="cellTitle(userId, d)"
              >
                {{ cellDisplayText(grid.shifts[userId][d]) }}
              </span>
              <span v-else class="inline-block px-2 py-1 min-w-[3rem] text-gray-400">—</span>
            </td>
            <td class="p-2 text-center font-medium text-gray-800">
              {{ (grid.totalsByUser[userId] ?? 0).toFixed(1) }}
            </td>
          </tr>
          </template>
        </tbody>
      </table>
    </div>
    <p v-else-if="locations.length === 0 && !loading" class="text-gray-500">Нет доступных точек.</p>

    <div
      v-if="helpers.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-800">
            Кого позвать на усиление {{ helpers.date ? '(' + formatDate(helpers.date) + ')' : '' }}
          </h2>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600"
            @click="closeHelpers"
          >
            ✕
          </button>
        </div>
        <div class="px-4 py-3">
          <p v-if="helpers.loading" class="text-gray-500 text-sm">Загрузка кандидатов...</p>
          <p v-else-if="helpers.error" class="text-sm text-red-600">{{ helpers.error }}</p>
          <p v-else-if="helpers.items.length === 0" class="text-sm text-gray-500">
            Нет сотрудников, которые хотят работать в этот день и свободны от смен.
          </p>
          <ul v-else class="space-y-2 max-h-80 overflow-y-auto">
            <li
              v-for="h in helpers.items"
              :key="h.id"
              class="flex items-center justify-between gap-3 border border-gray-100 rounded-lg px-3 py-2 hover:bg-gray-50"
            >
              <div>
                <div class="font-medium text-gray-900">
                  {{ h.fullName }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ h.positionLabel }}
                  <span v-if="h.homeLocationName"> · {{ h.homeLocationName }}</span>
                </div>
                <div v-if="h.availability" class="text-xs text-blue-700 mt-0.5">
                  Желает работать: {{ h.availability }}
                </div>
              </div>
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-medium hover:bg-primary-dark disabled:opacity-50"
                :disabled="cellSaving"
                @click="sendInvite(h)"
              >
                Отправить инвайт
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from '@/api/client'
import { isHoliday } from '@/utils/holidays'
import { cardConfigToStyle, cardNameStyle, cardStatusStyle, cardEffectClass } from '@/utils/cardStyleRenderer'

const locations = ref([])
const locationId = ref(null)
const period = ref('week')
const date = ref(new Date().toISOString().slice(0, 10))
const grid = ref(null)
const loading = ref(false)
const gridError = ref('')
const canEditSchedule = ref(false)
const cellSaving = ref(false)
const helpers = ref({
  open: false,
  date: '',
  items: [],
  loading: false,
  error: '',
})

/** Порядок строк: сервер отдаёт userOrder, т.к. у объекта users в JS числовые ключи сортируются по id */
const userOrder = computed(() => {
  const g = grid.value
  if (!g?.users) return []
  if (Array.isArray(g.userOrder) && g.userOrder.length) {
    return g.userOrder.map((id) => Number(id))
  }
  return Object.keys(g.users).map((id) => Number(id))
})

const reinforcementUserOrder = computed(() => {
  const g = grid.value
  if (!g?.usersReinforcement) return []
  if (Array.isArray(g.usersReinforcementOrder) && g.usersReinforcementOrder.length) {
    return g.usersReinforcementOrder.map((id) => Number(id))
  }
  return Object.keys(g.usersReinforcement).map((id) => Number(id))
})

function formatDate(d) {
  const [y, m, day] = d.split('-')
  return `${day}.${m}`
}

function hasAvailability(userId, d) {
  return grid.value?.availability?.[userId]?.[d]
}

function cellTitle(userId, d) {
  const av = grid.value?.availability?.[userId]?.[d]
  return av ? 'Желаемый график: ' + av : ''
}

function cellDisplayText(cell) {
  if (cell.displayText && cell.displayText !== '0') return cell.displayText
  if (cell.timeStart && cell.timeEnd) return cell.timeStart.slice(0, 5) + '–' + cell.timeEnd.slice(0, 5)
  return cell.cellText || '0'
}

function hasCardStyle(user) {
  const sc = user?.cardStyleConfig
  return sc && typeof sc === 'object' && Object.keys(sc).length > 0
}

function scheduleCardClass(user) {
  if (hasCardStyle(user)) return cardEffectClass(user.cardStyleConfig)
  const code = user?.cardCode
  if (!code) return ''
  return 'schedule-user-card schedule-user-card_' + String(code).replace(/_/g, '-')
}

function scheduleCardStyle(user) {
  if (!hasCardStyle(user)) return {}
  const base = cardConfigToStyle(user.cardStyleConfig)
  delete base.borderRadius
  return base
}

function scheduleNameStyle(user) {
  if (!hasCardStyle(user)) return {}
  return cardNameStyle(user.cardStyleConfig)
}

function scheduleStatusStyle(user) {
  if (!hasCardStyle(user)) return {}
  return cardStatusStyle(user.cardStyleConfig)
}

async function onCellClick(userId, d, currentShift) {
  if (cellSaving.value || !locationId.value) return
  const current = currentShift && !currentShift.isDayOff && currentShift.timeStart
    ? `${currentShift.timeStart.slice(0, 2)}-${currentShift.timeEnd.slice(0, 2)}`
    : ''
  const raw = window.prompt('Время смены (часы, например 10-22) или 0 для выходного:', current || '10-22')
  if (raw == null) return
  const value = raw.trim() === '' || raw === '0' ? '0' : raw.trim()
  cellSaving.value = true
  try {
    await api.schedule.updateCell({
      userId: Number(userId),
      locationId: locationId.value,
      date: d,
      value,
    })
    await loadGrid()
  } catch (e) {
    gridError.value = e.data?.message || e.message || 'Ошибка сохранения'
  } finally {
    cellSaving.value = false
  }
}

function openHelpers(d) {
  if (!locationId.value) return
  helpers.value.open = true
  helpers.value.date = d
  helpers.value.loading = true
  helpers.value.error = ''
  helpers.value.items = []
  api.schedule
    .helpers({ location_id: locationId.value, date: d })
    .then((res) => {
      helpers.value.items = res.items || []
    })
    .catch((e) => {
      helpers.value.error = e.data?.message || e.message || 'Ошибка загрузки кандидатов'
    })
    .finally(() => {
      helpers.value.loading = false
    })
}

function closeHelpers() {
  helpers.value.open = false
}

function availabilityToValue(helper) {
  if (helper.timeStart && helper.timeEnd) {
    const [sh, sm] = helper.timeStart.split(':').map((x) => parseInt(x, 10))
    const [eh, em] = helper.timeEnd.split(':').map((x) => parseInt(x, 10))
    const start = sh + (sm >= 30 ? 0.5 : 0)
    const end = eh + (em >= 30 ? 0.5 : 0)
    const fmt = (v) => (v % 1 === 0 ? String(v) : v.toString().replace('.5', '.5'))
    return `${fmt(start)}-${fmt(end)}`
  }
  return '10-22'
}

async function sendInvite(h) {
  if (!locationId.value || !helpers.value.date || cellSaving.value) return
  cellSaving.value = true
  try {
    await api.schedule.sendInvite({
      userId: Number(h.id),
      locationId: locationId.value,
      date: helpers.value.date,
      value: availabilityToValue(h),
    })
    await loadGrid()
    helpers.value.open = false
    // Убираем из списка того, кому отправили инвайт (опционально — бэкенд уже не вернёт его в helpers при повторном открытии)
    helpers.value.items = helpers.value.items.filter((x) => x.id !== h.id)
  } catch (e) {
    gridError.value = e.data?.message || e.message || 'Ошибка отправки инвайта'
  } finally {
    cellSaving.value = false
  }
}

async function loadLocations() {
  try {
    const res = await api.schedule.locations()
    locations.value = res.locations || []
    canEditSchedule.value = !!res.canEditSchedule
    if (locations.value.length && locationId.value == null) {
      locationId.value = res.defaultLocationId ?? locations.value[0].id
    }
  } catch (e) {
    gridError.value = e.data?.message || e.message || 'Ошибка загрузки точек'
  }
}

async function loadGrid() {
  if (!locationId.value) return
  gridError.value = ''
  loading.value = true
  try {
    const res = await api.schedule.grid({
      location_id: locationId.value,
      period: period.value,
      date: date.value,
    })
    grid.value = res
  } catch (e) {
    gridError.value = e.data?.message || e.message || 'Ошибка загрузки графика'
    grid.value = null
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadLocations()
  loadGrid()
})
watch([locationId, period, date], loadGrid)
</script>
