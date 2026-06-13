<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <router-link to="/admin" class="text-gray-500 hover:text-gray-700">← Админ-панель</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Push-рассылка</h1>
    </div>

    <p v-if="error" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">{{ error }}</p>
    <p v-if="successMessage" class="p-4 rounded-lg bg-green-50 text-green-700 mb-4">{{ successMessage }}</p>

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ stats.subscriptionsCount }}</div>
        <div class="text-sm text-gray-500 mt-1">Активных подписок</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-2xl font-bold text-gray-800">{{ stats.usersCount }}</div>
        <div class="text-sm text-gray-500 mt-1">Пользователей с подпиской</div>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Режим отправки</label>
        <select v-model="form.mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
          <option value="self">Тест: только мне (админу)</option>
          <option value="all">Рассылка: всем подписанным</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок *</label>
        <input
          v-model="form.title"
          type="text"
          required
          maxlength="120"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Текст уведомления *</label>
        <textarea
          v-model="form.body"
          rows="4"
          required
          maxlength="300"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ссылка при клике (опционально)</label>
        <input
          v-model="form.url"
          type="text"
          placeholder="/learning или https://dbthub.ru/learning"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>

      <div class="flex items-center gap-2">
        <input id="requireInteraction" v-model="form.requireInteraction" type="checkbox" class="rounded border-gray-300" />
        <label for="requireInteraction" class="text-sm text-gray-700">Не скрывать автоматически (требовать действие)</label>
      </div>

      <div class="pt-2">
        <button type="submit" :disabled="sending" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50">
          {{ sending ? 'Отправка...' : form.mode === 'all' ? 'Отправить всем' : 'Отправить тест' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { sanitizePushPath } from '@/utils/security'

const stats = ref({ subscriptionsCount: 0, usersCount: 0 })
const sending = ref(false)
const error = ref('')
const successMessage = ref('')
const form = reactive({
  mode: 'self',
  title: 'DBT уведомление',
  body: '',
  url: '',
  requireInteraction: false,
})

async function loadStats() {
  const { api } = await import('@/api/client')
  const res = await api.admin.pushStats()
  stats.value = {
    subscriptionsCount: res.subscriptionsCount || 0,
    usersCount: res.usersCount || 0,
  }
}

async function submit() {
  error.value = ''
  successMessage.value = ''
  sending.value = true
  try {
    const { api } = await import('@/api/client')
    const safeUrl = sanitizePushPath(form.url)
    if (form.url.trim() && !safeUrl) {
      throw new Error('Разрешены только внутренние пути приложения, например /learning')
    }
    const res = await api.admin.pushSend({
      mode: form.mode,
      title: form.title.trim(),
      body: form.body.trim(),
      url: safeUrl || undefined,
      requireInteraction: form.requireInteraction,
      tag: form.mode === 'all' ? 'admin-broadcast' : 'admin-test',
    })
    const r = res.result || {}
    successMessage.value = `Готово. Очередь: ${r.queued || 0}, отправлено: ${r.sent || 0}, ошибок: ${r.failed || 0}, удалено просроченных: ${r.removed || 0}.`
    await loadStats()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка отправки push-уведомления'
  } finally {
    sending.value = false
  }
}

onMounted(async () => {
  try {
    await loadStats()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки статистики push'
  }
})
</script>
