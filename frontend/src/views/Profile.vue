<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Личный кабинет</h1>
    <div v-if="loading" class="text-center py-12 text-gray-500">Загрузка...</div>
      <div v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</div>
      <template v-else-if="profile">
        <div class="grid gap-6 md:grid-cols-[280px_1fr]">
          <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm text-center">
            <div class="relative inline-block">
              <img
                v-if="avatarSrc"
                :src="avatarSrc"
                alt="Аватар"
                class="w-32 h-32 rounded-full object-cover border-4 border-white shadow"
              />
              <div
                v-else
                class="w-32 h-32 rounded-full bg-primary/20 flex items-center justify-center text-2xl font-bold text-primary border-4 border-white shadow"
              >
                {{ (profile.user.firstName || profile.user.email || '?')[0].toUpperCase() }}
              </div>
              <label class="absolute bottom-0 right-0 bg-primary text-white rounded-full p-2 cursor-pointer shadow hover:bg-primary-dark">
                <input
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                  class="hidden"
                  @change="onAvatarChange"
                />
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13v2a2 2 0 01-2 2H7a2 2 0 01-2-2v-2"/></svg>
              </label>
            </div>
            <p class="mt-3 font-semibold text-gray-800">{{ profile.user.fullName }}</p>
            <p class="text-sm text-gray-500">{{ profile.user.positionLabel }}</p>
          </div>

          <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-800 mb-4">Основные данные</h2>
              <form @submit.prevent="saveProfile" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                    <input v-model="form.firstName" type="text" class="w-full h-11 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Фамилия</label>
                    <input v-model="form.lastName" type="text" class="w-full h-11 px-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                  </div>
                </div>
                <button type="submit" :disabled="saving" class="btn-primary">
                  {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
                </button>
                <p v-if="saveMessage" class="text-sm text-green-600">{{ saveMessage }}</p>
              </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-800 mb-2">Контактная информация</h2>
              <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd class="font-medium">{{ profile.user.email }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Телефон</dt><dd class="font-medium">{{ profile.user.phone }}</dd></div>
                <div v-if="profile.user.locationName" class="flex justify-between"><dt class="text-gray-500">Точка</dt><dd class="font-medium">{{ profile.user.locationName }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">В системе с</dt><dd class="font-medium">{{ formatDate(profile.user.createdAt) }}</dd></div>
              </dl>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-800 mb-2">Достижения</h2>
              <p class="text-sm text-gray-500">Раздел в разработке — здесь появятся ваши награды и прогресс.</p>
            </div>

            <div v-if="profile.templates?.length" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-800 mb-4">Карточка профиля</h2>
              <p class="text-sm text-gray-500 mb-4">Карточка отображается в сайдбаре и в графике смен. Выберите стиль:</p>
              <ul class="space-y-4">
                <li
                  v-for="t in profile.templates"
                  :key="t.id"
                  class="flex flex-wrap items-center gap-4 py-3 border-b border-gray-100 last:border-0"
                >
                  <div
                    class="w-[160px] h-[52px] rounded-lg flex items-center gap-2 overflow-hidden shrink-0 transition-all"
                    :class="[cardEffectClass(t.styleConfig), !hasStyleConfig(t) ? (t.cssClass || 'user-card_default') : '']"
                    :style="hasStyleConfig(t) ? { ...cardConfigToStyle(t.styleConfig), padding: '6px 10px' } : {}"
                  >
                    <div
                      class="shrink-0 rounded-full bg-white/25 flex items-center justify-center text-[10px] font-bold"
                      :style="hasStyleConfig(t) ? { ...cardAvatarStyle(t.styleConfig), width: '28px', height: '28px' } : { width: '28px', height: '28px' }"
                    >
                      <span :style="hasStyleConfig(t) ? { ...cardNameStyle(t.styleConfig), fontSize: '10px' } : {}">{{ (profile.user.firstName || '?')[0].toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                      <div class="truncate" :style="hasStyleConfig(t) ? { ...cardNameStyle(t.styleConfig), fontSize: '11px' } : { fontSize: '11px' }">{{ t.name }}</div>
                      <div class="truncate" :style="hasStyleConfig(t) ? { ...cardStatusStyle(t.styleConfig), fontSize: '9px' } : { fontSize: '9px', opacity: 0.7 }">preview</div>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <span class="font-medium text-gray-800">{{ t.name }}</span>
                    <p v-if="t.description" class="text-sm text-gray-500">{{ t.description }}</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      v-if="t.isSelected"
                      @click="cardRemove"
                      class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                    >
                      Снять
                    </button>
                    <button
                      v-else
                      @click="cardSelect(t.id)"
                      class="px-3 py-1.5 text-sm rounded-lg bg-primary text-white hover:bg-primary-dark"
                    >
                      Выбрать
                    </button>
                    <button
                      @click="cardVisibility(t.id, !t.isVisible)"
                      class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                    >
                      {{ t.isVisible ? 'Скрыть' : 'Показать' }}
                    </button>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <p class="mt-8 text-[10px] text-center text-gray-400 opacity-40 select-none">
          Made by kusgi for Double Bubble Tea
        </p>
      </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { api, getToken } from '@/api/client'
import { resetUserCache } from '@/router'
import { cardConfigToStyle, cardNameStyle, cardStatusStyle, cardAvatarStyle, cardEffectClass } from '@/utils/cardStyleRenderer'

function hasStyleConfig(t) {
  return t.styleConfig && typeof t.styleConfig === 'object' && Object.keys(t.styleConfig).length > 0
}

const router = useRouter()
const loading = ref(true)
const error = ref('')
const profile = ref(null)
const avatarSrc = ref('')
const saving = ref(false)
const saveMessage = ref('')
const form = reactive({ firstName: '', lastName: '' })

async function loadAvatarBlob() {
  avatarSrc.value = ''
  if (!profile.value?.user?.avatar) return
  const token = getToken()
  if (!token) return
  try {
    const url = api.profile.avatarFileUrl()
    const res = await fetch(url, { headers: { Authorization: `Bearer ${token}` } })
    if (!res.ok) return
    const blob = await res.blob()
    avatarSrc.value = URL.createObjectURL(blob)
  } catch (_) {}
}
function revokeAvatarBlob() {
  if (avatarSrc.value && avatarSrc.value.startsWith('blob:')) {
    URL.revokeObjectURL(avatarSrc.value)
  }
  avatarSrc.value = ''
}

function formatDate(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    revokeAvatarBlob()
    const res = await api.profile.get()
    profile.value = res
    form.firstName = res.user?.firstName ?? ''
    form.lastName = res.user?.lastName ?? ''
    await loadAvatarBlob()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
    if (e.status === 401) {
      resetUserCache()
      router.push({ name: 'Login', query: { redirect: '/' } })
    }
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  saving.value = true
  saveMessage.value = ''
  try {
    await api.profile.update({ firstName: form.firstName, lastName: form.lastName })
    saveMessage.value = 'Профиль обновлён.'
    await load()
  } catch (e) {
    saveMessage.value = ''
    error.value = e.data?.message || e.message || 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}

async function onAvatarChange(ev) {
  const file = ev.target.files?.[0]
  if (!file) return
  try {
    await api.profile.uploadAvatar(file)
    await load()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки аватара'
  }
  ev.target.value = ''
  await loadAvatarBlob()
}

onUnmounted(revokeAvatarBlob)

async function cardSelect(templateId) {
  try {
    await api.profile.cardSelect(templateId)
    await load()
    resetUserCache()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка'
  }
}

async function cardRemove() {
  try {
    await api.profile.cardRemove()
    await load()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка'
  }
}

async function cardVisibility(templateId, visible) {
  try {
    await api.profile.cardVisibility(templateId, visible)
    await load()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка'
  }
}

onMounted(load)
</script>

<style scoped>
.btn-primary {
  @apply px-4 py-2 rounded-lg bg-primary text-white font-medium hover:bg-primary-dark disabled:opacity-50;
}
</style>
