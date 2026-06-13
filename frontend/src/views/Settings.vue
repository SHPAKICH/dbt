<template>
  <div class="max-w-3xl mx-auto px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Настройки</h1>

    <div class="space-y-6">
      <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <h2 class="px-6 py-4 text-lg font-semibold text-gray-800 border-b border-gray-100">Внешний вид</h2>
        <div class="divide-y divide-gray-100">
          <div class="flex items-center justify-between px-6 py-4">
            <div>
              <p class="font-medium text-gray-800">Тёмная тема</p>
              <p class="text-sm text-gray-500 mt-0.5">Включить тёмное оформление интерфейса</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="settings.darkTheme" class="sr-only peer" @change="save" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              <span class="ml-2 text-sm text-gray-600">Вкл.</span>
            </label>
          </div>
          <div class="flex items-center justify-between px-6 py-4">
            <div>
              <p class="font-medium text-gray-800">Компактный вид таблиц</p>
              <p class="text-sm text-gray-500 mt-0.5">Уменьшить отступы в таблицах графиков и отчётов</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="settings.compactTables" class="sr-only peer" @change="save" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              <span class="ml-2 text-sm text-gray-600">Вкл.</span>
            </label>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <h2 class="px-6 py-4 text-lg font-semibold text-gray-800 border-b border-gray-100">Уведомления</h2>
        <div class="px-6 py-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-gray-800">Напоминания о сменах</p>
              <p class="text-sm text-gray-500 mt-0.5">Получать push-уведомления о предстоящих сменах</p>
              <p class="text-xs mt-2" :class="pushState.error ? 'text-red-600' : 'text-gray-500'">
                {{ pushState.error || pushState.status }}
              </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                class="sr-only peer"
                :checked="settings.shiftReminders"
                :disabled="pushState.busy || !pushSupported"
                @change="onShiftReminderToggle"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              <span class="ml-2 text-sm text-gray-600">{{ pushState.busy ? '...' : 'Вкл.' }}</span>
            </label>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <h2 class="px-6 py-4 text-lg font-semibold text-gray-800 border-b border-gray-100">Дата и время</h2>
        <div class="px-6 py-4">
          <p class="font-medium text-gray-800">Формат даты</p>
          <p class="text-sm text-gray-500 mt-0.5 mb-2">Как отображать даты в графиках и отчётах</p>
          <select
            v-model="settings.dateFormat"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
            @change="save"
          >
            <option value="d.m.Y">31.12.2025</option>
            <option value="d.m.y">31.12.25</option>
            <option value="Y-m-d">2025-12-31</option>
          </select>
        </div>
      </section>

      <section
        v-if="canSeeStock"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
      >
        <h2 class="px-6 py-4 text-lg font-semibold text-gray-800 border-b border-gray-100">Подключение к iiko</h2>
        <div class="px-6 py-4 space-y-4">
          <p class="text-sm text-gray-500">
            Ссылка на сервер, логин и пароль учётной записи (как в iikoChain). Нужно для остатков и просмотра актов по товару.
            Данные хранятся только в вашем браузере.
          </p>
          <div>
            <label class="block text-sm font-medium text-gray-800 mb-1">URL iiko-сервера</label>
            <input
              v-model="iiko.baseUrl"
              type="url"
              placeholder="https://your-iiko-server:443"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
              @change="saveIiko"
            />
            <p class="text-xs text-gray-500 mt-1">Например: http://localhost:8080 или https://example.iiko.it:443</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1">Логин</label>
              <input
                v-model="iiko.login"
                type="text"
                autocomplete="username"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                @change="saveIiko"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1">
                Пароль
                <span v-if="iikoHasPassword" class="text-xs font-normal text-emerald-600">· сохранён</span>
              </label>
              <input
                v-model="iikoPasswordInput"
                type="password"
                autocomplete="current-password"
                :placeholder="iikoHasPassword ? 'Оставьте пустым, чтобы не менять' : '••••••••'"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                @change="saveIikoPassword"
              />
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
              :disabled="iikoTest.busy"
              @click="testIiko"
            >
              {{ iikoTest.busy ? 'Проверка…' : 'Проверить подключение' }}
            </button>
            <router-link
              v-if="iikoSettingsComplete()"
              to="/stock"
              class="text-sm text-primary font-medium hover:underline"
            >
              Остатки на складе →
            </router-link>
            <router-link
              v-if="iikoSettingsComplete()"
              to="/admin/analytics"
              class="text-sm text-primary font-medium hover:underline"
            >
              Аналитика →
            </router-link>
            <router-link
              v-if="iikoSettingsComplete()"
              to="/admin/analytics?tab=checks"
              class="text-sm text-primary font-medium hover:underline"
            >
              Чеки →
            </router-link>
            <router-link
              v-if="iikoSettingsComplete()"
              to="/admin/analytics?tab=reports"
              class="text-sm text-primary font-medium hover:underline"
            >
              Отчёты по продажам →
            </router-link>
          </div>
          <p v-if="iikoTest.error" class="text-sm text-red-600">{{ iikoTest.error }}</p>
          <p v-else-if="iikoTest.ok" class="text-sm text-green-700">
            Подключение успешно{{ iikoTest.version ? ` (версия: ${iikoTest.version})` : '' }}
          </p>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <h2 class="px-6 py-4 text-lg font-semibold text-gray-800 border-b border-gray-100">Безопасность</h2>
        <div class="px-6 py-4 space-y-4">
          <div>
            <p class="font-medium text-gray-800">Привязка Telegram</p>
            <p class="text-sm text-gray-500 mt-0.5">
              Нужна для восстановления пароля через бота. Откройте бота и отправьте команду с кодом.
            </p>
            <p v-if="telegram.error" class="text-sm text-red-600 mt-2">{{ telegram.error }}</p>
            <p v-else-if="telegram.message" class="text-sm text-green-700 mt-2">{{ telegram.message }}</p>

            <div v-if="telegram.isLinked" class="mt-3 flex flex-wrap items-center gap-3">
              <span class="text-sm text-gray-600">Telegram привязан</span>
              <button
                type="button"
                class="text-sm text-red-600 hover:underline"
                :disabled="telegram.busy"
                @click="unlinkTelegram"
              >
                Отвязать
              </button>
            </div>

            <div v-else-if="telegram.code" class="mt-3 p-3 rounded-lg bg-gray-50 border border-gray-200 text-sm">
              <p class="font-medium text-gray-800">Код: <span class="font-mono text-primary">{{ telegram.code }}</span></p>
              <p class="text-gray-600 mt-1">Действует {{ Math.round(telegram.expiresIn / 60) }} мин.</p>
              <p class="mt-2 text-gray-700">
                Отправьте боту
                <a :href="telegramBotUrl" target="_blank" rel="noopener" class="text-primary font-medium">@{{ telegram.botUsername }}</a>:
              </p>
              <code class="block mt-1 p-2 bg-white rounded border text-gray-800">/link {{ telegram.code }}</code>
            </div>

            <button
              v-if="!telegram.isLinked"
              type="button"
              class="mt-3 px-4 py-2 rounded-lg border border-primary text-primary font-medium hover:bg-primary/5 disabled:opacity-50"
              :disabled="telegram.busy"
              @click="requestTelegramLink"
            >
              {{ telegram.busy ? 'Генерация...' : (telegram.code ? 'Новый код' : 'Получить код привязки') }}
            </button>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <h2 class="px-6 py-4 text-lg font-semibold text-gray-800 border-b border-gray-100">Обратная связь</h2>
        <div class="px-6 py-4">
          <p class="font-medium text-gray-800">Telegram-бот обратной связи</p>
          <p class="text-sm text-gray-500 mt-0.5 mb-3">Напишите о проблемах в работе сервиса или предложениях по улучшению</p>
          <a
            href="https://t.me/dbthub_bot"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0088cc] text-white font-medium hover:bg-[#0077b5] transition-colors"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.693-1.653-1.124-2.678-1.8-1.185-.781-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.242-1.865-.44-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.121.1.154.234.17.33.015.096.034.313.019.484z"/></svg>
            @dbthub_bot
          </a>
        </div>
      </section>

      <div class="bg-gray-100 rounded-xl p-4 text-sm text-gray-600">
        Настройки сохраняются в вашем браузере и применяются только на этом устройстве.
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted, computed, ref } from 'vue'
import { api } from '@/api/client'
import {
  loadIikoSettings,
  saveIikoSettings,
  iikoSettingsComplete,
  getIikoCredentialsPayload,
} from '@/utils/iikoSettings'
import { canAccessStock } from '@/utils/access'
import { getCurrentUser } from '@/router'

const STORAGE_KEY = 'dbt_settings'
const VAPID_PUBLIC_KEY = (import.meta.env.VITE_VAPID_PUBLIC_KEY || '').trim()

const settings = reactive({
  darkTheme: false,
  compactTables: false,
  shiftReminders: false,
  dateFormat: 'd.m.Y',
})
let registration = null
const pushSupported = typeof window !== 'undefined'
  && 'Notification' in window
  && 'serviceWorker' in navigator
  && 'PushManager' in window
const pushState = reactive({
  busy: false,
  status: '',
  error: '',
})
const telegram = reactive({
  busy: false,
  isLinked: false,
  code: '',
  expiresIn: 0,
  botUsername: 'dbthub_bot',
  message: '',
  error: '',
})
const telegramBotUrl = computed(() => `https://t.me/${telegram.botUsername}`)

const currentUser = ref(null)
const canSeeStock = computed(() => canAccessStock(currentUser.value))

const iiko = reactive(loadIikoSettings())
const iikoPasswordInput = ref('')
const iikoHasPassword = computed(() => Boolean(iiko.password))
const iikoTest = reactive({ busy: false, ok: false, error: '', version: '' })
function saveIiko() {
  saveIikoSettings({
    baseUrl: iiko.baseUrl,
    login: iiko.login,
    defaultStoreId: iiko.defaultStoreId,
  })
  iikoTest.ok = false
  iikoTest.error = ''
}

function saveIikoPassword() {
  if (iikoPasswordInput.value) {
    saveIikoSettings({ password: iikoPasswordInput.value })
    iiko.password = iikoPasswordInput.value
    iikoPasswordInput.value = ''
  }
  iikoTest.ok = false
  iikoTest.error = ''
}

async function testIiko() {
  const creds = getIikoCredentialsPayload()
  if (!creds) {
    iikoTest.error = 'Заполните URL, логин и пароль'
    iikoTest.ok = false
    return
  }
  iikoTest.busy = true
  iikoTest.error = ''
  iikoTest.ok = false
  try {
    const res = await api.iikoStock.test(creds)
    if (res.ok) {
      iikoTest.ok = true
      iikoTest.version = res.version || ''
    } else {
      iikoTest.error = res.message || 'Ошибка подключения'
    }
  } catch (e) {
    iikoTest.error = e.data?.message || e.message || 'Ошибка'
  } finally {
    iikoTest.busy = false
  }
}

async function requestTelegramLink() {
  telegram.busy = true
  telegram.error = ''
  telegram.message = ''
  try {
    const res = await api.profile.telegramLink()
    telegram.code = res.code || ''
    telegram.expiresIn = res.expiresIn || 900
    telegram.botUsername = res.botUsername || 'dbthub_bot'
    telegram.isLinked = !!res.isLinked
    telegram.message = res.instruction || 'Отправьте команду боту'
  } catch (e) {
    telegram.error = e.data?.message || e.message || 'Не удалось получить код'
  } finally {
    telegram.busy = false
  }
}

async function unlinkTelegram() {
  telegram.busy = true
  telegram.error = ''
  try {
    await api.profile.telegramUnlink()
    telegram.isLinked = false
    telegram.code = ''
    telegram.message = 'Telegram отвязан'
  } catch (e) {
    telegram.error = e.data?.message || e.message || 'Не удалось отвязать'
  } finally {
    telegram.busy = false
  }
}

function load() {
  try {
    const s = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
    settings.darkTheme = !!s.darkTheme
    settings.compactTables = !!s.compactTables
    settings.shiftReminders = !!s.shiftReminders
    if (s.dateFormat) settings.dateFormat = s.dateFormat
    apply()
  } catch (_) {}
}

function save() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({
    darkTheme: settings.darkTheme,
    compactTables: settings.compactTables,
    shiftReminders: settings.shiftReminders,
    dateFormat: settings.dateFormat,
  }))
  apply()
}

function apply() {
  document.body.classList.toggle('theme-dark', settings.darkTheme)
  document.body.classList.toggle('compact-tables', settings.compactTables)
  const meta = document.querySelector('meta[name="theme-color"]')
  if (meta) meta.content = settings.darkTheme ? '#1a1a1a' : '#FF6F61'
}

function arrayBufferToBase64(buffer) {
  if (!buffer) return ''
  const bytes = new Uint8Array(buffer)
  let binary = ''
  bytes.forEach((b) => { binary += String.fromCharCode(b) })
  return btoa(binary)
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = atob(base64)
  return Uint8Array.from([...rawData].map((ch) => ch.charCodeAt(0)))
}

async function getRegistration() {
  if (registration) return registration
  registration = await navigator.serviceWorker.register('/sw.js')
  await navigator.serviceWorker.ready
  return registration
}

async function syncPushState() {
  pushState.error = ''
  if (!pushSupported) {
    settings.shiftReminders = false
    pushState.status = 'Браузер не поддерживает push-уведомления.'
    save()
    return
  }
  try {
    const reg = await getRegistration()
    const subscription = await reg.pushManager.getSubscription()
    settings.shiftReminders = !!subscription
    pushState.status = subscription ? 'Push-уведомления включены.' : 'Push-уведомления выключены.'
    save()
  } catch (e) {
    settings.shiftReminders = false
    pushState.error = e?.message || 'Не удалось проверить push-подписку.'
  }
}

async function onShiftReminderToggle(event) {
  const targetEnabled = !!event?.target?.checked
  pushState.error = ''
  if (!pushSupported) {
    settings.shiftReminders = false
    save()
    return
  }
  pushState.busy = true
  try {
    const reg = await getRegistration()
    let sub = await reg.pushManager.getSubscription()

    if (targetEnabled) {
      if (Notification.permission === 'denied') {
        throw new Error('Разрешение на уведомления заблокировано в браузере.')
      }
      if (Notification.permission !== 'granted') {
        const perm = await Notification.requestPermission()
        if (perm !== 'granted') {
          throw new Error('Разрешение на уведомления не выдано.')
        }
      }
      if (!sub) {
        const subscribeOptions = { userVisibleOnly: true }
        if (VAPID_PUBLIC_KEY) {
          subscribeOptions.applicationServerKey = urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        }
        sub = await reg.pushManager.subscribe(subscribeOptions)
      }
      await api.push.subscribe({
        endpoint: sub.endpoint,
        p256dh: arrayBufferToBase64(sub.getKey('p256dh')),
        auth: arrayBufferToBase64(sub.getKey('auth')),
      })
      settings.shiftReminders = true
      pushState.status = 'Push-уведомления включены.'
      save()
      return
    }

    if (sub) {
      await api.push.unsubscribe(sub.endpoint).catch(() => {})
      await sub.unsubscribe()
    }
    settings.shiftReminders = false
    pushState.status = 'Push-уведомления выключены.'
    save()
  } catch (e) {
    settings.shiftReminders = false
    pushState.error = e?.data?.message || e?.message || 'Не удалось изменить настройки push.'
  } finally {
    pushState.busy = false
  }
}

onMounted(async () => {
  currentUser.value = await getCurrentUser().catch(() => null)
  load()
  await syncPushState()
  try {
    const res = await api.profile.telegramStatus()
    telegram.isLinked = !!res.isLinked
    telegram.botUsername = res.botUsername || 'dbthub_bot'
  } catch {
    // API недоступен
  }
})
</script>
