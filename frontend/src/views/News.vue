<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Уведомления</h1>

    <!-- Инвайты на смены -->
    <section class="mb-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-3">Инвайты на смены</h2>
      <p v-if="invitesLoading" class="text-gray-500 text-sm">Загрузка...</p>
      <p v-else-if="invitesError" class="text-sm text-red-600">{{ invitesError }}</p>
      <p v-else-if="!invites.length" class="text-sm text-gray-500">Нет ожидающих инвайтов.</p>
      <ul v-else class="space-y-3">
        <li
          v-for="inv in invites"
          :key="inv.id"
          class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3"
        >
          <div>
            <p class="font-medium text-gray-900">
              Вас приглашают на смену: {{ inv.locationName }}
            </p>
            <p class="text-sm text-gray-600 mt-0.5">
              {{ formatDateShort(inv.date) }}
              <span v-if="inv.timeStart && inv.timeEnd"> · {{ inv.timeStart }}–{{ inv.timeEnd }}</span>
            </p>
            <p v-if="inv.invitedBy" class="text-xs text-gray-500">От: {{ inv.invitedBy }}</p>
          </div>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary-dark disabled:opacity-50"
              :disabled="inviteActionId === inv.id"
              @click="acceptInvite(inv)"
            >
              Принять
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 disabled:opacity-50"
              :disabled="inviteActionId === inv.id"
              @click="declineInvite(inv)"
            >
              Отказать
            </button>
          </div>
        </li>
      </ul>
    </section>

    <!-- Новости (превью, по клику — полный просмотр) -->
    <section>
      <h2 class="text-lg font-semibold text-gray-800 mb-3">Новости</h2>
      <p v-if="loading" class="text-gray-500">Загрузка...</p>
      <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
      <div v-else>
        <p v-if="items.length === 0" class="text-gray-500">Пока нет новостей.</p>
        <ul v-else class="space-y-3">
          <li
            v-for="n in items"
            :key="n.id"
            class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:border-primary/30 hover:shadow-md transition-all cursor-pointer"
            @click="$router.push({ name: 'NewsView', params: { id: n.id } })"
          >
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-lg font-semibold text-gray-900 flex-1">{{ n.title }}</h3>
              <span class="text-xs text-gray-400 whitespace-nowrap shrink-0">
                {{ formatDate(n.publishedAt || n.createdAt) }}
              </span>
            </div>
            <p v-if="n.preview" class="mt-2 text-sm text-gray-600 line-clamp-2">
              {{ n.preview }}
            </p>
            <p class="mt-2 text-xs text-primary font-medium">Читать полностью →</p>
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/api/client'

const items = ref([])
const loading = ref(true)
const error = ref('')

const invites = ref([])
const invitesLoading = ref(true)
const invitesError = ref('')
const inviteActionId = ref(null)

function formatDate(val) {
  if (!val) return ''
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatDateShort(dateStr) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return `${d}.${m}.${y}`
}

async function loadInvites() {
  invitesLoading.value = true
  invitesError.value = ''
  try {
    const res = await api.schedule.myInvites()
    invites.value = res.items || []
  } catch (e) {
    invitesError.value = e.data?.message || e.message || 'Ошибка загрузки инвайтов'
    invites.value = []
  } finally {
    invitesLoading.value = false
  }
}

async function acceptInvite(inv) {
  inviteActionId.value = inv.id
  try {
    await api.schedule.acceptInvite({ id: inv.id })
    invites.value = invites.value.filter((i) => i.id !== inv.id)
  } catch (e) {
    invitesError.value = e.data?.message || e.message || 'Ошибка принятия'
  } finally {
    inviteActionId.value = null
  }
}

async function declineInvite(inv) {
  inviteActionId.value = inv.id
  try {
    await api.schedule.declineInvite({ id: inv.id })
    invites.value = invites.value.filter((i) => i.id !== inv.id)
  } catch (e) {
    invitesError.value = e.data?.message || e.message || 'Ошибка отклонения'
  } finally {
    inviteActionId.value = null
  }
}

onMounted(async () => {
  loadInvites()
  try {
    const res = await api.news.list()
    items.value = res.items || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки новостей'
  } finally {
    loading.value = false
  }
})
</script>

