<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <router-link to="/learning/lessons" class="text-gray-500 hover:text-gray-700">← Уроки</router-link>
      <router-link v-if="lesson?.isAdmin" :to="{ name: 'GuruLessonEdit', params: { id: lesson.id } }" class="text-primary font-medium hover:underline">
        Редактировать
      </router-link>
    </div>
    <div v-if="loading" class="py-12 text-center text-gray-500">Загрузка...</div>
    <div v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</div>
    <article v-else-if="lesson" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <header class="p-6 pb-4 border-b border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900">{{ lesson.title }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500">
          <span v-if="lesson.category" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ lesson.category }}</span>
          <span>{{ formatDate(lesson.updatedAt || lesson.createdAt) }}</span>
        </div>
        <router-link
          v-if="lesson.testId"
          :to="{ name: 'GuruTestView', params: { id: lesson.testId } }"
          class="inline-block mt-2 text-sm text-primary font-medium hover:underline"
        >
          Связанный тест →
        </router-link>
      </header>
      <div
        class="lesson-content p-6 prose prose-gray max-w-none"
        v-html="sanitizedContent"
      />
    </article>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/client'
import { sanitizeHtml } from '@/utils/security'

const route = useRoute()
const lesson = ref(null)
const loading = ref(true)
const error = ref('')
const id = computed(() => route.params.id)
const sanitizedContent = computed(() => sanitizeHtml(lesson.value?.content))

function formatDate(val) {
  if (!val) return ''
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load() {
  if (!id.value) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.guru.lesson(id.value)
    lesson.value = res
    api.guru.lessonMarkRead(id.value).catch(() => {})
  } catch (e) {
    error.value = e.data?.message || e.message || 'Урок не найден'
    lesson.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(id, load)
</script>

<style scoped>
.lesson-content :deep(p) {
  @apply mb-4 text-gray-700 leading-relaxed;
}
.lesson-content :deep(p:last-child) {
  @apply mb-0;
}
.lesson-content :deep(img) {
  @apply max-w-full h-auto rounded-lg my-4 block;
}
.lesson-content :deep(h2) {
  @apply text-xl font-semibold text-gray-900 mt-6 mb-2;
}
.lesson-content :deep(h3) {
  @apply text-lg font-semibold text-gray-800 mt-4 mb-2;
}
.lesson-content :deep(ul) {
  @apply list-disc pl-6 my-3 space-y-1;
}
.lesson-content :deep(ol) {
  @apply list-decimal pl-6 my-3 space-y-1;
}
.lesson-content :deep(li) {
  @apply text-gray-700;
}
</style>
