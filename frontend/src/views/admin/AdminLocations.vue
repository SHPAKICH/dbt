<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <router-link to="/admin" class="text-gray-500 hover:text-gray-700">← Админ-панель</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Управление точками</h1>
      <router-link to="/admin/locations/create" class="px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
        Создать точку
      </router-link>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">ID</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Название</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Название в iiko</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Адрес</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Телефон</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Статус</th>
              <th class="px-4 py-3 text-sm font-medium text-gray-600">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="loc in locations"
              :key="loc.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="px-4 py-3 text-gray-800">{{ loc.id }}</td>
              <td class="px-4 py-3 text-gray-800">{{ loc.name }}</td>
              <td class="px-4 py-3 text-gray-600 text-sm">{{ loc.iikoName || '—' }}</td>
              <td class="px-4 py-3 text-gray-800">{{ loc.address || '—' }}</td>
              <td class="px-4 py-3 text-gray-800">{{ loc.phone || '—' }}</td>
              <td class="px-4 py-3">
                <span :class="loc.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'" class="px-2 py-0.5 rounded text-sm">
                  {{ loc.isActive ? 'Активна' : 'Неактивна' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <router-link :to="{ name: 'AdminLocationEdit', params: { id: loc.id } }" class="text-primary hover:underline text-sm mr-2">Редактировать</router-link>
                <button type="button" @click="confirmDelete(loc)" class="text-red-600 hover:underline text-sm">Удалить</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="locations.length === 0" class="p-6 text-gray-500 text-center">Нет точек.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const locations = ref([])
const loading = ref(true)
const error = ref('')

async function confirmDelete(loc) {
  if (!confirm(`Удалить точку «${loc.name}»?`)) return
  try {
    const { api } = await import('@/api/client')
    await api.admin.deleteLocation(loc.id)
    locations.value = locations.value.filter((x) => x.id !== loc.id)
  } catch (e) {
    alert(e.data?.message || e.message || 'Ошибка удаления')
  }
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.admin.locations()
    locations.value = res.locations || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
