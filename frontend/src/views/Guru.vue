<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <header class="rounded-2xl bg-gradient-to-br from-emerald-50 via-white to-teal-50 border border-emerald-100/80 p-6 sm:p-8 mb-6 shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Меню Гуру</h1>
          <p class="text-gray-600 mt-1.5 max-w-xl">
            Уроки, тесты и технологические карты — всё обучение и меню в одном месте.
          </p>
        </div>
        <router-link
          v-if="canSeeProgress"
          to="/learning/progress"
          class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-medium px-4 py-2.5 hover:bg-indigo-700 transition shadow-sm"
        >
          Прогресс сотрудников
        </router-link>
      </div>

      <div class="mt-5 relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input
          v-model="searchQuery"
          type="search"
          autocomplete="off"
          placeholder="Поиск по урокам, тестам и карточкам меню…"
          class="w-full pl-12 pr-10 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 shadow-sm"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
          aria-label="Очистить поиск"
          @click="clearSearch"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <p v-if="searchQuery.trim().length === 1" class="mt-2 text-xs text-gray-500">
        Введите ещё один символ для поиска
      </p>
    </header>

    <div v-if="loading" class="py-16 text-center text-gray-500">Загрузка...</div>
    <div v-else-if="error" class="p-4 rounded-xl bg-red-50 text-red-700 border border-red-100">{{ error }}</div>

    <template v-else-if="isSearchActive">
      <div v-if="searchLoading" class="py-12 text-center text-gray-500">Ищем…</div>
      <div v-else-if="searchError" class="p-4 rounded-xl bg-red-50 text-red-700">{{ searchError }}</div>
      <div v-else-if="!hasSearchResults" class="py-12 text-center">
        <p class="text-gray-600">По запросу «{{ searchQuery.trim() }}» ничего не найдено.</p>
        <button type="button" class="mt-3 text-sm text-emerald-700 font-medium hover:underline" @click="clearSearch">
          Сбросить поиск
        </button>
      </div>
      <div v-else class="space-y-8">
        <p class="text-sm text-gray-500">Найдено: {{ totalSearchResults }}</p>

        <section v-if="searchResults.lessons.length">
          <h2 class="text-base font-semibold text-gray-800 mb-3">📘 Уроки ({{ searchResults.lessons.length }})</h2>
          <div class="space-y-2">
            <router-link
              v-for="item in searchResults.lessons"
              :key="'sl-' + item.id"
              :to="{ name: 'GuruLessonView', params: { id: item.id } }"
              class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm hover:border-gray-300 hover:shadow transition"
            >
              <div class="min-w-0 flex-1">
                <div class="font-medium text-gray-900 truncate">{{ item.title }}</div>
                <div v-if="item.category" class="text-sm text-gray-500 truncate mt-0.5">{{ item.category }}</div>
              </div>
              <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Урок</span>
            </router-link>
          </div>
        </section>

        <section v-if="searchResults.tests.length">
          <h2 class="text-base font-semibold text-gray-800 mb-3">✅ Тесты ({{ searchResults.tests.length }})</h2>
          <div class="space-y-2">
            <router-link
              v-for="item in searchResults.tests"
              :key="'st-' + item.id"
              :to="{ name: 'GuruTestView', params: { id: item.id } }"
              class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm hover:border-gray-300 hover:shadow transition"
            >
              <div class="min-w-0 flex-1">
                <div class="font-medium text-gray-900 truncate">{{ item.title }}</div>
                <div v-if="item.description || item.category" class="text-sm text-gray-500 truncate mt-0.5">
                  {{ item.category || item.description }}
                </div>
              </div>
              <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">Тест</span>
            </router-link>
          </div>
        </section>

        <section v-if="searchResults.cards.length">
          <h2 class="text-base font-semibold text-gray-800 mb-3">🍵 Карточки ({{ searchResults.cards.length }})</h2>
          <div class="space-y-2">
            <router-link
              v-for="item in searchResults.cards"
              :key="'sc-' + item.id"
              :to="{ name: 'GuruCardView', params: { id: item.id } }"
              class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm hover:border-gray-300 hover:shadow transition"
            >
              <img
                v-if="item.image"
                :src="item.image"
                alt=""
                class="h-10 w-10 shrink-0 rounded-lg object-cover bg-gray-100"
              />
              <div class="min-w-0 flex-1">
                <div class="font-medium text-gray-900 truncate">{{ item.title }}</div>
                <div v-if="item.category" class="text-sm text-gray-500 truncate mt-0.5">{{ item.category }}</div>
              </div>
              <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Карточка</span>
            </router-link>
          </div>
        </section>
      </div>
    </template>

    <template v-else-if="dashboard">
      <nav class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8" aria-label="Разделы обучения">
        <router-link
          to="/learning/lessons"
          class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:border-emerald-300 hover:shadow-md transition"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-2xl group-hover:bg-emerald-200 transition">📘</span>
          <div class="min-w-0">
            <div class="font-semibold text-gray-900">Уроки</div>
            <div class="text-2xl font-bold text-emerald-700">{{ dashboard.lessonsCount ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Теория и материалы</div>
          </div>
        </router-link>
        <router-link
          to="/learning/tests"
          class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:border-blue-300 hover:shadow-md transition"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-2xl group-hover:bg-blue-200 transition">✅</span>
          <div class="min-w-0">
            <div class="font-semibold text-gray-900">Тесты</div>
            <div class="text-2xl font-bold text-blue-700">{{ testsTotal }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Проверка знаний</div>
          </div>
        </router-link>
        <router-link
          to="/learning/cards"
          class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:border-amber-300 hover:shadow-md transition"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-2xl group-hover:bg-amber-200 transition">🍵</span>
          <div class="min-w-0">
            <div class="font-semibold text-gray-900">Карточки</div>
            <div class="text-2xl font-bold text-amber-700">{{ dashboard.cardsCount ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Техкарты и меню</div>
          </div>
        </router-link>
      </nav>

      <section v-if="dashboard.isAdmin" class="mb-8 rounded-xl border border-dashed border-gray-200 bg-gray-50/80 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Создать контент</p>
        <div class="flex flex-wrap gap-2">
          <router-link
            to="/learning/lessons/create"
            class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-gray-200 px-3 py-2 text-sm font-medium text-gray-800 hover:bg-emerald-50 hover:border-emerald-200 transition"
          >
            + Урок
          </router-link>
          <router-link
            to="/learning/tests/create"
            class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-gray-200 px-3 py-2 text-sm font-medium text-gray-800 hover:bg-blue-50 hover:border-blue-200 transition"
          >
            + Тест
          </router-link>
          <router-link
            to="/learning/cards/create"
            class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-gray-200 px-3 py-2 text-sm font-medium text-gray-800 hover:bg-amber-50 hover:border-amber-200 transition"
          >
            + Карточка
          </router-link>
        </div>
      </section>

      <div class="grid lg:grid-cols-2 gap-8">
        <section>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-800">Уроки</h2>
            <router-link to="/learning/lessons" class="text-sm text-emerald-700 font-medium hover:underline">Все →</router-link>
          </div>
          <div v-if="dashboard.lessons?.length" class="space-y-2">
            <router-link
              v-for="lesson in dashboard.lessons"
              :key="'l-' + lesson.id"
              :to="{ name: 'GuruLessonView', params: { id: lesson.id } }"
              class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm hover:border-gray-300 hover:shadow transition"
            >
              <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Урок</span>
              <div class="min-w-0 flex-1">
                <div class="font-medium text-gray-900 truncate">{{ lesson.title }}</div>
                <div v-if="lesson.category" class="text-sm text-gray-500 truncate mt-0.5">{{ lesson.category }}</div>
              </div>
              <span class="shrink-0 text-gray-400 text-sm">→</span>
            </router-link>
          </div>
          <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-6 text-center">
            <p class="text-sm text-gray-500">Уроков пока нет</p>
            <router-link to="/learning/lessons" class="inline-block mt-2 text-sm text-emerald-700 font-medium hover:underline">Перейти к урокам</router-link>
          </div>
        </section>

        <section>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-800">Тесты</h2>
            <router-link to="/learning/tests" class="text-sm text-emerald-700 font-medium hover:underline">Все →</router-link>
          </div>
          <div v-if="dashboard.tests?.length" class="space-y-2">
            <router-link
              v-for="test in dashboard.tests"
              :key="'t-' + test.id"
              :to="{ name: 'GuruTestView', params: { id: test.id } }"
              class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm hover:border-gray-300 hover:shadow transition"
            >
              <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">Тест</span>
              <div class="min-w-0 flex-1">
                <div class="font-medium text-gray-900 truncate">{{ test.title }}</div>
                <div v-if="test.description" class="text-sm text-gray-500 truncate mt-0.5">{{ test.description }}</div>
              </div>
              <span class="shrink-0 text-gray-400 text-sm">→</span>
            </router-link>
          </div>
          <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-6 text-center">
            <p class="text-sm text-gray-500">Тестов пока нет</p>
            <router-link to="/learning/tests" class="inline-block mt-2 text-sm text-emerald-700 font-medium hover:underline">Перейти к тестам</router-link>
          </div>
        </section>
      </div>

      <section class="mt-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold text-gray-800">Технологические карты</h2>
          <router-link to="/learning/cards" class="text-sm text-emerald-700 font-medium hover:underline">Все →</router-link>
        </div>
        <div v-if="dashboard.cards?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <router-link
            v-for="card in dashboard.cards"
            :key="'c-' + card.id"
            :to="{ name: 'GuruCardView', params: { id: card.id } }"
            class="flex gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm hover:border-amber-300 hover:shadow-md transition"
          >
            <img
              v-if="card.image"
              :src="card.image"
              alt=""
              class="h-16 w-16 shrink-0 rounded-lg object-cover bg-gray-100"
            />
            <div v-else class="h-16 w-16 shrink-0 rounded-lg bg-amber-50 flex items-center justify-center text-2xl">🍵</div>
            <div class="min-w-0 flex-1">
              <div class="font-medium text-gray-900 truncate">{{ card.title }}</div>
              <div v-if="card.category" class="text-xs text-gray-500 mt-0.5 truncate">{{ card.category }}</div>
            </div>
          </router-link>
        </div>
        <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-6 text-center">
          <p class="text-sm text-gray-500">Карточек пока нет</p>
          <router-link to="/learning/cards" class="inline-block mt-2 text-sm text-emerald-700 font-medium hover:underline">Открыть каталог</router-link>
        </div>
      </section>

      <section v-if="dashboard.myResults?.length" class="mt-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Последние результаты тестов</h2>
        <ul class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100 overflow-hidden shadow-sm">
          <li
            v-for="r in dashboard.myResults"
            :key="r.id"
            class="flex items-center justify-between gap-4 px-4 py-3"
          >
            <span class="font-medium text-gray-800 truncate">{{ r.testTitle }}</span>
            <span
              class="shrink-0 text-sm font-medium px-2.5 py-0.5 rounded-full"
              :class="r.passed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
            >
              {{ r.passed ? 'Сдан' : 'Не сдан' }} · {{ r.score }}%
            </span>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const dashboard = ref(null)
const loading = ref(true)
const error = ref('')

const searchQuery = ref('')
const searchLoading = ref(false)
const searchError = ref('')
const searchResults = ref({ lessons: [], tests: [], cards: [] })
let searchTimer = null
let searchRequestId = 0

const isSearchActive = computed(() => searchQuery.value.trim().length >= 2)

const hasSearchResults = computed(() => {
  const r = searchResults.value
  return r.lessons.length > 0 || r.tests.length > 0 || r.cards.length > 0
})

const totalSearchResults = computed(() => {
  const r = searchResults.value
  return r.lessons.length + r.tests.length + r.cards.length
})

const testsTotal = computed(() => dashboard.value?.testsCount ?? dashboard.value?.tests?.length ?? 0)

const canSeeProgress = computed(() => {
  if (!dashboard.value) return false
  return dashboard.value.isAdmin || ['manager', 'location_manager'].includes(dashboard.value.position)
})

function clearSearch() {
  searchQuery.value = ''
  searchResults.value = { lessons: [], tests: [], cards: [] }
  searchError.value = ''
}

async function runSearch(q) {
  const trimmed = q.trim()
  if (trimmed.length < 2) {
    searchResults.value = { lessons: [], tests: [], cards: [] }
    searchError.value = ''
    return
  }
  const requestId = ++searchRequestId
  searchLoading.value = true
  searchError.value = ''
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.search(trimmed)
    if (requestId !== searchRequestId) return
    searchResults.value = {
      lessons: res.lessons || [],
      tests: res.tests || [],
      cards: res.cards || [],
    }
  } catch (e) {
    if (requestId !== searchRequestId) return
    searchError.value = e.data?.message || e.message || 'Ошибка поиска'
  } finally {
    if (requestId === searchRequestId) {
      searchLoading.value = false
    }
  }
}

watch(searchQuery, (val) => {
  clearTimeout(searchTimer)
  if (val.trim().length < 2) {
    searchResults.value = { lessons: [], tests: [], cards: [] }
    searchLoading.value = false
    return
  }
  searchTimer = setTimeout(() => runSearch(val), 300)
})

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.dashboard()
    dashboard.value = res
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
