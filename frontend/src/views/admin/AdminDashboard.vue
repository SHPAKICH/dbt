<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Админ-панель</h1>
    <p v-if="loading" class="text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="space-y-6">
      <p v-if="successMessage" class="p-4 rounded-lg bg-green-50 text-green-700">{{ successMessage }}</p>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center">
          <div class="text-3xl font-bold text-gray-800">{{ stats.usersCount }}</div>
          <p class="text-gray-600 mt-1">Всего пользователей</p>
          <router-link to="/admin/users" class="inline-block mt-3 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90">
            Управление пользователями
          </router-link>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center">
          <div class="text-3xl font-bold text-gray-800">{{ stats.activeUsersCount }}</div>
          <p class="text-gray-600 mt-1">Активных пользователей</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center">
          <div class="text-3xl font-bold text-gray-800">{{ stats.locationsCount }}</div>
          <p class="text-gray-600 mt-1">Точек (кафе)</p>
          <router-link to="/admin/locations" class="inline-block mt-3 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90">
            Управление точками
          </router-link>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center">
          <div class="text-3xl font-bold text-gray-800">{{ stats.pushSubscriptionsCount }}</div>
          <p class="text-gray-600 mt-1">Push-подписок</p>
          <router-link to="/admin/push" class="inline-block mt-3 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90">
            Рассылка уведомлений
          </router-link>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center md:col-span-2 xl:col-span-1">
          <div class="text-3xl font-bold text-gray-800">{{ stats.newsCount }}</div>
          <p class="text-gray-600 mt-1">Новостей</p>
          <router-link to="/admin/news" class="inline-block mt-3 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90">
            Управление новостями
          </router-link>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center md:col-span-2 xl:col-span-1">
          <div class="text-3xl font-bold text-gray-800">🎨</div>
          <p class="text-gray-600 mt-1">Карточки профиля</p>
          <router-link to="/admin/cards" class="inline-block mt-3 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90">
            Конструктор карточек
          </router-link>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm text-center md:col-span-2 xl:col-span-1">
          <div class="text-3xl font-bold text-gray-800">₽</div>
          <p class="text-gray-600 mt-1">Зарплата</p>
          <router-link to="/admin/payroll" class="inline-block mt-3 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90">
            Ставки, КРО и надбавки
          </router-link>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-800">Резервное копирование БД</h2>
            <p class="text-sm text-gray-500 mt-1">Расписание: {{ backup.scheduleLabel || 'Каждые 12 часов' }}</p>
            <p class="text-sm text-gray-500 mt-1">Хранение: {{ backup.retentionDays || 14 }} дн.</p>
            <p class="text-sm text-gray-500 mt-1 break-all">Каталог: {{ backup.directory || '-' }}</p>
          </div>
          <button
            type="button"
            :disabled="downloadingBackup"
            class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="downloadBackup"
          >
            {{ downloadingBackup ? 'Подготовка дампа...' : 'Скачать свежий дамп' }}
          </button>
        </div>

        <div class="mt-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Последние резервные копии</h3>
          <p v-if="!backup.recentBackups.length" class="text-sm text-gray-500">Резервные копии ещё не создавались.</p>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 text-left text-gray-500">
                  <th class="py-2 pr-4 font-medium">Файл</th>
                  <th class="py-2 pr-4 font-medium">Дата</th>
                  <th class="py-2 font-medium">Размер</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in backup.recentBackups" :key="item.filename" class="border-b border-gray-100 last:border-0">
                  <td class="py-2 pr-4 text-gray-800 break-all">{{ item.filename }}</td>
                  <td class="py-2 pr-4 text-gray-600">{{ item.modifiedAt || '-' }}</td>
                  <td class="py-2 text-gray-600">{{ formatSize(item.size) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const stats = ref({ usersCount: 0, locationsCount: 0, activeUsersCount: 0, pushSubscriptionsCount: 0, newsCount: 0 })
const backup = ref({ recentBackups: [], directory: '', retentionDays: 14, scheduleLabel: 'Каждые 12 часов' })
const loading = ref(true)
const error = ref('')
const successMessage = ref('')
const downloadingBackup = ref(false)

function formatSize(value) {
  const size = Number(value || 0)
  if (!size) return '0 Б'
  const units = ['Б', 'КБ', 'МБ', 'ГБ']
  let current = size
  let unitIndex = 0
  while (current >= 1024 && unitIndex < units.length - 1) {
    current /= 1024
    unitIndex += 1
  }
  return `${current >= 10 || unitIndex === 0 ? current.toFixed(0) : current.toFixed(1)} ${units[unitIndex]}`
}

async function loadDashboard(showLoader = true) {
  if (showLoader) loading.value = true
  try {
    const { api } = await import('@/api/client')
    const [dashboardRes, pushStatsRes] = await Promise.all([
      api.admin.dashboard(),
      api.admin.pushStats().catch(() => ({ subscriptionsCount: 0 })),
    ])
    stats.value = {
      ...dashboardRes,
      pushSubscriptionsCount: pushStatsRes.subscriptionsCount || 0,
      newsCount: dashboardRes.newsCount || 0,
    }
    backup.value = {
      recentBackups: dashboardRes.backup?.recentBackups || [],
      directory: dashboardRes.backup?.directory || '',
      retentionDays: dashboardRes.backup?.retentionDays || 14,
      scheduleLabel: dashboardRes.backup?.scheduleLabel || 'Каждые 12 часов',
    }
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    if (showLoader) loading.value = false
  }
}

async function downloadBackup() {
  error.value = ''
  successMessage.value = ''
  downloadingBackup.value = true
  try {
    const { api } = await import('@/api/client')
    const { blob, filename } = await api.admin.downloadDbBackup()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    successMessage.value = `Дамп ${filename} создан и скачан.`
    await loadDashboard(false)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка создания резервной копии'
  } finally {
    downloadingBackup.value = false
  }
}

onMounted(async () => {
  await loadDashboard(true)
})
</script>
