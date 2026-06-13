<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-4">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link to="/learning/tests" class="text-gray-500 hover:text-gray-700">Тесты</router-link>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else-if="result" class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
      <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ result.testTitle }}</h1>
      <p class="text-lg mb-4" :class="result.passed ? 'text-green-600' : 'text-red-600'">
        {{ result.passed ? 'Поздравляем! Тест сдан.' : 'Тест не сдан. Попробуйте ещё раз.' }}
      </p>
      <p class="text-gray-600 mb-1">Набрано баллов: <strong>{{ result.pointsEarned }} / {{ result.pointsMax }}</strong></p>
      <p class="text-gray-600 mb-1">Процент: <strong>{{ result.score }}%</strong></p>
      <p v-if="result.timeSpent" class="text-gray-600 mb-1">Время: <strong>{{ formatTime(result.timeSpent) }}</strong></p>
      <p v-if="result.finishedAt" class="text-sm text-gray-500 mt-2">{{ formatDate(result.finishedAt) }}</p>
      <div class="mt-6 flex flex-wrap justify-center gap-2">
        <router-link to="/learning/tests" class="inline-flex px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">
          К списку тестов
        </router-link>
        <router-link
          :to="{ name: 'GuruTestLeaderboard', params: { id: result.testId } }"
          class="inline-flex px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50"
        >
          Таблица лидеров
        </router-link>
        <router-link
          :to="{ name: 'GuruTestView', params: { id: result.testId } }"
          class="inline-flex px-4 py-2 rounded-lg border border-primary text-primary font-medium hover:bg-primary/5"
        >
          Пройти снова
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const result = ref(null)
const loading = ref(true)
const error = ref('')

function formatTime(sec) {
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

function formatDate(str) {
  if (!str) return ''
  const d = new Date(str)
  return d.toLocaleString('ru-RU')
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.result(route.params.id)
    result.value = res
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
