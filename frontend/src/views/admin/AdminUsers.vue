<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <router-link to="/admin" class="text-gray-500 hover:text-gray-700">← Админ-панель</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Управление пользователями</h1>
      <router-link
        v-if="canCreate"
        to="/admin/users/create"
        class="px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700"
      >
        Создать пользователя
      </router-link>
    </div>
    <div v-if="isManager && managerLocations.length === 0" class="p-4 rounded-lg bg-amber-50 text-amber-800 mb-4">
      У вас нет закреплённых точек. Обратитесь к администратору.
    </div>
    <div v-else-if="isManager && managerLocations.length" class="p-4 rounded-lg bg-blue-50 text-blue-800 mb-4 text-sm">
      Отображаются пользователи, привязанные к вашим точкам.
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
        <div class="relative flex-1 max-w-md">
          <input
            v-model="searchQuery"
            type="search"
            autocomplete="off"
            placeholder="Поиск: имя, email, телефон, должность, точка, ID…"
            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
        </div>
        <p v-if="searchQuery.trim() && users.length" class="text-sm text-gray-500 shrink-0">
          Показано: {{ filteredUsers.length }} из {{ users.length }}
        </p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">ID</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Имя</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Email</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Телефон</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Должность</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Точка</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Статус</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="u in filteredUsers"
              :key="u.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="px-4 py-3 text-gray-800">{{ u.id }}</td>
              <td class="px-4 py-3 text-gray-800">{{ u.fullName }}</td>
              <td class="px-4 py-3 text-gray-800">{{ u.email }}</td>
              <td class="px-4 py-3 text-gray-800">{{ u.phone }}</td>
              <td class="px-4 py-3 text-gray-800">
                {{ u.positionLabel }}
                <span v-if="u.isMainAdmin" class="ml-1 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-700">Главный</span>
                <span v-else-if="u.hasSuperAccess" class="ml-1 px-1.5 py-0.5 rounded text-xs bg-violet-100 text-violet-700">Супер</span>
                <span v-else-if="u.isAdmin" class="ml-1 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-700">Админ</span>
              </td>
              <td class="px-4 py-3 text-gray-800">{{ u.locationName || '—' }}</td>
              <td class="px-4 py-3">
                <span :class="u.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'" class="px-2 py-0.5 rounded text-sm">
                  {{ u.isActive ? 'Активен' : 'Неактивен' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <router-link :to="{ name: 'AdminUserEdit', params: { id: u.id } }" class="text-primary hover:underline text-sm mr-2">Редактировать</router-link>
                <button
                  v-if="canDelete(u)"
                  type="button"
                  @click="confirmDelete(u)"
                  class="text-red-600 hover:underline text-sm"
                >
                  Удалить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="users.length === 0" class="p-6 text-gray-500 text-center">Нет пользователей.</p>
      <p v-else-if="filteredUsers.length === 0" class="p-6 text-gray-500 text-center">Ничего не найдено — попробуйте другой запрос.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const users = ref([])
const isManager = ref(false)
const managerLocations = ref([])
const canCreate = ref(true)
const currentUserId = ref(null)
const loading = ref(true)
const error = ref('')
const searchQuery = ref('')

function userMatchesQuery(u, tokens) {
  const blob = [
    String(u.id),
    u.fullName,
    u.email,
    u.phone,
    u.positionLabel,
    u.position,
    u.locationName || '',
    u.isAdmin ? 'админ' : '',
    u.hasSuperAccess ? 'супер' : '',
    u.isMainAdmin ? 'главный' : '',
  ]
    .join(' ')
    .toLowerCase()
  return tokens.every((t) => blob.includes(t))
}

const filteredUsers = computed(() => {
  const tokens = searchQuery.value
    .trim()
    .toLowerCase()
    .split(/\s+/)
    .filter(Boolean)
  if (!tokens.length) {
    return users.value
  }
  return users.value.filter((u) => userMatchesQuery(u, tokens))
})

function canDelete(u) {
  if (u.isMainAdmin) return false
  if (u.isAdmin && !u.hasSuperAccess) return false
  if (currentUserId.value != null && u.id === currentUserId.value) return false
  return true
}

async function confirmDelete(u) {
  if (!confirm(`Удалить пользователя ${u.fullName}?`)) return
  try {
    const { api } = await import('@/api/client')
    await api.admin.deleteUser(u.id)
    users.value = users.value.filter((x) => x.id !== u.id)
  } catch (e) {
    alert(e.data?.message || e.message || 'Ошибка удаления')
  }
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const [res, userRes] = await Promise.all([api.admin.users(), api.auth.user().catch(() => ({ user: null }))])
    users.value = res.users || []
    isManager.value = res.isManager || false
    managerLocations.value = res.managerLocations || []
    canCreate.value = true
    currentUserId.value = userRes?.user?.id ?? null
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
