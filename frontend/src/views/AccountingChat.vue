<template>
  <div class="flex h-[calc(100vh-4rem)] md:h-[calc(100vh-3rem)] bg-gray-100 rounded-xl shadow-sm overflow-hidden">

    <!-- Левая панель: список локаций -->
    <div
      class="flex flex-col border-r border-gray-200 bg-white/90 backdrop-blur transition-all duration-200"
      :class="mobileSidebarOpen ? 'absolute inset-y-0 left-0 z-20 w-[260px] shadow-xl' : 'hidden md:flex w-[260px]'"
    >
      <div class="px-4 py-3 border-b border-gray-100 shrink-0">
        <div class="font-semibold text-gray-900 flex items-center gap-2 text-base">
          <span>💼</span> Бухгалтерия
        </div>
        <p class="text-xs text-gray-400 mt-0.5">Выберите точку для общения</p>
      </div>

      <div class="flex-1 overflow-y-auto py-1">
        <!-- Скелетон загрузки -->
        <template v-if="loadingLocations">
          <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-4 py-3 animate-pulse">
            <div class="h-10 w-10 rounded-full bg-gray-200 shrink-0"></div>
            <div class="flex-1 space-y-1.5">
              <div class="h-3 bg-gray-200 rounded w-3/4"></div>
              <div class="h-2.5 bg-gray-100 rounded w-1/2"></div>
            </div>
          </div>
        </template>

        <template v-else>
          <div v-if="!locations.length" class="px-4 py-6 text-sm text-gray-400 text-center">
            Нет активных точек
          </div>
          <button
            v-for="loc in locations"
            :key="loc.id"
            type="button"
            class="w-full text-left flex items-center gap-3 px-4 py-3 hover:bg-primary/5 border-l-2 transition-colors"
            :class="selectedLocation?.id === loc.id
              ? 'border-primary bg-primary/5'
              : 'border-transparent'"
            @click="selectLocation(loc); mobileSidebarOpen = false"
          >
            <div
              class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold text-base shrink-0"
              :class="locationColor(loc.id)"
            >
              {{ loc.name[0]?.toUpperCase() }}
            </div>
            <div class="min-w-0">
              <div class="font-medium text-sm text-gray-900 truncate">{{ loc.name }}</div>
              <div class="text-xs text-gray-400 truncate">
                {{ lastMessages[loc.id] || 'Нет сообщений' }}
              </div>
            </div>
          </button>
        </template>
      </div>
    </div>

    <!-- Overlay мобильного сайдбара -->
    <div
      v-if="mobileSidebarOpen"
      class="fixed inset-0 z-10 bg-black/40 md:hidden"
      @click="mobileSidebarOpen = false"
    />

    <!-- Правая часть: чат -->
    <div class="flex-1 flex flex-col bg-slate-50 min-w-0">

      <!-- Шапка чата -->
      <header class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 bg-white/90 backdrop-blur shrink-0">
        <!-- Кнопка списка (мобильный) -->
        <button
          type="button"
          class="md:hidden h-9 w-9 flex items-center justify-center rounded-full hover:bg-gray-100 shrink-0"
          @click="mobileSidebarOpen = true"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <template v-if="selectedLocation">
          <div
            class="h-10 w-10 rounded-full flex items-center justify-center text-white font-bold text-base shrink-0"
            :class="locationColor(selectedLocation.id)"
          >
            {{ selectedLocation.name[0]?.toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-gray-900 truncate">{{ selectedLocation.name }}</div>
            <div class="text-xs text-gray-400">Чат бухгалтерии</div>
          </div>
          <span class="hidden md:inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 text-xs shrink-0">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Онлайн
          </span>
        </template>
        <template v-else>
          <div class="text-sm text-gray-400">Выберите точку слева</div>
        </template>
      </header>

      <!-- Лента сообщений -->
      <div ref="scrollArea" class="flex-1 overflow-y-auto px-3 md:px-6 py-4 space-y-2">

        <div v-if="!selectedLocation" class="flex flex-col items-center justify-center h-full text-gray-400 text-sm text-center gap-2">
          <span class="text-4xl">💼</span>
          <p class="font-medium text-gray-500">Выберите точку в списке слева</p>
          <p class="text-xs">Каждая точка — отдельный чат для бухгалтерии</p>
        </div>

        <div v-else-if="loadingMessages" class="flex justify-center py-8">
          <div class="flex items-center gap-2 text-gray-400 text-sm">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            Загрузка...
          </div>
        </div>

        <div
          v-else-if="messages.length === 0"
          class="flex flex-col items-center justify-center h-full text-gray-400 text-sm text-center gap-1"
        >
          <p>В этом чате пока нет сообщений.</p>
          <p class="text-xs">Напишите первое!</p>
        </div>

        <template v-else>
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="flex w-full"
            :class="msg.isMine ? 'justify-end' : 'justify-start'"
          >
            <div class="max-w-[80%] md:max-w-[68%] flex flex-col gap-0.5" :class="msg.isMine ? 'items-end' : 'items-start'">
              <div
                class="px-3 py-2 rounded-2xl shadow-sm text-sm whitespace-pre-wrap break-words"
                :class="msg.isMine
                  ? 'bg-primary text-white rounded-br-sm'
                  : 'bg-white rounded-bl-sm text-gray-900'"
              >
                <div v-if="!msg.isMine" class="text-xs font-medium mb-1 opacity-70" v-text="msg.author"></div>
                <div v-if="msg.text" v-text="msg.text"></div>

                <div v-if="msg.files && msg.files.length" class="mt-2 space-y-1.5">
                  <div v-for="file in msg.files" :key="file.id || file.url">
                    <!-- Изображение -->
                    <button
                      v-if="file.isImage"
                      type="button"
                      class="block"
                      @click="openAttachmentPreview(file)"
                    >
                      <img
                        :src="file.safeUrl"
                        :alt="file.name"
                        class="max-h-48 rounded-lg object-cover border border-black/10"
                        loading="lazy"
                      />
                    </button>
                    <!-- Прочий файл -->
                    <div
                      v-else
                      class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-black/5 hover:bg-black/10 text-xs"
                    >
                      <button
                        type="button"
                        class="min-w-0 flex-1 flex items-center gap-2 text-left"
                        @click="openAttachmentPreview(file)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828M16 5l3 3-9 9a4 4 0 11-5.657-5.657L13 3l3 2z" />
                        </svg>
                        <span class="truncate max-w-[160px]">{{ file.name }}</span>
                      </button>
                      <a
                        v-if="file.safeUrl"
                        :href="file.safeUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="shrink-0 text-[10px] opacity-70 hover:opacity-100 underline"
                      >
                        Открыть
                      </a>
                      <span v-else class="shrink-0 text-[10px] opacity-50">
                        Ссылка заблокирована
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="relative px-1 mt-1 flex items-center gap-1 flex-wrap">
                <button
                  v-for="reaction in msg.reactions"
                  :key="reaction.emoji"
                  type="button"
                  class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] transition"
                  :class="reaction.reactedByMe
                    ? 'border-primary/50 bg-primary/10 text-primary'
                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                  :disabled="reactingMessageIds[msg.id]"
                  @click="toggleReaction(msg, reaction.emoji)"
                >
                  <span>{{ reaction.emoji }}</span>
                  <span>{{ reaction.count }}</span>
                </button>
                <button
                  type="button"
                  class="inline-flex items-center justify-center h-6 w-6 rounded-full border border-gray-200 bg-white text-xs hover:bg-gray-50"
                  :disabled="reactingMessageIds[msg.id]"
                  @click="toggleReactionPicker(msg.id)"
                >
                  +
                </button>

                <div
                  v-if="reactionPickerMessageId === msg.id"
                  class="absolute top-7 z-10 bg-white border border-gray-200 rounded-xl shadow-lg p-1.5 flex items-center gap-1"
                  @click.stop
                >
                  <button
                    v-for="emoji in REACTION_EMOJIS"
                    :key="emoji"
                    type="button"
                    class="h-7 w-7 rounded-lg hover:bg-gray-100 text-base flex items-center justify-center"
                    :disabled="reactingMessageIds[msg.id]"
                    @click="toggleReaction(msg, emoji); reactionPickerMessageId = null"
                  >
                    {{ emoji }}
                  </button>
                </div>
              </div>
              <div class="text-[10px] text-gray-400 px-1">{{ formatTime(msg.createdAt) }}</div>
            </div>
          </div>
        </template>
      </div>

      <!-- Поле ввода -->
      <form
        v-if="selectedLocation"
        class="border-t border-gray-200 bg-white/90 backdrop-blur px-3 md:px-4 py-2 shrink-0"
        @submit.prevent="handleSend"
      >
        <div v-if="attachedFiles.length" class="flex flex-wrap gap-1 mb-2">
          <div
            v-for="(file, index) in attachedFiles"
            :key="index"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/5 border border-primary/20 text-[11px] text-primary"
          >
            <span class="max-w-[120px] truncate">{{ file.name }}</span>
            <button type="button" class="hover:text-primary/60 font-bold" @click="removeFile(index)">×</button>
          </div>
        </div>

        <div class="flex items-end gap-2">
          <textarea
            v-model="draft"
            class="flex-1 resize-none rounded-2xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/60 focus:border-primary/60 max-h-32 min-h-[40px]"
            :placeholder="`Написать в ${selectedLocation.name}...`"
            rows="1"
            @keydown.enter.exact.prevent="handleSend"
            @keydown.enter.shift.exact="draft += '\n'"
          />

          <label class="h-9 w-9 flex items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:text-primary cursor-pointer shrink-0">
            <input ref="fileInput" type="file" class="hidden" multiple @change="handleFilesChange" />
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828M16 5l3 3-9 9a4 4 0 11-5.657-5.657L13 3l3 2z" />
            </svg>
          </label>

          <button
            type="submit"
            class="h-9 w-9 flex items-center justify-center rounded-full bg-primary text-white hover:bg-primary-dark disabled:opacity-50 shrink-0 shadow-sm"
            :disabled="sending || (!draft.trim() && !attachedFiles.length)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>

        <p v-if="sendError" class="mt-1 text-xs text-red-500">{{ sendError }}</p>
      </form>
    </div>

    <!-- Превью вложений -->
    <Teleport to="body">
      <div
        v-if="previewAttachment"
        class="fixed inset-0 z-[2000] bg-black/80 flex items-center justify-center p-4"
        @click.self="closeAttachmentPreview"
      >
        <div class="relative max-w-4xl w-full max-h-[92vh] bg-white rounded-xl shadow-2xl overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-gray-900 truncate">{{ previewAttachment.name }}</div>
              <div class="text-xs text-gray-400">{{ previewAttachment.previewKindLabel }}</div>
            </div>
            <button
              type="button"
              class="h-8 w-8 rounded-full hover:bg-gray-100 text-gray-600 flex items-center justify-center"
              @click="closeAttachmentPreview"
            >&times;</button>
          </div>

          <div class="p-4 max-h-[calc(92vh-64px)] overflow-auto flex items-center justify-center">
            <img
              v-if="previewAttachment.previewKind === 'image'"
              :src="previewAttachment.url"
              :alt="previewAttachment.name"
              class="w-full h-auto max-h-[82vh] object-contain rounded-lg"
            />
            <video
              v-else-if="previewAttachment.previewKind === 'video'"
              controls
              class="w-full max-h-[82vh] rounded-lg bg-black"
            >
              <source :src="previewAttachment.url" :type="previewAttachment.mimeType || undefined" />
            </video>
            <audio
              v-else-if="previewAttachment.previewKind === 'audio'"
              controls
              class="w-full"
            >
              <source :src="previewAttachment.url" :type="previewAttachment.mimeType || undefined" />
            </audio>
            <iframe
              v-else-if="previewAttachment.previewKind === 'pdf'"
              :src="previewAttachment.url"
              class="w-full h-[78vh] rounded-lg border border-gray-200"
              title="PDF preview"
            />
            <div v-else-if="previewAttachment.previewKind === 'text'" class="w-full">
              <div v-if="previewTextLoading" class="text-sm text-gray-500">Загрузка файла...</div>
              <div v-else-if="previewTextError" class="text-sm text-red-500">{{ previewTextError }}</div>
              <pre
                v-else
                class="w-full text-xs leading-5 bg-gray-50 border border-gray-200 rounded-lg p-3 overflow-auto max-h-[76vh] whitespace-pre-wrap break-words"
              >{{ previewTextContent }}</pre>
            </div>
            <div v-else class="text-center text-sm text-gray-600">
              <p class="mb-2">Предпросмотр для этого типа файла недоступен.</p>
              <a
                :href="previewAttachment.url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary underline"
              >
                Открыть файл в новой вкладке
              </a>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { api, baseURL } from '@/api/client'
import { getCurrentUser } from '@/router'
import { sanitizeTrustedUrl } from '@/utils/security'
import { compressImagesInList } from '@/utils/imageUpload'

// ─── состояние ───────────────────────────────────────────────────────────────
const locations        = ref([])
const loadingLocations = ref(false)
const selectedLocation = ref(null)
const mobileSidebarOpen = ref(false)

const messages        = ref([])
const loadingMessages = ref(false)
const lastMessages    = ref({})   // loc.id → последний текст

const draft         = ref('')
const attachedFiles = ref([])
const sending       = ref(false)
const sendError     = ref('')
const scrollArea    = ref(null)
const fileInput     = ref(null)
const previewAttachment = ref(null)
const previewTextContent = ref('')
const previewTextLoading = ref(false)
const previewTextError = ref('')
const reactionPickerMessageId = ref(null)
const reactingMessageIds = ref({})

const currentUser = ref(null)
const REACTION_EMOJIS = ['👍', '❤️', '🔥', '😂', '👏', '😢', '😡', '🎉']

// Цвета аватаров локаций (циклически)
const COLORS = [
  'bg-gradient-to-br from-amber-400 to-pink-500',
  'bg-gradient-to-br from-sky-400 to-indigo-500',
  'bg-gradient-to-br from-emerald-400 to-teal-600',
  'bg-gradient-to-br from-violet-500 to-purple-700',
  'bg-gradient-to-br from-orange-400 to-red-500',
  'bg-gradient-to-br from-lime-400 to-green-600',
]
function locationColor(id) {
  return COLORS[(id - 1) % COLORS.length]
}

// ─── загрузка локаций ─────────────────────────────────────────────────────────
async function loadLocations() {
  loadingLocations.value = true
  try {
    const data = await api.accounting.locations()
    locations.value = data.locations || []

    if (locations.value.length && !selectedLocation.value) {
      // Автовыбор: точка пользователя → иначе первая
      const userLocId = currentUser.value?.locationId
      const preferred = userLocId
        ? locations.value.find((l) => l.id === userLocId)
        : null
      selectLocation(preferred || locations.value[0])
    }
  } catch (e) {
    console.error('accounting locations:', e)
  } finally {
    loadingLocations.value = false
  }
}

// ─── живые превью в сайдбаре ──────────────────────────────────────────────────
async function loadLastMessages() {
  try {
    const data = await api.accounting.lastMessages()
    const map = data.lastMessages || {}
    Object.entries(map).forEach(([locId, msg]) => {
      let preview = ''
      if (msg.text) {
        preview = msg.text.length > 40 ? msg.text.slice(0, 40) + '…' : msg.text
      } else if (msg.hasFiles) {
        preview = '📎 файл'
      }
      lastMessages.value[Number(locId)] = preview
    })
  } catch (e) {
    // тихо — не критично
  }
}

// ─── выбор локации ────────────────────────────────────────────────────────────
function selectLocation(loc) {
  selectedLocation.value = loc
  messages.value = []
  reactionPickerMessageId.value = null
  loadMessages(true)
}

// ─── загрузка сообщений ───────────────────────────────────────────────────────
async function loadMessages(showLoader = false) {
  if (!selectedLocation.value) return
  if (showLoader) loadingMessages.value = true
  sendError.value = ''
  try {
    if (!currentUser.value) {
      currentUser.value = await getCurrentUser()
    }
    const data = await api.accounting.messages(selectedLocation.value.id)
    const userId = currentUser.value?.id
    messages.value = normalize(data.messages || [], userId)

    // обновляем превью последнего сообщения в боковой панели
    if (messages.value.length) {
      const last = messages.value[messages.value.length - 1]
      lastMessages.value[selectedLocation.value.id] =
        (last.text ? last.text.slice(0, 40) : (last.files?.length ? '📎 файл' : ''))
    }

    await nextTick()
    scrollToBottom()
  } catch (e) {
    console.error('accounting messages:', e)
  } finally {
    loadingMessages.value = false
  }
}

function normalize(raw, userId) {
  return raw.map((m) => ({
    id: m.id,
    text: m.text || '',
    author: m.author || '',
    createdAt: m.created_at || m.createdAt || null,
    isMine: !!userId && (m.user_id === userId || m.userId === userId),
    files: (m.files || []).map((f, i) => {
      const url  = f.url || ''
      const safeUrl = sanitizeTrustedUrl(url, [baseURL])
      const name = f.name || url.split('/').pop() || `file-${i + 1}`
      const mimeType = f.mimeType || f.mime_type || ''
      const ext = getFileExtension(name)
      const isImage = !!safeUrl && (IMAGE_EXTENSIONS.has(ext) || mimeType.startsWith('image/'))
      const previewKind = detectPreviewKind(ext, mimeType, isImage)
      return {
        id: f.id || `${name}-${i}`,
        url,
        safeUrl,
        name,
        mimeType,
        isImage,
        previewKind,
        previewKindLabel: PREVIEW_KIND_LABELS[previewKind],
      }
    }),
    reactions: normalizeReactions(m.reactions || []),
  }))
}

function normalizeReactions(raw) {
  return (raw || [])
    .map((r) => ({
      emoji: r.emoji || '',
      count: Number(r.count || 0),
      reactedByMe: !!r.reactedByMe,
    }))
    .filter((r) => r.emoji && r.count > 0)
}

const IMAGE_EXTENSIONS = new Set(['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'avif'])
const VIDEO_EXTENSIONS = new Set(['mp4', 'webm', 'mov', 'ogg', 'm4v'])
const AUDIO_EXTENSIONS = new Set(['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'])
const TEXT_EXTENSIONS = new Set(['txt', 'csv', 'json', 'xml', 'md', 'log', 'yml', 'yaml'])
const PREVIEW_KIND_LABELS = {
  image: 'Изображение',
  video: 'Видео',
  audio: 'Аудио',
  pdf: 'PDF',
  text: 'Текстовый файл',
  other: 'Файл',
}

function getFileExtension(name) {
  const cleanName = String(name || '').split('?')[0].split('#')[0]
  const dot = cleanName.lastIndexOf('.')
  if (dot === -1) return ''
  return cleanName.slice(dot + 1).toLowerCase()
}

function detectPreviewKind(ext, mimeType, isImage) {
  if (isImage) return 'image'
  if (mimeType.startsWith('video/') || VIDEO_EXTENSIONS.has(ext)) return 'video'
  if (mimeType.startsWith('audio/') || AUDIO_EXTENSIONS.has(ext)) return 'audio'
  if (mimeType === 'application/pdf' || ext === 'pdf') return 'pdf'
  if (mimeType.startsWith('text/') || mimeType.includes('json') || TEXT_EXTENSIONS.has(ext)) return 'text'
  return 'other'
}

async function openAttachmentPreview(file) {
  if (!file?.safeUrl) {
    previewAttachment.value = null
    previewTextContent.value = ''
    previewTextError.value = 'Ссылка на вложение отклонена политикой безопасности.'
    previewTextLoading.value = false
    return
  }
  previewAttachment.value = file
  previewTextContent.value = ''
  previewTextError.value = ''
  if (file.previewKind !== 'text') return

  previewTextLoading.value = true
  try {
    const response = await fetch(file.safeUrl)
    if (!response.ok) throw new Error('bad status')
    previewTextContent.value = await response.text()
  } catch {
    previewTextError.value = 'Не удалось загрузить предпросмотр. Откройте файл в новой вкладке.'
  } finally {
    previewTextLoading.value = false
  }
}

function closeAttachmentPreview() {
  previewAttachment.value = null
  previewTextContent.value = ''
  previewTextError.value = ''
  previewTextLoading.value = false
}

function toggleReactionPicker(messageId) {
  reactionPickerMessageId.value = reactionPickerMessageId.value === messageId ? null : messageId
}

async function toggleReaction(message, emoji) {
  const messageId = Number(message?.id || 0)
  if (!messageId || !emoji) return
  if (reactingMessageIds.value[messageId]) return

  reactingMessageIds.value[messageId] = true
  try {
    const data = await api.accounting.reactMessage({ message_id: messageId, emoji })
    const target = messages.value.find((m) => m.id === messageId)
    if (target) {
      target.reactions = normalizeReactions(data?.reactions || [])
    }
  } catch (e) {
    console.error('accounting react:', e)
  } finally {
    reactingMessageIds.value[messageId] = false
  }
}

function scrollToBottom() {
  const el = scrollArea.value
  if (el) el.scrollTop = el.scrollHeight
}

// ─── отправка ─────────────────────────────────────────────────────────────────
async function handleSend() {
  if (sending.value || !selectedLocation.value) return
  const text = draft.value.trim()
  if (!text && !attachedFiles.value.length) return

  sending.value = true
  sendError.value = ''
  try {
    const form = new FormData()
    form.append('location_id', selectedLocation.value.id)
    if (text) form.append('text', text)
    const preparedFiles = await compressImagesInList(attachedFiles.value)
    preparedFiles.forEach((f, i) => form.append('files[]', f, f.name || `file-${i + 1}`))

    const data = await api.accounting.sendMessage(form)
    const userId = currentUser.value?.id
    const appended = normalize(
      Array.isArray(data?.messages) ? data.messages : [data.message || data],
      userId
    )
    messages.value = messages.value.concat(appended)

    // обновляем превью
    if (appended.length) {
      const last = appended[appended.length - 1]
      lastMessages.value[selectedLocation.value.id] =
        (last.text ? last.text.slice(0, 40) : (last.files?.length ? '📎 файл' : ''))
    }

    draft.value = ''
    attachedFiles.value = []
    if (fileInput.value) fileInput.value.value = ''
    await nextTick()
    scrollToBottom()
  } catch (e) {
    sendError.value = e?.data?.message || e?.message || 'Не удалось отправить сообщение.'
  } finally {
    sending.value = false
  }
}

function handleFilesChange(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  attachedFiles.value = attachedFiles.value.concat(files).slice(0, 10)
  if (fileInput.value) fileInput.value.value = ''
}
function removeFile(i) { attachedFiles.value.splice(i, 1) }

function formatTime(value) {
  if (!value) return ''
  try {
    const d = new Date(value)
    if (isNaN(d)) return ''
    return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
  } catch { return '' }
}

// ─── поллинг ──────────────────────────────────────────────────────────────────
let pollMessages = null
let pollSidebar  = null

onMounted(async () => {
  currentUser.value = await getCurrentUser()
  await loadLocations()
  // Сразу загрузим превью для всех локаций
  await loadLastMessages()
  // Поллинг сообщений активной локации — каждые 10 с
  pollMessages = setInterval(() => loadMessages(false), 10000)
  // Поллинг превью сайдбара — каждые 15 с
  pollSidebar = setInterval(loadLastMessages, 15000)
})

onBeforeUnmount(() => {
  if (pollMessages) clearInterval(pollMessages)
  if (pollSidebar)  clearInterval(pollSidebar)
  reactionPickerMessageId.value = null
})

// При смене локации — перезапускаем поллинг сообщений
watch(selectedLocation, () => {
  if (pollMessages) clearInterval(pollMessages)
  pollMessages = setInterval(() => loadMessages(false), 10000)
})
</script>
