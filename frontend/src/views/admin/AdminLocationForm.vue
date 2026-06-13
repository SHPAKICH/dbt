<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <div class="mb-4">
      <router-link to="/admin/locations" class="text-gray-500 hover:text-gray-700">← Точки</router-link>
    </div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ isEdit ? 'Редактирование точки' : 'Создание точки' }}</h1>
    <p v-if="error" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">{{ error }}</p>
    <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название в iiko</label>
        <input
          v-model="form.iikoName"
          type="text"
          placeholder="Как точка называется в iiko"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
        <p class="mt-1 text-xs text-gray-500">
          Точное название подразделения в iiko для чеков, отчётов и аналитики. Скопируйте из списка точек iiko.
        </p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
        <textarea v-model="form.address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
        <input v-model="form.phone" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div class="flex items-center gap-2">
        <input v-model="form.isActive" type="checkbox" id="locActive" class="rounded border-gray-300" />
        <label for="locActive" class="text-sm text-gray-700">Активна</label>
      </div>
      <div class="flex gap-2 pt-4">
        <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </button>
        <router-link to="/admin/locations" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Отмена</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const form = reactive({
  name: '',
  iikoName: '',
  address: '',
  phone: '',
  isActive: true,
})
const error = ref('')
const saving = ref(false)

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { api } = await import('@/api/client')
      const res = await api.admin.location(route.params.id)
      form.name = res.name ?? ''
      form.iikoName = res.iikoName ?? ''
      form.address = res.address ?? ''
      form.phone = res.phone ?? ''
      form.isActive = res.isActive ?? true
    } catch (e) {
      error.value = e.data?.message || e.message || 'Ошибка загрузки'
    }
  }
})

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const { api } = await import('@/api/client')
    const body = {
      name: form.name,
      iikoName: form.iikoName.trim() || null,
      address: form.address,
      phone: form.phone,
      isActive: form.isActive ? 1 : 0,
    }
    if (isEdit.value) {
      await api.admin.updateLocation(route.params.id, body)
    } else {
      await api.admin.createLocation(body)
    }
    router.push({ name: 'AdminLocations' })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}
</script>
