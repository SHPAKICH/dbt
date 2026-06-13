<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <div class="mb-4">
      <router-link to="/admin/users" class="text-gray-500 hover:text-gray-700">← Пользователи</router-link>
    </div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ isEdit ? 'Редактирование пользователя' : 'Создание пользователя' }}</h1>
    <p v-if="error" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">{{ error }}</p>
    <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
        <input v-model="form.firstName" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Фамилия</label>
        <input v-model="form.lastName" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
        <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Телефон *</label>
        <input v-model="form.phone" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div v-if="isEdit">
        <label class="block text-sm font-medium text-gray-700 mb-1">Telegram</label>
        <input v-model="form.telegram" type="text" placeholder="@username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div v-if="isEdit">
        <label class="block text-sm font-medium text-gray-700 mb-1">День рождения</label>
        <input v-model="form.birthday" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div v-if="isEdit">
        <label class="block text-sm font-medium text-gray-700 mb-1">Дата аттестации</label>
        <input v-model="form.certificationDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Должность *</label>
        <select v-model="form.position" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
          <option value="">Выберите должность</option>
          <option v-for="(label, key) in positionOptions" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <div v-if="isEdit">
        <label class="block text-sm font-medium text-gray-700 mb-1">Дата вступления в должность</label>
        <input v-model="form.positionEffectiveFrom" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        <p class="mt-1 text-xs text-gray-500">При смене должности укажите дату, с которой действует новая ставка (для расчёта зарплаты). Если не указать — будет использована сегодняшняя дата.</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Точка</label>
        <select v-model="form.locationId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
          <option :value="null">Не закреплён</option>
          <option v-for="loc in locationsOptions" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
        </select>
      </div>
      <div v-if="canEditManagerTerritories" class="rounded-lg border border-amber-100 bg-amber-50/60 p-4 space-y-2">
        <label class="block text-sm font-medium text-gray-800">Точки территориального управляющего</label>
        <p class="text-xs text-gray-600">Эти точки определяют, где у управляющего есть полномочия: график, снабжение, суточные отчёты и др.</p>
        <div class="max-h-52 overflow-y-auto rounded-md border border-amber-200/80 bg-white p-2 space-y-1.5">
          <label
            v-for="loc in locationsOptions"
            :key="'mgr-loc-' + loc.id"
            class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 rounded px-1 py-0.5"
          >
            <input
              type="checkbox"
              class="rounded border-gray-300 text-primary focus:ring-primary/30"
              :checked="form.managedLocationIds.includes(loc.id)"
              @change="toggleManagedLocation(loc.id)"
            />
            <span>{{ loc.name }}</span>
          </label>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <input v-model="form.isActive" type="checkbox" id="isActive" class="rounded border-gray-300" />
        <label for="isActive" class="text-sm text-gray-700">Активен</label>
      </div>
      <div
        v-if="canManageSuperAccess"
        class="rounded-lg border border-violet-200 bg-violet-50/70 p-4 space-y-2"
      >
        <label class="flex items-start gap-2 cursor-pointer">
          <input
            v-model="form.hasSuperAccess"
            type="checkbox"
            class="rounded border-gray-300 text-violet-600 focus:ring-violet-500/30 mt-0.5"
          />
          <span>
            <span class="block text-sm font-medium text-gray-800">Супер-доступ</span>
            <span class="block text-xs text-gray-600 mt-0.5">
              Полный доступ к админ-панели: точки, пользователи, аналитика, настройки зарплаты и др.
              Выдавать может только главный администратор.
            </span>
          </span>
        </label>
      </div>
      <template v-if="!isEdit">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Пароль *</label>
          <input v-model="form.password" type="password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение пароля *</label>
          <input v-model="form.confirmPassword" type="password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
      </template>
      <template v-else>
        <hr class="my-4" />
        <h3 class="font-medium text-gray-800 mb-2">Смена пароля</h3>
        <p class="text-sm text-gray-500 mb-2">Оставьте пустым, если не меняете пароль.</p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
          <input v-model="form.newPassword" type="password" minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение пароля</label>
          <input v-model="form.confirmPassword" type="password" minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
      </template>
      <div class="flex gap-2 pt-4">
        <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </button>
        <router-link to="/admin/users" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Отмена</router-link>
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
const locationsOptions = ref([])
const currentUserIsAdmin = ref(false)
const currentUserIsMainAdmin = ref(false)
const editingUserId = ref(null)
const canEditManagerTerritories = computed(
  () => (currentUserIsAdmin.value || currentUserIsMainAdmin.value) && form.position === 'manager',
)
const canManageSuperAccess = computed(() => {
  if (!currentUserIsMainAdmin.value) return false
  if (!isEdit.value) return true
  if (editingUserId.value == null) return false
  if (form.isMainAdmin) return false
  if (form.isLegacyAdmin) return false
  return true
})
const positionOptions = {
  manager: 'Управляющий',
  location_manager: 'Менеджер точки',
  senior_teamaker: 'Старший тимейкер',
  teamaker: 'Тимейкер',
  trainee: 'Стажер',
}
const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  position: '',
  locationId: null,
  isActive: true,
  password: '',
  confirmPassword: '',
  telegram: '',
  birthday: '',
  certificationDate: '',
  newPassword: '',
  positionEffectiveFrom: '',
  managedLocationIds: [],
  hasSuperAccess: false,
  isMainAdmin: false,
  isLegacyAdmin: false,
})
const error = ref('')
const saving = ref(false)

function toggleManagedLocation(id) {
  const i = form.managedLocationIds.indexOf(id)
  if (i === -1) {
    form.managedLocationIds.push(id)
  } else {
    form.managedLocationIds.splice(i, 1)
  }
}

onMounted(async () => {
  const { api } = await import('@/api/client')
  try {
    const me = await api.auth.user()
    currentUserIsAdmin.value = !!me.user?.isAdmin
    currentUserIsMainAdmin.value = !!me.user?.isMainAdmin
  } catch {
    currentUserIsAdmin.value = false
  }
  if (isEdit.value) {
    try {
      const res = await api.admin.user(route.params.id)
      const u = res.user
      editingUserId.value = u.id
      form.firstName = u.firstName ?? ''
      form.lastName = u.lastName ?? ''
      form.email = u.email ?? ''
      form.phone = u.phone ?? ''
      form.position = u.position ?? ''
      form.locationId = u.locationId
      form.isActive = u.isActive ?? true
      form.telegram = u.telegram ?? ''
      form.birthday = u.birthday ? u.birthday.slice(0, 10) : ''
      form.certificationDate = u.certificationDate ? u.certificationDate.slice(0, 10) : ''
      form.managedLocationIds = Array.isArray(u.managedLocationIds) ? [...u.managedLocationIds] : []
      form.hasSuperAccess = !!u.hasSuperAccess
      form.isMainAdmin = !!u.isMainAdmin
      form.isLegacyAdmin = !!u.isAdmin && !u.hasSuperAccess
      locationsOptions.value = res.locationsOptions || []
    } catch (e) {
      error.value = e.data?.message || e.message || 'Ошибка загрузки'
      return
    }
  } else {
    const res = await api.admin.users()
    locationsOptions.value = res.locationsOptions || []
    if (res.isManager && (res.managerLocations || []).length) {
      const first = res.managerLocations[0]
      form.locationId = first
    }
  }
})

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const { api } = await import('@/api/client')
    if (isEdit.value) {
      const payload = {
        firstName: form.firstName,
        lastName: form.lastName,
        email: form.email,
        phone: form.phone,
        position: form.position,
        locationId: form.locationId,
        isActive: form.isActive,
        telegram: form.telegram || null,
        birthday: form.birthday || null,
        certificationDate: form.certificationDate || null,
        newPassword: form.newPassword || undefined,
        confirmPassword: form.confirmPassword || undefined,
      }
      if (form.positionEffectiveFrom) {
        payload.positionEffectiveFrom = form.positionEffectiveFrom
      }
      if (canEditManagerTerritories.value) {
        payload.managedLocationIds = [...form.managedLocationIds]
      }
      if (canManageSuperAccess.value) {
        payload.hasSuperAccess = form.hasSuperAccess ? 1 : 0
      }
      await api.admin.updateUser(route.params.id, payload)
      router.push({ name: 'AdminUsers' })
    } else {
      const createBody = {
        firstName: form.firstName,
        lastName: form.lastName,
        email: form.email,
        phone: form.phone,
        position: form.position,
        locationId: form.locationId,
        isActive: form.isActive ? 1 : 0,
        password: form.password,
        confirmPassword: form.confirmPassword,
      }
      if (canEditManagerTerritories.value) {
        createBody.managedLocationIds = [...form.managedLocationIds]
      }
      if (canManageSuperAccess.value) {
        createBody.hasSuperAccess = form.hasSuperAccess ? 1 : 0
      }
      await api.admin.createUser(createBody)
      router.push({ name: 'AdminUsers' })
    }
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения'
    if (e.data?.errors) {
      error.value += ' ' + Object.values(e.data.errors).flat().join(' ')
    }
  } finally {
    saving.value = false
  }
}
</script>
