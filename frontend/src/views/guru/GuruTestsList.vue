<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-6">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Уроки и тесты</h1>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="test in tests"
        :key="test.id"
        :to="{ name: 'GuruTestView', params: { id: test.id } }"
        class="block bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:border-primary hover:shadow-md transition"
      >
        <div class="font-semibold text-gray-800">{{ test.title }}</div>
        <p v-if="test.description" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ test.description }}</p>
        <span class="inline-block mt-2 text-sm text-primary font-medium">Открыть →</span>
      </router-link>
    </div>
    <p v-if="!loading && !error && (!tests || tests.length === 0)" class="text-gray-500 py-6">Нет доступных тестов.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const tests = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.tests()
    tests.value = res.tests || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
