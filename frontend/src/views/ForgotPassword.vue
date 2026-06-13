<template>
  <div class="min-h-screen flex items-center justify-center p-8 bg-white">
    <div class="w-full max-w-md">
      <router-link to="/login" class="text-sm text-primary hover:underline mb-6 inline-block">← Назад к входу</router-link>
      <h1 class="text-2xl font-bold text-gray-800 mb-1">Восстановление пароля</h1>
      <p class="text-gray-500 mb-6">Укажите email или телефон, привязанный к аккаунту</p>

      <div v-if="success" class="p-4 rounded-lg bg-green-50 text-green-800 text-sm mb-6">
        {{ success }}
        <p v-if="hint === 'telegram_not_linked'" class="mt-2 text-amber-800">
          Telegram не привязан. Привяжите его в настройках профиля или выберите восстановление по email.
        </p>
        <p v-if="hint === 'email_unavailable'" class="mt-2 text-amber-800">
          Для этого аккаунта нет рабочего email. Привяжите Telegram в настройках и повторите попытку.
        </p>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-5">
        <div v-if="error" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email или телефон</label>
          <input
            v-model="form.emailOrPhone"
            type="text"
            autocomplete="username"
            placeholder="Введите email или телефон"
            class="w-full h-12 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Способ восстановления</label>
          <div class="space-y-2">
            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer" :class="form.channel === 'auto' ? 'border-primary bg-primary/5' : 'border-gray-200'">
              <input v-model="form.channel" type="radio" value="auto" class="mt-1 text-primary" />
              <span>
                <span class="font-medium text-gray-800">Автоматически</span>
                <span class="block text-sm text-gray-500">Telegram, если привязан, иначе email</span>
              </span>
            </label>
            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer" :class="form.channel === 'email' ? 'border-primary bg-primary/5' : 'border-gray-200'">
              <input v-model="form.channel" type="radio" value="email" class="mt-1 text-primary" />
              <span>
                <span class="font-medium text-gray-800">По email</span>
                <span class="block text-sm text-gray-500">Ссылка на почту</span>
              </span>
            </label>
            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer" :class="form.channel === 'telegram' ? 'border-primary bg-primary/5' : 'border-gray-200'">
              <input v-model="form.channel" type="radio" value="telegram" class="mt-1 text-primary" />
              <span>
                <span class="font-medium text-gray-800">Через Telegram</span>
                <span class="block text-sm text-gray-500">Сообщение от бота (нужна привязка в настройках)</span>
              </span>
            </label>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full h-12 rounded-lg bg-primary hover:bg-primary-dark text-white font-semibold disabled:opacity-50"
        >
          {{ loading ? 'Отправка...' : 'Отправить инструкцию' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { api } from '@/api/client'

const loading = ref(false)
const error = ref('')
const success = ref('')
const hint = ref('')
const form = reactive({
  emailOrPhone: '',
  channel: 'auto',
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const res = await api.auth.forgotPassword(form)
    success.value = res.message || 'Если аккаунт существует, инструкция отправлена.'
    hint.value = res.hint || ''
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка'
  } finally {
    loading.value = false
  }
}
</script>
