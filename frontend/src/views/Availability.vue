<template>
  <div class="max-w-full mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Карта возможностей</h1>

    <div class="flex flex-wrap items-center gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Месяц</label>
        <select
          v-model="month"
          class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary/20 focus:border-primary"
        >
          <option v-for="m in 12" :key="m" :value="m">{{ monthName(m) }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Год</label>
        <input
          v-model.number="year"
          type="number"
          min="2020"
          max="2030"
          class="rounded-lg border border-gray-300 px-3 py-2 w-24 focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>
      <div v-if="canTeamView" class="flex rounded-lg border border-gray-200 overflow-hidden text-sm">
        <button
          type="button"
          class="px-4 py-2 transition-colors"
          :class="activeTab === 'my' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          @click="setTab('my')"
        >
          Моя карта
        </button>
        <button
          type="button"
          class="px-4 py-2 transition-colors border-l border-gray-200"
          :class="activeTab === 'team' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          @click="setTab('team')"
        >
          По точкам
        </button>
      </div>
      <div v-if="activeTab === 'team' && canFilterAllLocations && teamData?.locations?.length > 1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
        <select
          v-model="teamLocationFilter"
          class="rounded-lg border border-gray-300 px-3 py-2 min-w-[12rem] focus:ring-2 focus:ring-primary/20 focus:border-primary"
          @change="loadTeam"
        >
          <option value="__all__">Все точки территории</option>
          <option v-for="loc in teamData.locations" :key="loc.id" :value="String(loc.id)">{{ loc.name }}</option>
        </select>
      </div>
    </div>

    <p v-if="activeTab === 'my'" class="text-sm text-gray-600 mb-4">
      Укажите желаемое время работы по дням (например, 9-21 или 9.5-21.5). Оставьте пустым для выходного.
    </p>
    <p v-else-if="canTeamView" class="text-sm text-gray-600 mb-4">
      Таблица в том же формате, что график смен: по строкам — сотрудники точки, по столбцам — дни месяца.
      <span v-if="gridLegend" class="text-gray-500"> {{ gridLegend }}</span>
    </p>

    <!-- Моя карта -->
    <template v-if="activeTab === 'my'">
      <div v-if="loadingMy" class="py-8 text-center text-gray-500">Загрузка...</div>
      <div v-else-if="errorMy" class="p-4 rounded-lg bg-red-50 text-red-700">{{ errorMy }}</div>
      <div v-else-if="myData" class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full min-w-[600px] text-sm">
          <thead>
            <tr class="bg-gray-100 border-b border-gray-200">
              <th class="text-left p-3 font-semibold text-gray-800 sticky left-0 bg-gray-100 z-10 min-w-[180px]">
                Сотрудник
              </th>
              <th
                v-for="d in dayRange"
                :key="'h-' + d"
                class="p-2 text-center font-medium whitespace-nowrap min-w-[3.25rem]"
                :class="isHoliday(dateKey(d)) ? 'schedule-cell-holiday schedule-date-cell' : 'text-gray-700'"
                :title="isHoliday(dateKey(d)) ? 'Официальный праздник РФ' : ''"
              >
                <span class="block">{{ d }}</span>
                <span class="block text-xs font-normal opacity-80">{{ weekdayShort(d) }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-100">
              <td class="p-3 sticky left-0 z-10 bg-white border-r border-gray-100">
                <span class="font-medium text-gray-800">{{ currentUser?.fullName || 'Вы' }}</span>
                <span v-if="currentUser?.positionLabel" class="block text-xs text-gray-500">{{ currentUser.positionLabel }}</span>
              </td>
              <td
                v-for="d in dayRange"
                :key="'c-' + d"
                class="p-1 text-center align-middle border-l border-gray-50"
                :class="{ 'schedule-cell-holiday': isHoliday(dateKey(d)) }"
              >
                <input
                  type="text"
                  :value="cellValue(d)"
                  placeholder="9-21"
                  class="w-full max-w-[4.5rem] mx-auto px-1.5 py-1.5 text-center text-xs rounded-md border border-gray-300 focus:ring-2 focus:ring-primary/30 focus:border-primary"
                  @blur="onCellBlur(d, $event)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Команда по точкам -->
    <template v-else>
      <div v-if="loadingTeam" class="py-8 text-center text-gray-500">Загрузка...</div>
      <div v-else-if="errorTeam" class="p-4 rounded-lg bg-red-50 text-red-700">{{ errorTeam }}</div>
      <div v-else-if="teamData" class="space-y-8">
        <section
          v-for="(section, idx) in teamData.sections"
          :key="section.location.id"
          class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
        >
          <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-800">
              {{ sectionTitle(section, idx) }}
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ section.location.name }}</p>
          </div>
          <div v-if="!section.employees?.length" class="p-4 text-sm text-gray-500">Нет сотрудников в этой точке.</div>
          <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[600px] text-sm">
              <thead>
                <tr class="bg-gray-100 border-b border-gray-200">
                  <th class="text-left p-3 font-semibold text-gray-800 sticky left-0 bg-gray-100 z-10 min-w-[180px]">
                    Сотрудник
                  </th>
                  <th
                    v-for="d in teamDayRange"
                    :key="'th-' + section.location.id + '-' + d"
                    class="p-2 text-center font-medium whitespace-nowrap min-w-[3.25rem]"
                    :class="isHoliday(teamDateKey(d)) ? 'schedule-cell-holiday schedule-date-cell' : 'text-gray-700'"
                    :title="isHoliday(teamDateKey(d)) ? 'Официальный праздник РФ' : ''"
                  >
                    <span class="block">{{ d }}</span>
                    <span class="block text-xs font-normal opacity-80">{{ teamWeekdayShort(d) }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="emp in section.employees"
                  :key="emp.id"
                  class="border-b border-gray-100 hover:bg-gray-50/50"
                >
                  <td class="p-3 sticky left-0 z-10 bg-white border-r border-gray-100">
                    <span class="font-medium text-gray-800">{{ emp.fullName }}</span>
                    <span v-if="emp.positionLabel" class="block text-xs text-gray-500">{{ emp.positionLabel }}</span>
                  </td>
                  <td
                    v-for="d in teamDayRange"
                    :key="emp.id + '-' + d"
                    class="p-2 text-center text-gray-800 align-middle border-l border-gray-50 text-xs"
                    :class="{ 'schedule-cell-holiday': isHoliday(teamDateKey(d)) }"
                  >
                    {{ teamCellText(section, emp.id, d) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
        <p v-if="!teamData.sections?.length" class="text-gray-500 text-sm">Нет доступных точек.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from '@/api/client'
import { getCurrentUser } from '@/router'
import { isHoliday } from '@/utils/holidays'

const month = ref(new Date().getMonth() + 1)
const year = ref(new Date().getFullYear())
const currentUser = ref(null)

const activeTab = ref('my')
const teamLocationFilter = ref('__all__')

const myData = ref(null)
const loadingMy = ref(false)
const errorMy = ref('')

const teamData = ref(null)
const loadingTeam = ref(false)
const errorTeam = ref('')

const canTeamView = computed(() => {
  const u = currentUser.value
  if (!u) return false
  return !!u.isAdmin || u.position === 'manager' || u.position === 'location_manager'
})

const canFilterAllLocations = computed(() => {
  const u = currentUser.value
  if (!u) return false
  return !!u.isAdmin || u.position === 'manager'
})

const gridLegend = computed(() => {
  if (teamData.value?.dates?.some((d) => isHoliday(d))) {
    return 'Красный столбец — официальный праздник РФ.'
  }
  return ''
})

const dayRange = computed(() => {
  const n = myData.value?.lastDay
  if (!n) return []
  const out = []
  for (let d = 1; d <= n; d++) out.push(d)
  return out
})

const teamDayRange = computed(() => {
  const n = teamData.value?.lastDay
  if (!n) return []
  const out = []
  for (let d = 1; d <= n; d++) out.push(d)
  return out
})

function monthName(m) {
  const names = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь']
  return names[m - 1] || ''
}

function dateKey(d) {
  return `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
}

function teamDateKey(d) {
  if (teamData.value?.dates?.[d - 1]) return teamData.value.dates[d - 1]
  return dateKey(d)
}

function weekdayShort(d) {
  const map = myData.value?.daysOfMonth
  const names = myData.value?.weekdayNames
  if (!map || !names) return ''
  return names[map[d]] || ''
}

function teamWeekdayShort(d) {
  const map = teamData.value?.daysOfMonth
  const names = teamData.value?.weekdayNames
  if (!map || !names) return ''
  return names[map[d]] || ''
}

function cellValue(d) {
  if (!myData.value?.byDate) return ''
  const key = dateKey(d)
  const cell = myData.value.byDate[key]
  return cell && cell.text ? cell.text : ''
}

function teamCellText(section, userId, day) {
  const dateStr = teamDateKey(day)
  const byUser = section.byUserByDate?.[String(userId)] || section.byUserByDate?.[userId]
  const t = byUser?.[dateStr]
  return t || '—'
}

function sectionTitle(section, idx) {
  const multi = teamData.value?.sections?.length > 1
  if (multi) return `Точка ${idx + 1}`
  return 'Карта возможностей'
}

function setTab(tab) {
  activeTab.value = tab
  if (tab === 'team' && canTeamView.value && !teamData.value && !loadingTeam.value) {
    loadTeam()
  }
}

async function loadMy() {
  loadingMy.value = true
  errorMy.value = ''
  try {
    myData.value = await api.availability.my({ month: month.value, year: year.value })
  } catch (e) {
    errorMy.value = e.data?.message || e.message || 'Ошибка загрузки'
    myData.value = null
  } finally {
    loadingMy.value = false
  }
}

function teamRequestParams() {
  const params = { month: month.value, year: year.value }
  if (canFilterAllLocations.value) {
    if (teamLocationFilter.value === '__all__') {
      params.location_id = ''
    } else if (teamLocationFilter.value) {
      params.location_id = teamLocationFilter.value
    }
  }
  return params
}

async function loadTeam() {
  if (!canTeamView.value) return
  loadingTeam.value = true
  errorTeam.value = ''
  try {
    teamData.value = await api.availability.team(teamRequestParams())
  } catch (e) {
    errorTeam.value = e.data?.message || e.message || 'Ошибка загрузки'
    teamData.value = null
  } finally {
    loadingTeam.value = false
  }
}

async function onCellBlur(day, ev) {
  const value = (ev.target.value || '').trim()
  const date = dateKey(day)
  try {
    await api.availability.updateCell({ date, value })
    if (myData.value && myData.value.byDate) {
      if (value === '' || value === '0') {
        myData.value.byDate[date] = null
      } else {
        myData.value.byDate[date] = { text: value, timeStart: null, timeEnd: null }
      }
    }
  } catch (e) {
    errorMy.value = e.data?.message || e.message || 'Ошибка сохранения'
  }
}

onMounted(async () => {
  currentUser.value = await getCurrentUser()
  if (canTeamView.value) {
    activeTab.value = 'team'
  }
  await loadMy()
  if (activeTab.value === 'team') {
    await loadTeam()
  }
})

watch([month, year], () => {
  loadMy()
  if (activeTab.value === 'team' && canTeamView.value) {
    loadTeam()
  }
})
</script>
