<template>
  <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
      <div class="h-9 w-9 rounded-xl bg-blue-50 flex items-center justify-center text-lg shrink-0">📋</div>
      <div>
        <div class="font-semibold text-gray-800">Новая заявка</div>
        <div class="text-xs text-gray-500">Тема, тип, приоритет, объект обслуживания</div>
      </div>
    </div>

    <div class="px-5 py-5 space-y-5">
      <div
        v-if="locations.length === 0"
        class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm px-4 py-3"
      >
        Нет доступных точек для заявки. Обратитесь к администратору.
      </div>

      <template v-else>
        <!-- Объект обслуживания: только если несколько точек -->
        <div v-if="showLocationField">
          <label class="block text-xs font-medium text-gray-500 mb-1.5">Объект обслуживания (точка)</label>
          <select
            v-model.number="form.maintenanceEntityId"
            class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-300"
          >
            <option :value="null" disabled>Выберите точку</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">
              {{ loc.name }}
            </option>
          </select>
        </div>
        <div v-else class="text-sm text-gray-600">
          <span class="text-gray-500">Точка:</span>
          <span class="font-medium text-gray-800 ml-1">{{ singleLocationName }}</span>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1.5">Тема заявки <span class="text-red-500">*</span></label>
          <input
            v-model.trim="form.title"
            type="text"
            maxlength="500"
            placeholder="Кратко опишите суть"
            class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-300"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Тип заявки</label>
            <select
              v-model="form.issueType"
              class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
            >
              <option v-for="opt in issueTypeOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Приоритет</label>
            <select
              v-model="form.priority"
              class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
            >
              <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>
        </div>

        <div>
          <button
            type="button"
            class="text-sm font-medium text-blue-600 hover:text-blue-800"
            @click="showDescription = !showDescription"
          >
            {{ showDescription ? 'Скрыть описание' : '+ Добавить описание' }}
          </button>
          <div v-if="showDescription" class="mt-2">
            <textarea
              v-model.trim="form.description"
              rows="4"
              placeholder="Подробности, шаги воспроизведения…"
              class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 resize-y min-h-[100px]"
            />
          </div>
        </div>

        <div>
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            @change="onFileChange"
          />
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-dashed border-gray-300 text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors"
            @click="fileInput?.click()"
          >
            <span>📎</span>
            {{ form.attachment ? 'Заменить файл' : 'Прикрепить файл' }}
          </button>
          <p v-if="form.attachment" class="mt-2 text-xs text-gray-600 truncate">
            {{ form.attachment.name }}
            <button type="button" class="text-red-600 ml-2 hover:underline" @click="clearFile">Убрать</button>
          </p>
        </div>

        <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>
        <p v-if="submitSuccess" class="text-sm text-green-700">{{ submitSuccess }}</p>

        <div class="flex flex-wrap gap-3 pt-1">
          <button
            type="button"
            class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none shadow-sm"
            :disabled="submitting || !canSubmit"
            @click="submit"
          >
            {{ submitting ? 'Отправка…' : 'Создать заявку' }}
          </button>
          <button
            type="button"
            class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50"
            :disabled="submitting"
            @click="resetForm"
          >
            Очистить
          </button>
        </div>

        <p class="text-xs text-gray-400 leading-relaxed">
          Отправка в OkDesk будет подключена после согласования кодов типов и приоритетов с администратором.
          Сейчас данные проверяются локально и не уходят на сервер.
        </p>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'

const props = defineProps({
  locations: {
    type: Array,
    default: () => [],
  },
})

/** Коды как в примерах OkDesk; при необходимости заменить после уточнения у админа */
const issueTypeOptions = [
  { value: 'incident', label: 'Инцидент' },
  { value: 'service', label: 'Обслуживание' },
]

const priorityOptions = [
  { value: 'low', label: 'Низкий' },
  { value: 'normal', label: 'Обычный' },
  { value: 'high', label: 'Высокий' },
]

const showDescription = ref(false)
const fileInput = ref(null)
const submitting = ref(false)
const submitError = ref('')
const submitSuccess = ref('')

const form = reactive({
  maintenanceEntityId: null,
  title: '',
  issueType: 'service',
  priority: 'normal',
  description: '',
  attachment: null,
})

const showLocationField = computed(() => props.locations.length > 1)

const singleLocationName = computed(() => {
  if (props.locations.length !== 1) return ''
  return props.locations[0].name || ''
})

const resolvedLocationId = computed(() => {
  if (props.locations.length === 0) return null
  if (props.locations.length === 1) return props.locations[0].id
  return form.maintenanceEntityId
})

const canSubmit = computed(() => {
  if (props.locations.length === 0) return false
  if (!form.title.trim()) return false
  if (props.locations.length > 1 && (form.maintenanceEntityId == null || form.maintenanceEntityId === '')) {
    return false
  }
  return true
})

watch(
  () => props.locations,
  (list) => {
    if (list.length === 1) {
      form.maintenanceEntityId = list[0].id
    } else if (list.length > 1 && form.maintenanceEntityId != null) {
      const exists = list.some((l) => l.id === form.maintenanceEntityId)
      if (!exists) form.maintenanceEntityId = null
    }
  },
  { immediate: true },
)

function onFileChange(e) {
  const f = e.target.files?.[0]
  form.attachment = f || null
  e.target.value = ''
}

function clearFile() {
  form.attachment = null
  if (fileInput.value) fileInput.value.value = ''
}

function resetForm() {
  form.title = ''
  form.issueType = 'service'
  form.priority = 'normal'
  form.description = ''
  clearFile()
  showDescription.value = false
  submitError.value = ''
  submitSuccess.value = ''
  if (props.locations.length === 1) {
    form.maintenanceEntityId = props.locations[0].id
  } else {
    form.maintenanceEntityId = null
  }
}

/**
 * Соберёт payload для OkDesk POST /api/v1/issues (после появления бэкенда-прокси).
 */
function buildPayloadForOkDesk() {
  const payload = {
    issue: {
      title: form.title.trim(),
      type: form.issueType,
      priority: form.priority,
      maintenance_entity_id: String(resolvedLocationId.value),
    },
  }
  const desc = form.description.trim()
  if (desc) payload.issue.description = desc
  return payload
}

async function submit() {
  submitError.value = ''
  submitSuccess.value = ''
  if (!canSubmit.value) {
    submitError.value = 'Заполните обязательные поля.'
    return
  }
  submitting.value = true
  try {
    const payload = buildPayloadForOkDesk()
    // Заглушка: интеграция с OkDesk / прокси API — позже
    await new Promise((r) => setTimeout(r, 400))
    console.info('[IssueCreateForm] draft payload for OkDesk:', payload)
    if (form.attachment) {
      console.info('[IssueCreateForm] attachment (multipart):', form.attachment.name, form.attachment.size, 'bytes')
    }
    submitSuccess.value =
      'Черновик заявки подготовлен. После настройки OkDesk отправка выполнится автоматически; данные выведены в консоль (F12).'
    resetForm()
  } catch (e) {
    submitError.value = e?.message || 'Не удалось обработать форму.'
  } finally {
    submitting.value = false
  }
}
</script>
