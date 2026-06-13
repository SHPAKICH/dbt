<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-4">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link to="/learning/tests" class="text-gray-500 hover:text-gray-700">Тесты</router-link>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else-if="test" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <img v-if="test.image" :src="test.image" alt="" class="w-full object-cover max-h-48" />
      <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ test.title }}</h1>
        <span v-if="test.category" class="inline-block mt-2 px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-sm">{{ test.category }}</span>
        <p v-if="test.description" class="mt-3 text-gray-600 whitespace-pre-line">{{ test.description }}</p>
        <ul class="mt-4 space-y-1 text-gray-600">
          <li>Вопросов: <strong>{{ test.questionsCount }}</strong></li>
          <li>Максимум баллов: <strong>{{ test.maxPoints }}</strong></li>
          <li>Проходной балл: <strong>{{ test.passScore }}%</strong></li>
          <li v-if="test.timeLimit">Лимит времени на тест: <strong>{{ test.timeLimit }} сек</strong></li>
          <li v-if="test.questionTimeLimit">Лимит на вопрос: <strong>{{ test.questionTimeLimit }} сек</strong></li>
        </ul>
        <div class="mt-6 flex flex-wrap gap-2">
          <router-link
            v-if="test.questionsCount > 0"
            :to="{ name: 'GuruTestTake', params: { id: test.id } }"
            class="inline-flex items-center px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90"
          >
            Начать тест
          </router-link>
          <router-link
            :to="{ name: 'GuruTestLeaderboard', params: { id: test.id } }"
            class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50"
          >
            Таблица лидеров
          </router-link>
          <router-link
            v-if="test.isAdmin"
            :to="{ name: 'GuruTestResults', params: { id: test.id } }"
            class="inline-flex items-center px-4 py-2 rounded-lg border border-blue-300 text-blue-700 font-medium hover:bg-blue-50"
          >
            Все результаты
          </router-link>
          <router-link
            v-if="test.isAdmin"
            :to="{ name: 'GuruTestEdit', params: { id: test.id } }"
            class="inline-flex items-center px-4 py-2 rounded-lg border border-amber-300 text-amber-700 font-medium hover:bg-amber-50"
          >
            Редактировать
          </router-link>
          <button
            v-if="test.isAdmin"
            type="button"
            class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 text-red-700 font-medium hover:bg-red-50"
            @click="confirmDelete"
          >
            Удалить
          </button>
        </div>

        <div v-if="leaderboard.length >= 0" class="mt-8 pt-6 border-t border-gray-200">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Таблица лидеров (превью)</h2>
          <div v-if="leaderboardLoading" class="text-sm text-gray-500">Загрузка...</div>
          <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="px-3 py-2 font-medium text-gray-600">#</th>
                  <th class="px-3 py-2 font-medium text-gray-600">Сотрудник</th>
                  <th class="px-3 py-2 font-medium text-gray-600">Балл</th>
                  <th class="px-3 py-2 font-medium text-gray-600">Дата</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in leaderboard" :key="row.rank" class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-800">{{ row.rank }}</td>
                  <td class="px-3 py-2 text-gray-800">{{ row.userName }}</td>
                  <td class="px-3 py-2 text-gray-800">{{ row.score }}%</td>
                  <td class="px-3 py-2 text-gray-600">{{ formatDate(row.finishedAt) }}</td>
                </tr>
              </tbody>
            </table>
            <p v-if="leaderboard.length === 0 && !leaderboardLoading" class="p-3 text-gray-500 text-sm">Пока нет результатов.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const test = ref(null)
const loading = ref(true)
const error = ref('')
const leaderboard = ref([])
const leaderboardLoading = ref(false)

function formatDate(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function loadLeaderboard() {
  if (!test.value?.id) return
  leaderboardLoading.value = true
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.testLeaderboard(test.value.id)
    leaderboard.value = res.leaders || []
  } catch (_) {
    leaderboard.value = []
  } finally {
    leaderboardLoading.value = false
  }
}

async function confirmDelete() {
  if (!test.value?.isAdmin || !confirm(`Удалить тест «${test.value.title}»? Тест, все вопросы и результаты будут удалены.`)) return
  try {
    const { api } = await import('@/api/client')
    await api.guru.deleteTest(test.value.id)
    router.push({ name: 'GuruTestsList' })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка удаления'
  }
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.test(route.params.id)
    test.value = res
    await loadLeaderboard()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
watch(() => route.params.id, () => {
  if (test.value?.id === route.params.id) loadLeaderboard()
})
</script>
