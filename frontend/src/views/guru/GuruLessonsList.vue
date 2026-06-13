<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <div class="flex items-center gap-4">
        <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
        <h1 class="text-2xl font-bold text-gray-800">Уроки</h1>
      </div>
      <router-link
        v-if="isAdmin"
        to="/learning/lessons/create"
        class="inline-flex items-center gap-2 rounded-lg bg-green-100 hover:bg-green-200 px-4 py-2 text-green-800 font-medium"
      >
        ➕ Создать урок
      </router-link>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
      <input
        v-model="search"
        type="search"
        placeholder="Поиск по урокам..."
        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary flex-1 min-w-[200px]"
      />
      <select
        v-model="category"
        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 bg-white"
      >
        <option value="">Все категории</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="lesson in lessons"
        :key="lesson.id"
        :to="{ name: 'GuruLessonView', params: { id: lesson.id } }"
        class="block bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:border-primary hover:shadow-md transition"
      >
        <div class="font-semibold text-gray-800">{{ lesson.title }}</div>
        <p v-if="lesson.category" class="text-sm text-gray-500 mt-1">{{ lesson.category }}</p>
        <span class="inline-block mt-2 text-sm text-primary font-medium">Читать →</span>
      </router-link>
    </div>
    <p v-if="!loading && !error && (!lessons || lessons.length === 0)" class="text-gray-500 py-6">Нет доступных уроков.</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from '@/api/client'

const lessons = ref([])
const categories = ref([])
const isAdmin = ref(false)
const loading = ref(true)
const error = ref('')
const search = ref('')
const category = ref('')

const params = computed(() => {
  const p = {}
  if (search.value) p.search = search.value
  if (category.value) p.category = category.value
  return p
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [res, dash] = await Promise.all([
      api.guru.lessons(params.value),
      api.guru.dashboard().catch(() => ({})),
    ])
    lessons.value = res.lessons || []
    categories.value = res.categories || []
    isAdmin.value = !!dash.isAdmin
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch([search, category], load)
</script>
