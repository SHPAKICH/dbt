<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-4">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link :to="{ name: 'GuruTestView', params: { id: testId } }" class="text-gray-500 hover:text-gray-700">Тест</router-link>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <template v-else>
      <h1 class="text-2xl font-bold text-gray-800 mb-4">{{ testTitle }} — таблица лидеров</h1>
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-sm font-medium text-gray-600">#</th>
                <th class="px-4 py-3 text-sm font-medium text-gray-600">Сотрудник</th>
                <th class="px-4 py-3 text-sm font-medium text-gray-600">Балл (%)</th>
                <th class="px-4 py-3 text-sm font-medium text-gray-600">Баллы</th>
                <th class="px-4 py-3 text-sm font-medium text-gray-600">Время</th>
                <th class="px-4 py-3 text-sm font-medium text-gray-600">Дата</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in leaders"
                :key="row.rank"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="px-4 py-3 text-gray-800">{{ row.rank }}</td>
                <td class="px-4 py-3 text-gray-800">{{ row.userName }}</td>
                <td class="px-4 py-3 text-gray-800">{{ row.score }}%</td>
                <td class="px-4 py-3 text-gray-800">{{ row.pointsEarned }} / {{ row.pointsMax }}</td>
                <td class="px-4 py-3 text-gray-800">{{ row.timeSpent != null ? formatTime(row.timeSpent) : '—' }}</td>
                <td class="px-4 py-3 text-gray-600 text-sm">{{ formatDate(row.finishedAt) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="leaders.length === 0" class="p-6 text-gray-500 text-center">Пока нет результатов.</p>
      </div>
      <router-link
        :to="{ name: 'GuruTestView', params: { id: testId } }"
        class="inline-block mt-4 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
      >
        ← К тесту
      </router-link>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const testId = route.params.id
const testTitle = ref('')
const leaders = ref([])
const loading = ref(true)
const error = ref('')

function formatTime(sec) {
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

function formatDate(str) {
  if (!str) return '—'
  return new Date(str).toLocaleString('ru-RU')
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.testLeaderboard(testId)
    testTitle.value = res.testTitle || ''
    leaders.value = res.leaders || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
