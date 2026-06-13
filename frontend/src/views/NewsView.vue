<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <router-link to="/news" class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 mb-4 text-sm">
      ← К списку уведомлений
    </router-link>
    <div v-if="loading" class="py-12 text-center text-gray-500">Загрузка...</div>
    <div v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</div>
    <article v-else-if="item" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <header class="p-6 pb-4 border-b border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900">{{ item.title }}</h1>
        <p class="mt-2 text-sm text-gray-500">
          {{ formatDate(item.publishedAt || item.createdAt) }}
        </p>
      </header>
      <div
        class="news-body p-6 prose prose-gray max-w-none"
        v-html="sanitizedBody"
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
const item = ref(null)
const loading = ref(true)
const error = ref('')

const id = computed(() => route.params.id)
const sanitizedBody = computed(() => sanitizeHtml(item.value?.body))

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
    const res = await api.news.get(id.value)
    item.value = res
  } catch (e) {
    error.value = e.data?.message || e.message || 'Новость не найдена'
    item.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(id, load)
</script>

<style scoped>
.news-body :deep(p) {
  @apply mb-4 text-gray-700 leading-relaxed;
}
.news-body :deep(p:last-child) {
  @apply mb-0;
}
.news-body :deep(img) {
  @apply max-w-full h-auto rounded-lg my-4 block;
}
.news-body :deep(h2) {
  @apply text-xl font-semibold text-gray-900 mt-6 mb-2;
}
.news-body :deep(h3) {
  @apply text-lg font-medium text-gray-800 mt-4 mb-2;
}
</style>
