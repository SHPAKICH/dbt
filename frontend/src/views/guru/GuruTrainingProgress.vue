<template>
  <div class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-6">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Прогресс обучения</h1>
    </div>

    <div v-if="loading" class="py-12 text-center text-gray-500">Загрузка...</div>
    <div v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</div>
    <template v-else>
      <div class="flex flex-wrap items-end gap-4 mb-6">
        <div v-if="locations.length > 1" class="flex flex-col gap-1">
          <label class="text-sm font-medium text-gray-600">Точка</label>
          <select v-model="filterLocation" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Все точки</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium text-gray-600">Поиск</label>
          <input v-model="search" type="text" placeholder="Имя сотрудника..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <div class="text-3xl font-bold text-gray-800">{{ filteredUsers.length }}</div>
          <div class="text-sm text-gray-500 mt-1">Сотрудников</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <div class="text-3xl font-bold text-blue-600">{{ totalTests }}</div>
          <div class="text-sm text-gray-500 mt-1">Тестов</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <div class="text-3xl font-bold text-emerald-600">{{ totalLessons }}</div>
          <div class="text-sm text-gray-500 mt-1">Уроков</div>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 font-semibold text-gray-600 cursor-pointer select-none whitespace-nowrap" @click="toggleSort('fullName')">
                  Сотрудник {{ sortIcon('fullName') }}
                </th>
                <th class="px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Должность</th>
                <th class="px-4 py-3 font-semibold text-gray-600 whitespace-nowrap">Точка</th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center cursor-pointer select-none whitespace-nowrap" @click="toggleSort('testsPassed')">
                  Тесты {{ sortIcon('testsPassed') }}
                </th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center cursor-pointer select-none whitespace-nowrap" @click="toggleSort('avgScore')">
                  Ср. балл {{ sortIcon('avgScore') }}
                </th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center cursor-pointer select-none whitespace-nowrap" @click="toggleSort('lessonsCompleted')">
                  Уроки {{ sortIcon('lessonsCompleted') }}
                </th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center cursor-pointer select-none whitespace-nowrap" @click="toggleSort('lastActivity')">
                  Последняя активность {{ sortIcon('lastActivity') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">Нет данных</td>
              </tr>
              <tr v-for="u in filteredUsers" :key="u.userId" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 font-medium text-gray-800">{{ u.fullName }}</td>
                <td class="px-4 py-3 text-gray-600">{{ u.position }}</td>
                <td class="px-4 py-3 text-gray-600">{{ u.location }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="u.testsPassed === u.testsTotal && u.testsTotal > 0 ? 'text-emerald-600 font-semibold' : 'text-gray-800'">
                    {{ u.testsPassed }}/{{ u.testsTotal }}
                  </span>
                  <div class="mt-1 w-full bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all" :class="testBarColor(u)" :style="{ width: testPercent(u) + '%' }"></div>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="u.avgScore != null" :class="scoreClass(u.avgScore)">{{ u.avgScore }}%</span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="u.lessonsCompleted === u.lessonsTotal && u.lessonsTotal > 0 ? 'text-emerald-600 font-semibold' : 'text-gray-800'">
                    {{ u.lessonsCompleted }}/{{ u.lessonsTotal }}
                  </span>
                  <div class="mt-1 w-full bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all" :class="lessonBarColor(u)" :style="{ width: lessonPercent(u) + '%' }"></div>
                  </div>
                </td>
                <td class="px-4 py-3 text-center text-gray-500 text-xs whitespace-nowrap">
                  {{ u.lastActivity ? formatDate(u.lastActivity) : '—' }}
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
import { ref, computed, onMounted } from 'vue'
import { api } from '@/api/client'

const loading = ref(true)
const error = ref('')
const users = ref([])
const locations = ref([])
const totalTests = ref(0)
const totalLessons = ref(0)
const filterLocation = ref('')
const search = ref('')
const sortField = ref('fullName')
const sortDir = ref(1)

function toggleSort(field) {
  if (sortField.value === field) {
    sortDir.value *= -1
  } else {
    sortField.value = field
    sortDir.value = 1
  }
}

function sortIcon(field) {
  if (sortField.value !== field) return ''
  return sortDir.value === 1 ? '↑' : '↓'
}

const filteredUsers = computed(() => {
  let list = users.value
  if (filterLocation.value) {
    const locName = locations.value.find(l => l.id === Number(filterLocation.value))?.name
    if (locName) list = list.filter(u => u.location === locName)
  }
  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase()
    list = list.filter(u => u.fullName.toLowerCase().includes(q))
  }
  const field = sortField.value
  const dir = sortDir.value
  return [...list].sort((a, b) => {
    let va = a[field], vb = b[field]
    if (va == null) va = field === 'lastActivity' ? '' : -1
    if (vb == null) vb = field === 'lastActivity' ? '' : -1
    if (typeof va === 'string') return va.localeCompare(vb, 'ru') * dir
    return (va - vb) * dir
  })
})

function testPercent(u) {
  return u.testsTotal > 0 ? Math.round((u.testsPassed / u.testsTotal) * 100) : 0
}
function lessonPercent(u) {
  return u.lessonsTotal > 0 ? Math.round((u.lessonsCompleted / u.lessonsTotal) * 100) : 0
}
function testBarColor(u) {
  const p = testPercent(u)
  if (p === 100) return 'bg-emerald-500'
  if (p >= 50) return 'bg-blue-500'
  return 'bg-gray-400'
}
function lessonBarColor(u) {
  const p = lessonPercent(u)
  if (p === 100) return 'bg-emerald-500'
  if (p >= 50) return 'bg-blue-500'
  return 'bg-gray-400'
}
function scoreClass(score) {
  if (score >= 80) return 'text-emerald-600 font-semibold'
  if (score >= 50) return 'text-amber-600 font-semibold'
  return 'text-red-600 font-semibold'
}
function formatDate(val) {
  if (!val) return ''
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  try {
    const res = await api.guru.trainingProgress()
    users.value = res.users || []
    locations.value = res.locations || []
    totalTests.value = res.totalTests || 0
    totalLessons.value = res.totalLessons || 0
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
