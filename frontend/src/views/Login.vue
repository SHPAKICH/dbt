<template>
  <div class="min-h-screen flex">
    <div class="flex-1 flex items-center justify-center p-8 bg-white">
      <div class="w-full max-w-md">
        <div class="flex items-center gap-3 mb-8">
          <div class="text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <ellipse cx="12" cy="5" rx="9" ry="3"/>
              <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
              <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>
            </svg>
          </div>
          <span class="text-xl font-bold text-gray-800">dbt hub</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">С возвращением</h1>
        <p class="text-gray-500 mb-6">Войдите, чтобы получить доступ к личному кабинету</p>

        <form @submit.prevent="submit" class="space-y-5">
          <div v-if="error" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
            {{ error }}
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email или телефон</label>
            <input
              v-model="form.emailOrPhone"
              type="text"
              autocomplete="email"
              placeholder="Введите email или телефон"
              class="w-full h-12 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              placeholder="Введите пароль"
              class="w-full h-12 px-4 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
            <button type="button" @click="showPassword = !showPassword" class="mt-1 text-sm text-gray-500 hover:text-primary">
              {{ showPassword ? 'Скрыть' : 'Показать' }} пароль
            </button>
          </div>
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
              <input v-model="form.rememberMe" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary" />
              <span class="text-sm text-gray-600">Запомнить меня</span>
            </label>
            <router-link to="/forgot-password" class="text-sm text-primary hover:underline">Забыли пароль?</router-link>
          </div>
          <button
            type="submit"
            :disabled="loading"
            class="w-full h-12 rounded-lg bg-primary hover:bg-primary-dark text-white font-semibold disabled:opacity-50"
          >
            {{ loading ? 'Вход...' : 'Войти' }}
          </button>
        </form>
        <p class="mt-6 text-center text-sm text-gray-500">
          Нет аккаунта? Обратитесь к администратору.
        </p>
      </div>
    </div>
    <div class="hidden lg:flex flex-1 items-center justify-center bg-gradient-to-br from-primary-dark to-primary p-8">
      <div class="text-center text-white max-w-sm">
        <div class="mb-6 opacity-90">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>
          </svg>
        </div>
        <h2 class="text-2xl font-bold mb-2">dbt hub</h2>
        <p class="opacity-90">Собрали все ингредиенты в один напиток!</p>
        <div class="mt-8 flex justify-center gap-8 font-semibold opacity-90">
          <span>30+ точек</span>
          <span>150+ сотрудников</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api, setToken } from '@/api/client'
import { resetUserCache, resolveRedirect } from '@/router'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)
const form = reactive({
  emailOrPhone: '',
  password: '',
  rememberMe: true,
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const res = await api.auth.login(form)
    if (res.token) setToken(res.token)
    resetUserCache()
    const redirect = resolveRedirect(route.query.redirect || '/')
    await router.push(redirect)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка входа'
  } finally {
    loading.value = false
  }
}
</script>
