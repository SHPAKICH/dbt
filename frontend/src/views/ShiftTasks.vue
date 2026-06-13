<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-1">Задачи на смену</h1>
    <p class="text-gray-500 text-sm mb-5">Список задач на текущую смену для вашей точки.</p>

    <!-- Фильтры -->
    <div class="flex flex-wrap items-end gap-3 mb-5">
      <div class="flex-1 min-w-[160px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Точка</label>
        <select v-model="selectedLocation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/40 outline-none">
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
        </select>
      </div>
      <div class="min-w-[150px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Дата смены</label>
        <input v-model="shiftDate" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/40 outline-none" />
      </div>
    </div>

    <!-- Табы: Задачи / История -->
    <div class="flex gap-1 mb-5 border-b border-gray-200">
      <button
        class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
        :class="activeTab === 'tasks' ? 'bg-white border border-b-white border-gray-200 -mb-px text-primary' : 'text-gray-500 hover:text-gray-700'"
        @click="activeTab = 'tasks'"
      >
        Задачи
      </button>
      <button
        v-if="canCreate"
        class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
        :class="activeTab === 'history' ? 'bg-white border border-b-white border-gray-200 -mb-px text-primary' : 'text-gray-500 hover:text-gray-700'"
        @click="activeTab = 'history'; loadHistory()"
      >
        История
      </button>
    </div>

    <!-- ===== ТАБ: ЗАДАЧИ ===== -->
    <div v-if="activeTab === 'tasks'">
      <!-- Форма создания задачи -->
      <div v-if="canCreate" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Новая задача</h2>
        <div class="space-y-3">
          <input
            v-model="newTask.title"
            type="text"
            placeholder="Текст задачи (например: Проверить маркировки)"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/40 outline-none"
          />
          <div class="flex flex-wrap items-center gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
              <input v-model="newTask.requiresPhoto" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary/40" />
              Требуется фотоотчёт
            </label>
          </div>

          <!-- Выбор точек (для manager и admin) -->
          <div v-if="locations.length > 1">
            <label class="block text-xs font-medium text-gray-500 mb-1.5">На какие точки назначить</label>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="loc in locations"
                :key="loc.id"
                class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-lg border cursor-pointer transition-colors"
                :class="newTask.locationIds.includes(loc.id) ? 'bg-primary/10 border-primary/40 text-primary' : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-gray-300'"
              >
                <input
                  type="checkbox"
                  :value="loc.id"
                  v-model="newTask.locationIds"
                  class="sr-only"
                />
                {{ loc.name }}
              </label>
            </div>
            <button
              v-if="locations.length > 2"
              type="button"
              class="text-xs text-primary/70 hover:text-primary mt-1"
              @click="toggleAllLocations"
            >
              {{ newTask.locationIds.length === locations.length ? 'Снять все' : 'Выбрать все' }}
            </button>
          </div>

          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-opacity"
            :disabled="creating"
            @click="createTask"
          >
            {{ creating ? 'Создание...' : 'Создать задачу' }}
          </button>
          <p v-if="createError" class="text-xs text-red-500 mt-1">{{ createError }}</p>
        </div>
      </div>

      <!-- Загрузка -->
      <div v-if="loading" class="text-center py-10 text-gray-400 text-sm">Загрузка задач...</div>

      <!-- Пустой список -->
      <div v-else-if="tasks.length === 0" class="text-center py-10">
        <div class="text-4xl mb-2">📋</div>
        <p class="text-gray-500 text-sm">Нет задач на эту дату</p>
      </div>

      <!-- Список задач -->
      <div v-else class="space-y-3">
        <div
          v-for="task in tasks"
          :key="task.id"
          class="bg-white rounded-xl border shadow-sm overflow-hidden transition-colors"
          :class="task.completion ? 'border-green-200' : 'border-gray-200'"
        >
          <div class="p-4">
            <div class="flex items-start gap-3">
              <!-- Чекбокс / статус -->
              <div class="pt-0.5 shrink-0">
                <div v-if="task.completion" class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                  <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div v-else class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
              </div>

              <!-- Контент -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium" :class="task.completion ? 'text-gray-500 line-through' : 'text-gray-800'">
                  {{ task.title }}
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                  <span class="text-xs text-gray-400">Поставил: {{ task.createdBy }}</span>
                  <span v-if="task.requiresPhoto" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">📷 С фото</span>
                </div>

                <!-- Блок выполнения -->
                <div v-if="task.completion" class="mt-2 pl-3 border-l-2 border-green-300">
                  <p class="text-xs text-green-700">
                    Выполнил: {{ task.completion.completedBy }} — {{ formatDate(task.completion.completedAt) }}
                  </p>
                  <p v-if="task.completion.comment" class="text-xs text-gray-600 mt-1 italic">« {{ task.completion.comment }} »</p>
                  <div v-if="task.completion.photos && task.completion.photos.length" class="flex flex-wrap gap-2 mt-2">
                    <button
                      v-for="photo in task.completion.photos"
                      :key="photo.id"
                      type="button"
                      class="block w-16 h-16 rounded-lg overflow-hidden border border-gray-200 hover:border-primary/40 transition-colors"
                      @click="openPreview(photo)"
                    >
                      <img :src="photo.url" :alt="photo.fileName" class="w-full h-full object-cover" />
                    </button>
                  </div>
                </div>

                <!-- Форма выполнения -->
                <div v-if="!task.completion && completingTaskId === task.id" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-2">
                  <textarea
                    v-model="completeForm.comment"
                    placeholder="Комментарий (необязательно)"
                    rows="2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/40 outline-none resize-none"
                  ></textarea>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">
                      {{ task.requiresPhoto ? 'Фото (обязательно)' : 'Фото (необязательно)' }}
                    </label>
                    <input
                      type="file"
                      multiple
                      accept="image/jpeg,image/png,image/webp,image/heic,image/heif,image/*"
                      class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20"
                      @change="onPhotosSelected"
                    />
                  </div>
                  <div class="flex gap-2">
                    <button
                      type="button"
                      class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
                      :disabled="completing"
                      @click="completeTask(task)"
                    >
                      {{ completing ? 'Сохранение...' : 'Подтвердить' }}
                    </button>
                    <button
                      type="button"
                      class="px-3 py-1.5 rounded-lg bg-gray-200 text-gray-600 text-sm hover:bg-gray-300"
                      @click="completingTaskId = null"
                    >
                      Отмена
                    </button>
                  </div>
                  <p v-if="completeError" class="text-xs text-red-500">{{ completeError }}</p>
                </div>
              </div>

              <!-- Действия -->
              <div class="shrink-0 flex gap-1">
                <button
                  v-if="!task.completion && completingTaskId !== task.id"
                  type="button"
                  class="p-2 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors"
                  title="Выполнить"
                  @click="startComplete(task)"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
                <button
                  v-if="canDeleteTask(task)"
                  type="button"
                  class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  title="Удалить"
                  @click="deleteTask(task)"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== ТАБ: ИСТОРИЯ ===== -->
    <div v-if="activeTab === 'history' && canCreate">
      <div class="flex flex-wrap items-end gap-3 mb-4">
        <div class="min-w-[160px]">
          <label class="block text-xs font-medium text-gray-500 mb-1">Точка (история)</label>
          <select v-model="historyLocation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/40 outline-none" @change="loadHistory">
            <option :value="0">Все точки</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
        </div>
        <div class="min-w-[150px]">
          <label class="block text-xs font-medium text-gray-500 mb-1">Дата (история)</label>
          <input v-model="historyDate" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/40 outline-none" @change="loadHistory" />
        </div>
      </div>

      <div v-if="historyLoading" class="text-center py-10 text-gray-400 text-sm">Загрузка...</div>
      <div v-else-if="historyTasks.length === 0" class="text-center py-10">
        <div class="text-4xl mb-2">📂</div>
        <p class="text-gray-500 text-sm">Нет задач по заданным фильтрам</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="task in historyTasks"
          :key="task.id"
          class="bg-white rounded-xl border shadow-sm p-4"
          :class="task.completion ? 'border-green-200' : 'border-red-200'"
        >
          <div class="flex items-start gap-3">
            <div class="pt-0.5 shrink-0">
              <div v-if="task.completion" class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              </div>
              <div v-else class="w-5 h-5 rounded-full bg-red-400 flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800">{{ task.title }}</p>
              <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-400">
                <span>{{ task.locationName }}</span>
                <span>•</span>
                <span>{{ task.shiftDate }}</span>
                <span>•</span>
                <span>Поставил: {{ task.createdBy }}</span>
                <span v-if="task.requiresPhoto" class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">📷</span>
              </div>
              <div v-if="task.completion" class="mt-2 pl-3 border-l-2 border-green-300">
                <p class="text-xs text-green-700">Выполнил: {{ task.completion.completedBy }} — {{ formatDate(task.completion.completedAt) }}</p>
                <p v-if="task.completion.comment" class="text-xs text-gray-600 mt-1 italic">« {{ task.completion.comment }} »</p>
                <div v-if="task.completion.photos && task.completion.photos.length" class="flex flex-wrap gap-2 mt-2">
                  <button v-for="photo in task.completion.photos" :key="photo.id" type="button" class="block w-14 h-14 rounded-lg overflow-hidden border border-gray-200 hover:border-primary/40" @click="openPreview(photo)">
                    <img :src="photo.url" :alt="photo.fileName" class="w-full h-full object-cover" />
                  </button>
                </div>
              </div>
              <div v-else class="mt-1">
                <span class="text-xs text-red-500 font-medium">Не выполнена</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Photo preview modal -->
    <Teleport to="body">
      <div
        v-if="previewPhoto"
        class="fixed inset-0 z-[2000] bg-black/80 flex items-center justify-center p-4"
        @click.self="previewPhoto = null"
      >
        <div class="relative max-w-3xl max-h-[90vh] w-full">
          <button
            class="absolute -top-3 -right-3 h-8 w-8 bg-white rounded-full shadow flex items-center justify-center text-gray-700 hover:bg-gray-100 z-10"
            @click="previewPhoto = null"
          >&times;</button>
          <img
            :src="previewPhoto.url"
            :alt="previewPhoto.fileName"
            class="w-full h-auto max-h-[85vh] object-contain rounded-xl"
          />
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { api } from '@/api/client'
import { getCurrentUser } from '@/router'
import { compressImagesInList } from '@/utils/imageUpload'

const user = ref(null)
const locations = ref([])
const selectedLocation = ref(null)
const shiftDate = ref(new Date().toISOString().slice(0, 10))
const canCreate = ref(false)

const loading = ref(false)
const tasks = ref([])

const activeTab = ref('tasks')

const newTask = ref({
  title: '',
  requiresPhoto: false,
  locationIds: [],
})
const creating = ref(false)
const createError = ref('')

const completingTaskId = ref(null)
const completeForm = ref({ comment: '', photos: [] })
const completing = ref(false)
const completeError = ref('')

const previewPhoto = ref(null)

const historyLocation = ref(0)
const historyDate = ref(new Date().toISOString().slice(0, 10))
const historyTasks = ref([])
const historyLoading = ref(false)

onMounted(async () => {
  user.value = await getCurrentUser()
  await loadLocations()
})

async function loadLocations() {
  try {
    const res = await api.shiftTasks.locations()
    locations.value = res.locations || []
    canCreate.value = res.canCreate || false
    if (locations.value.length && !selectedLocation.value) {
      selectedLocation.value = locations.value[0].id
      newTask.value.locationIds = [locations.value[0].id]
    }
  } catch {}
}

watch([selectedLocation, shiftDate], () => {
  if (selectedLocation.value) loadTasks()
})

async function loadTasks() {
  if (!selectedLocation.value) return
  loading.value = true
  try {
    const res = await api.shiftTasks.tasks({
      location_id: selectedLocation.value,
      shift_date: shiftDate.value,
    })
    tasks.value = res.tasks || []
  } catch {
    tasks.value = []
  } finally {
    loading.value = false
  }
}

function toggleAllLocations() {
  if (newTask.value.locationIds.length === locations.value.length) {
    newTask.value.locationIds = []
  } else {
    newTask.value.locationIds = locations.value.map(l => l.id)
  }
}

async function createTask() {
  createError.value = ''
  if (!newTask.value.title.trim()) {
    createError.value = 'Введите текст задачи'
    return
  }
  const lids = locations.value.length === 1 ? [locations.value[0].id] : newTask.value.locationIds
  if (!lids.length) {
    createError.value = 'Выберите хотя бы одну точку'
    return
  }
  creating.value = true
  try {
    await api.shiftTasks.create({
      title: newTask.value.title.trim(),
      location_ids: lids,
      shift_date: shiftDate.value,
      requires_photo: newTask.value.requiresPhoto ? 1 : 0,
    })
    newTask.value.title = ''
    newTask.value.requiresPhoto = false
    await loadTasks()
  } catch (e) {
    createError.value = e?.data?.message || e.message || 'Ошибка создания'
  } finally {
    creating.value = false
  }
}

function startComplete(task) {
  completingTaskId.value = task.id
  completeForm.value = { comment: '', photos: [] }
  completeError.value = ''
}

function onPhotosSelected(e) {
  completeForm.value.photos = Array.from(e.target.files || [])
}

async function completeTask(task) {
  completeError.value = ''
  if (task.requiresPhoto && completeForm.value.photos.length === 0) {
    completeError.value = 'Для этой задачи нужен фотоотчёт'
    return
  }
  completing.value = true
  try {
    const fd = new FormData()
    fd.append('task_id', task.id)
    if (completeForm.value.comment.trim()) {
      fd.append('comment', completeForm.value.comment.trim())
    }
    const preparedPhotos = await compressImagesInList(completeForm.value.photos)
    for (const file of preparedPhotos) {
      fd.append('photos[]', file)
    }
    await api.shiftTasks.complete(fd)
    completingTaskId.value = null
    await loadTasks()
  } catch (e) {
    completeError.value = e?.data?.message || e.message || 'Ошибка'
  } finally {
    completing.value = false
  }
}

function canDeleteTask(task) {
  if (!user.value) return false
  return user.value.isAdmin || task.createdById === user.value.id
}

async function deleteTask(task) {
  if (!confirm('Удалить задачу?')) return
  try {
    await api.shiftTasks.remove(task.id)
    await loadTasks()
  } catch {}
}

async function loadHistory() {
  historyLoading.value = true
  try {
    const params = {}
    if (historyLocation.value) params.location_id = historyLocation.value
    if (historyDate.value) params.shift_date = historyDate.value
    const res = await api.shiftTasks.history(params)
    historyTasks.value = res.tasks || []
  } catch {
    historyTasks.value = []
  } finally {
    historyLoading.value = false
  }
}

function openPreview(photo) {
  previewPhoto.value = photo
}

function formatDate(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
