<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-6">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Технологические карты</h1>
    </div>
    <form @submit.prevent="applyFilters" class="flex flex-wrap gap-2 mb-6">
      <input
        v-model="search"
        type="text"
        placeholder="Поиск по названию, описанию, категории"
        class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
      />
      <select
        v-model="category"
        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
      >
        <option value="">Все категории</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">Найти</button>
      <button type="button" @click="resetFilters" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Сбросить</button>
    </form>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="card in cards"
        :key="card.id"
        :to="{ name: 'GuruCardView', params: { id: card.id } }"
        class="block bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:border-primary hover:shadow-md transition"
      >
        <img v-if="card.image" :src="card.image" alt="" class="w-full h-36 object-cover" />
        <div class="p-4">
          <div class="font-semibold text-gray-800">{{ card.title }}</div>
          <span v-if="card.category" class="inline-block mt-1 px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-sm">{{ card.category }}</span>
          <p v-if="card.prepTime" class="text-sm text-gray-500 mt-1">Время: {{ card.prepTime }} мин</p>
          <span class="inline-block mt-2 text-sm text-primary font-medium">Открыть →</span>
        </div>
      </router-link>
    </div>
    <p v-if="!loading && !error && (!cards || cards.length === 0)" class="text-gray-500 py-6">Нет карточек по заданным критериям.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const cards = ref([])
const categories = ref([])
const search = ref(route.query.search || '')
const category = ref(route.query.category || '')
const loading = ref(true)
const error = ref('')

function applyFilters() {
  router.replace({ path: '/learning/cards', query: search.value || category.value ? { search: search.value || undefined, category: category.value || undefined } : {} })
  loadCards()
}

function resetFilters() {
  search.value = ''
  category.value = ''
  router.replace({ path: '/learning/cards' })
  loadCards()
}

async function loadCards() {
  loading.value = true
  error.value = ''
  try {
    const { api } = await import('@/api/client')
    const params = {}
    if (search.value) params.search = search.value
    if (category.value) params.category = category.value
    const res = await api.guru.cards(params)
    cards.value = res.cards || []
    categories.value = res.categories || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCards()
})
</script>
