<template>
  <div class="min-h-screen flex items-center justify-center p-8 bg-white">
    <div class="w-full max-w-md">
      <router-link to="/login" class="text-sm text-primary hover:underline mb-6 inline-block">← Назад к входу</router-link>
      <h1 class="text-2xl font-bold text-gray-800 mb-1">Новый пароль</h1>
      <p class="text-gray-500 mb-6">Придумайте новый пароль для входа</p>

      <div v-if="tokenInvalid" class="p-4 rounded-lg bg-red-50 text-red-700 text-sm">
        Ссылка недействительна или истекла.
        <router-link to="/forgot-password" class="block mt-2 text-primary font-medium">Запросить сброс снова</router-link>
      </div>

      <div v-else-if="done" class="p-4 rounded-lg bg-green-50 text-green-800 text-sm">
        {{ done }}
        <router-link to="/login" class="block mt-3 text-primary font-semibold">Войти</router-link>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-5">
        <div v-if="error" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            minlength="6"
            class="w-full h-12 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
            required
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение</label>
          <input
            v-model="form.passwordConfirm"
            type="password"
            autocomplete="new-password"
            class="w-full h-12 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="loading || checking"
          class="w-full h-12 rounded-lg bg-primary hover:bg-primary-dark text-white font-semibold disabled:opacity-50"
        >
          {{ loading ? 'Сохранение...' : 'Сохранить пароль' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api/client'

const route = useRoute()
const loading = ref(false)
const checking = ref(true)
const error = ref('')
const done = ref('')
const tokenInvalid = ref(false)
const token = ref('')
const form = reactive({
  password: '',
  passwordConfirm: '',
})

onMounted(async () => {
  token.value = (route.query.token || '').trim()
  if (!token.value) {
    tokenInvalid.value = true
    checking.value = false
    return
  }
  try {
    await api.auth.validateResetToken(token.value)
  } catch {
    tokenInvalid.value = true
  } finally {
    checking.value = false
  }
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const res = await api.auth.resetPassword({
      token: token.value,
      password: form.password,
      passwordConfirm: form.passwordConfirm,
    })
    done.value = res.message || 'Пароль изменён'
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка'
    if (e.status === 400) tokenInvalid.value = true
  } finally {
    loading.value = false
  }
}
</script>
