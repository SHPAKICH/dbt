<template>
  <div :class="embedded ? 'h-full overflow-y-auto px-4 py-4 md:px-6' : 'max-w-6xl mx-auto px-4 pb-10'">
    <template v-if="!embedded">
      <h1 class="text-2xl font-bold text-gray-800 mb-1">Чеки</h1>
      <p class="text-sm text-gray-500 mb-6">
        Продажи из iiko: список чеков или сводка по смене (выручка по часам, типам оплаты и дням).
      </p>
    </template>

    <div v-if="!settingsReady" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 max-w-xl text-center">
      <h2 class="text-xl font-semibold text-gray-800 mb-2">Сначала настройте подключение к iiko</h2>
      <p class="text-sm text-gray-500 mb-4">URL сервера, логин и пароль — в разделе «Настройки».</p>
      <router-link to="/settings" class="inline-flex px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90">
        Перейти к настройкам
      </router-link>
    </div>

    <template v-else>
      <div class="flex border-b border-gray-200 mb-4 gap-1">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 -mb-px transition-colors"
          :class="activeTab === 'checks'
            ? 'border-primary text-primary bg-white'
            : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'checks'"
        >
          По чекам
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 -mb-px transition-colors"
          :class="activeTab === 'shift'
            ? 'border-primary text-primary bg-white'
            : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'shift'"
        >
          По смене
        </button>
      </div>

      <div class="flex flex-wrap items-end gap-3 mb-4">
        <div class="min-w-[220px] flex-1">
          <label class="block text-xs font-medium text-gray-600 mb-1">Точка iiko</label>
          <select
            v-model="departmentId"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            :disabled="loadingDepartments"
          >
            <option value="">Все точки</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">
              {{ d.name }}{{ d.code ? ` · ${d.code}` : '' }}
            </option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <div class="flex flex-wrap items-end gap-2">
            <label class="text-xs text-gray-500">с</label>
            <input v-model="periodFrom" type="date" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" />
            <label class="text-xs text-gray-500">по</label>
            <input v-model="periodTo" type="date" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" />
          </div>
          <div class="flex flex-wrap gap-1.5">
            <button
              type="button"
              class="px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors"
              :class="periodPreset === 'today'
                ? 'border-primary bg-primary/10 text-primary'
                : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              :disabled="isLoading"
              @click="setPeriodPreset('today')"
            >
              Сегодня
            </button>
            <button
              type="button"
              class="px-2.5 py-1 rounded-lg border text-xs font-medium transition-colors"
              :class="periodPreset === 'yesterday'
                ? 'border-primary bg-primary/10 text-primary'
                : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
              :disabled="isLoading"
              @click="setPeriodPreset('yesterday')"
            >
              Вчера
            </button>
          </div>
        </div>
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
          :disabled="isLoading"
          @click="refresh"
        >
          {{ isLoading ? 'Загрузка…' : refreshLabel }}
        </button>
      </div>

      <div v-if="error" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 mb-4">
        {{ error }}
      </div>
      <p v-if="warning" class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-4">
        {{ warning }}
      </p>
      <p v-if="lastUpdatedAt && (loadedOnceChecks || shiftLoadedOnce)" class="text-xs text-gray-400 mb-4 -mt-2">
        Обновлено {{ lastUpdatedLabel }} · автообновление каждые {{ pollIntervalSec }} сек
      </p>

      <!-- Вкладка: по чекам -->
      <template v-if="activeTab === 'checks'">
        <div v-if="checks.length" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Чеков</div>
            <div class="text-2xl font-semibold mt-1">{{ checks.length }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Выручка</div>
            <div class="text-2xl font-semibold mt-1 text-primary">{{ formatMoney(totalSum) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Средний чек</div>
            <div class="text-2xl font-semibold mt-1">{{ formatMoney(avgCheck) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Период</div>
            <div class="text-sm font-medium mt-2 text-gray-700">{{ formatPeriodLabel }}</div>
          </div>
        </div>

        <div v-if="loadingChecks && !checks.length" class="py-16 text-center text-gray-500 text-sm">
          Загрузка чеков из iiko…
        </div>
        <div v-else-if="!loadingChecks && loadedOnceChecks && !checks.length" class="py-16 text-center text-gray-500 text-sm">
          За выбранный период чеков не найдено.
        </div>

        <div v-else class="space-y-3">
          <article
            v-for="check in checks"
            :key="checkKey(check)"
            class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md cursor-pointer"
            :class="{ 'ring-2 ring-primary/30': selectedCheck?.orderId === check.orderId }"
            @click="openCheck(check)"
          >
            <div class="px-4 py-3 flex flex-wrap items-center gap-3">
              <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-primary/15 to-primary/5 flex items-center justify-center text-lg">
                🧾
              </div>
              <div class="flex-1 min-w-[140px]">
                <div class="font-semibold text-gray-800">
                  Чек {{ check.orderNum || check.orderId?.slice(0, 8) || '—' }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                  {{ formatCheckDate(check.date) }}
                  <span v-if="check.departmentName" class="ml-1">· {{ check.departmentName }}</span>
                </div>
              </div>
              <div class="text-right">
                <div class="text-lg font-bold text-gray-900 tabular-nums">{{ formatMoney(check.sum) }}</div>
                <div v-if="check.itemsCount" class="text-xs text-gray-500">{{ formatNum(check.itemsCount) }} поз.</div>
              </div>
              <div v-if="check.payType || check.orderType" class="w-full flex flex-wrap gap-1.5 mt-1">
                <span v-if="check.payType" class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                  {{ check.payType }}
                </span>
                <span v-if="check.orderType" class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                  {{ check.orderType }}
                </span>
              </div>
            </div>
          </article>
        </div>
      </template>

      <!-- Вкладка: по смене -->
      <template v-else>
        <div v-if="shiftSummary" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Чеков</div>
            <div class="text-2xl font-semibold mt-1">{{ shiftSummary.totalChecks ?? 0 }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Выручка</div>
            <div class="text-2xl font-semibold mt-1 text-primary">{{ formatMoney(shiftSummary.totalSum) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Средний чек</div>
            <div class="text-2xl font-semibold mt-1">{{ formatMoney(shiftSummary.avgCheck) }}</div>
          </div>
          <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="text-xs text-gray-500 uppercase">Период</div>
            <div class="text-sm font-medium mt-2 text-gray-700">{{ formatPeriodLabel }}</div>
          </div>
        </div>

        <div v-if="loadingShift && !shiftLoadedOnce" class="py-16 text-center text-gray-500 text-sm">
          Загрузка сводки по смене…
        </div>
        <div v-else-if="!loadingShift && shiftLoadedOnce && !hasShiftData" class="py-16 text-center text-gray-500 text-sm">
          За выбранный период данных не найдено.
        </div>

        <div v-else-if="hasShiftData" class="space-y-6">
          <!-- Выручка по часам -->
          <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Выручка по часам</h2>
            <div v-if="!byHour.length" class="text-sm text-gray-500">Нет данных по часам.</div>
            <div v-else class="space-y-2">
              <div
                v-for="row in byHour"
                :key="row.label"
                class="flex items-center gap-3 text-sm"
              >
                <span class="w-12 shrink-0 text-gray-600 tabular-nums">{{ row.label }}</span>
                <div class="flex-1 h-7 bg-gray-100 rounded-md overflow-hidden">
                  <div
                    class="h-full bg-primary/70 rounded-md transition-all min-w-[2px]"
                    :style="{ width: hourBarWidth(row) }"
                  />
                </div>
                <span class="w-28 text-right font-medium tabular-nums shrink-0">{{ formatMoney(row.sum) }}</span>
                <span v-if="row.checks" class="w-16 text-right text-xs text-gray-500 shrink-0">{{ row.checks }} ч.</span>
              </div>
            </div>
          </section>

          <div class="grid md:grid-cols-2 gap-6">
            <!-- Типы оплаты -->
            <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
              <h2 class="text-sm font-semibold text-gray-800 mb-4">Выручка по типам оплаты</h2>
              <div v-if="!byPaymentType.length" class="text-sm text-gray-500">Нет данных.</div>
              <table v-else class="w-full text-sm">
                <thead>
                  <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="pb-2 font-medium">Тип</th>
                    <th class="pb-2 font-medium text-right">Сумма</th>
                    <th class="pb-2 font-medium text-right">%</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="row in byPaymentType" :key="row.label">
                    <td class="py-2.5 text-gray-800">{{ row.label }}</td>
                    <td class="py-2.5 text-right font-medium tabular-nums">{{ formatMoney(row.sum) }}</td>
                    <td class="py-2.5 text-right text-gray-500 tabular-nums">{{ formatShare(row.share) }}</td>
                  </tr>
                </tbody>
              </table>
            </section>

            <!-- Типы заказа -->
            <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
              <h2 class="text-sm font-semibold text-gray-800 mb-4">Выручка по типам заказа</h2>
              <div v-if="!byOrderType.length" class="text-sm text-gray-500">Нет данных.</div>
              <table v-else class="w-full text-sm">
                <thead>
                  <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="pb-2 font-medium">Тип</th>
                    <th class="pb-2 font-medium text-right">Сумма</th>
                    <th class="pb-2 font-medium text-right">%</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="row in byOrderType" :key="row.label">
                    <td class="py-2.5 text-gray-800">{{ row.label }}</td>
                    <td class="py-2.5 text-right font-medium tabular-nums">{{ formatMoney(row.sum) }}</td>
                    <td class="py-2.5 text-right text-gray-500 tabular-nums">{{ formatShare(row.share) }}</td>
                  </tr>
                </tbody>
              </table>
            </section>
          </div>

          <!-- По дням -->
          <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Выручка по дням</h2>
            <div v-if="!byDay.length" class="text-sm text-gray-500">Нет данных.</div>
            <table v-else class="w-full text-sm max-w-xl">
              <thead>
                <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                  <th class="pb-2 font-medium">Дата</th>
                  <th class="pb-2 font-medium text-right">Сумма</th>
                  <th class="pb-2 font-medium text-right">Чеков</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="row in byDay" :key="row.label">
                  <td class="py-2.5 text-gray-800">{{ formatDayLabel(row.label) }}</td>
                  <td class="py-2.5 text-right font-medium tabular-nums">{{ formatMoney(row.sum) }}</td>
                  <td class="py-2.5 text-right text-gray-600 tabular-nums">{{ row.checks || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </section>
        </div>
      </template>
    </template>

    <!-- Детали чека -->
    <Teleport to="body">
      <div
        v-if="selectedCheck"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="closeDetail"
      >
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDetail" />
        <div
          class="relative w-full sm:max-w-lg max-h-[90vh] bg-white rounded-t-2xl sm:rounded-2xl shadow-xl flex flex-col overflow-hidden"
          role="dialog"
          aria-modal="true"
        >
          <div class="px-5 py-4 border-b border-gray-100 flex items-start gap-3">
            <div>
              <h2 class="text-lg font-bold text-gray-800">
                Чек {{ selectedCheck.orderNum || '—' }}
              </h2>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ formatCheckDate(selectedCheck.date) }}
                <span v-if="selectedCheck.departmentName"> · {{ selectedCheck.departmentName }}</span>
              </p>
            </div>
            <button
              type="button"
              class="ml-auto text-gray-400 hover:text-gray-600 text-2xl leading-none"
              aria-label="Закрыть"
              @click="closeDetail"
            >
              ×
            </button>
          </div>

          <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
            <span class="text-sm text-gray-600">Итого</span>
            <span class="text-xl font-bold text-primary tabular-nums">{{ formatMoney(selectedCheck.sum) }}</span>
          </div>

          <div class="flex-1 overflow-y-auto px-5 py-3">
            <div v-if="loadingDetail" class="py-10 text-center text-sm text-gray-500">Загрузка позиций…</div>
            <div v-else-if="detailError" class="py-6 text-sm text-rose-600">{{ detailError }}</div>
            <div v-else-if="!detailItems.length" class="py-6 text-sm text-gray-500 text-center">
              Состав чека не найден (проверьте права OLAP по продажам в iiko).
            </div>
            <ul v-else class="divide-y divide-gray-100">
              <li
                v-for="(item, idx) in detailItems"
                :key="idx"
                class="py-3 flex gap-3"
              >
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-gray-800 text-sm leading-snug">{{ item.name }}</div>
                  <div v-if="item.amount" class="text-xs text-gray-500 mt-0.5">
                    {{ formatNum(item.amount) }} × {{ formatMoney(item.unitPrice) }}
                  </div>
                </div>
                <div class="text-sm font-semibold text-gray-900 tabular-nums shrink-0">
                  {{ formatMoney(item.sum) }}
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
defineProps({
  embedded: { type: Boolean, default: false },
})

import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { api } from '@/api/client'
import { iikoSettingsComplete, getIikoCredentialsPayload } from '@/utils/iikoSettings'
import { pickIikoDepartmentForLocation } from '@/utils/iikoStoreMatch'
import { getCurrentUser } from '@/router'

const settingsReady = computed(() => iikoSettingsComplete())

const activeTab = ref('checks')
const periodPreset = ref(null)

function isoDateOnly(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function localDateOffset(days) {
  const d = new Date()
  d.setHours(12, 0, 0, 0)
  d.setDate(d.getDate() + days)
  return isoDateOnly(d)
}

function syncPeriodPreset() {
  const today = localDateOffset(0)
  const yesterday = localDateOffset(-1)
  if (periodFrom.value === today && periodTo.value === today) {
    periodPreset.value = 'today'
  } else if (periodFrom.value === yesterday && periodTo.value === yesterday) {
    periodPreset.value = 'yesterday'
  } else {
    periodPreset.value = null
  }
}

function setPeriodPreset(preset) {
  const day = preset === 'yesterday' ? localDateOffset(-1) : localDateOffset(0)
  periodFrom.value = day
  periodTo.value = day
  periodPreset.value = preset
  refresh()
}

function formatMoney(n) {
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 2,
  }).format(n)
}

function formatNum(n) {
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 3 }).format(n)
}

function formatShare(n) {
  if (!Number.isFinite(n)) return '—'
  return `${n.toFixed(1)}%`
}

function formatCheckDate(s) {
  if (!s) return '—'
  const d = new Date(s)
  if (!Number.isNaN(d.getTime())) {
    return d.toLocaleString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
  return String(s).slice(0, 16)
}

function formatDayLabel(s) {
  if (!s) return '—'
  const parts = String(s).slice(0, 10).split('-')
  if (parts.length === 3) return `${parts[2]}.${parts[1]}.${parts[0]}`
  return s
}

const departments = ref([])
const departmentId = ref('')
const periodFrom = ref(isoDateOnly(new Date(Date.now() - 7 * 86400000)))
const periodTo = ref(isoDateOnly(new Date()))

watch([periodFrom, periodTo], syncPeriodPreset)

const checks = ref([])
const totalSum = ref(0)
const loadingDepartments = ref(false)
const loadingChecks = ref(false)
const loadedOnceChecks = ref(false)
const loadingShift = ref(false)
const shiftLoadedOnce = ref(false)
const error = ref(null)
const warning = ref(null)

const shiftSummary = ref(null)
const byHour = ref([])
const byPaymentType = ref([])
const byOrderType = ref([])
const byDay = ref([])

const selectedCheck = ref(null)
const detailItems = ref([])
const loadingDetail = ref(false)
const detailError = ref(null)
const lastUpdatedAt = ref(null)
let pollTimer = null

const pollIntervalSec = computed(() => (periodPreset.value === 'today' ? 15 : 30))

const lastUpdatedLabel = computed(() => {
  if (!lastUpdatedAt.value) return ''
  return lastUpdatedAt.value.toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
})

const isLoading = computed(() =>
  activeTab.value === 'checks' ? loadingChecks.value : loadingShift.value
)

const refreshLabel = computed(() =>
  activeTab.value === 'checks' ? 'Показать чеки' : 'Обновить сводку'
)

const avgCheck = computed(() => {
  if (!checks.value.length) return 0
  return totalSum.value / checks.value.length
})

const formatPeriodLabel = computed(() => {
  const f = periodFrom.value
  const t = periodTo.value
  if (!f || !t) return '—'
  return `${f.split('-').reverse().join('.')} — ${t.split('-').reverse().join('.')}`
})

const maxHourSum = computed(() => {
  let m = 0
  for (const row of byHour.value) {
    if ((row.sum || 0) > m) m = row.sum
  }
  return m
})

const hasShiftData = computed(() =>
  (shiftSummary.value?.totalSum ?? 0) > 0
  || byHour.value.length > 0
  || byPaymentType.value.length > 0
  || byDay.value.length > 0
)

function hourBarWidth(row) {
  const max = maxHourSum.value
  if (!max || !row.sum) return '0%'
  return `${Math.round((row.sum / max) * 100)}%`
}

function checkKey(check) {
  return `${check.orderId}::${check.orderNum || ''}::${check.date || ''}`
}

async function loadDepartments() {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  loadingDepartments.value = true
  try {
    const data = await api.iikoChecks.departments({ iiko: creds })
    if (!data.ok) {
      error.value = data.message || 'Ошибка загрузки точек'
      return
    }
    departments.value = data.departments || []
    if (!departmentId.value) {
      const user = await getCurrentUser().catch(() => null)
      if (user?.locationId && user?.locationName) {
        const preferred = pickIikoDepartmentForLocation(
          departments.value,
          user.locationName,
          user.locationIikoName,
        )
        if (preferred && departments.value.some((d) => d.id === preferred)) {
          departmentId.value = preferred
        }
      }
    }
  } catch (e) {
    error.value = e.data?.message || e.message
  } finally {
    loadingDepartments.value = false
  }
}

async function loadChecks({ silent = false } = {}) {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  if (!silent) {
    loadingChecks.value = true
    error.value = null
    warning.value = null
    closeDetail()
  }
  try {
    const data = await api.iikoChecks.list({
      iiko: creds,
      from: periodFrom.value,
      to: periodTo.value,
      departmentId: departmentId.value || undefined,
    })
    loadedOnceChecks.value = true
    if (!data.ok) {
      error.value = data.message || 'Ошибка'
      checks.value = []
      totalSum.value = 0
      return
    }
    checks.value = data.checks || []
    totalSum.value = Number(data.totalSum) || 0
    if (!silent) warning.value = data.warning || null
    lastUpdatedAt.value = new Date()
  } catch (e) {
    if (!silent) {
      error.value = e.data?.message || e.message
      checks.value = []
    }
  } finally {
    if (!silent) loadingChecks.value = false
  }
}

async function loadShiftSummary({ silent = false } = {}) {
  const creds = getIikoCredentialsPayload()
  if (!creds) return
  if (!silent) {
    loadingShift.value = true
    error.value = null
    warning.value = null
  }
  try {
    const data = await api.iikoChecks.shiftSummary({
      iiko: creds,
      from: periodFrom.value,
      to: periodTo.value,
      departmentId: departmentId.value || undefined,
    })
    shiftLoadedOnce.value = true
    if (!data.ok) {
      error.value = data.message || 'Ошибка'
      shiftSummary.value = null
      byHour.value = []
      byPaymentType.value = []
      byOrderType.value = []
      byDay.value = []
      return
    }
    const s = data.summary || {}
    shiftSummary.value = {
      totalSum: s.totalSum ?? 0,
      totalChecks: s.totalChecks ?? 0,
      avgCheck: s.avgCheck ?? 0,
    }
    byHour.value = data.byHour || []
    byPaymentType.value = data.byPaymentType || []
    byOrderType.value = data.byOrderType || []
    byDay.value = data.byDay || []
    if (!silent) warning.value = data.warning || null
    lastUpdatedAt.value = new Date()
  } catch (e) {
    if (!silent) {
      error.value = e.data?.message || e.message
      shiftSummary.value = null
    }
  } finally {
    if (!silent) loadingShift.value = false
  }
}

function refresh({ silent = false } = {}) {
  if (activeTab.value === 'checks') {
    loadChecks({ silent })
  } else {
    loadShiftSummary({ silent })
  }
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

function startPolling() {
  stopPolling()
  if (!settingsReady.value) return
  pollTimer = setInterval(() => {
    if (isLoading.value || selectedCheck.value) return
    refresh({ silent: true })
  }, pollIntervalSec.value * 1000)
}

async function openCheck(check) {
  selectedCheck.value = check
  detailItems.value = []
  detailError.value = null
  const creds = getIikoCredentialsPayload()
  if (!creds || !check.orderId) return
  loadingDetail.value = true
  try {
    const data = await api.iikoChecks.detail({
      iiko: creds,
      orderId: check.orderId,
      from: periodFrom.value,
      to: periodTo.value,
      departmentId: departmentId.value || check.departmentId || undefined,
    })
    if (!data.ok) {
      detailError.value = data.message || 'Ошибка'
      return
    }
    detailItems.value = data.items || []
  } catch (e) {
    detailError.value = e.data?.message || e.message
  } finally {
    loadingDetail.value = false
  }
}

function closeDetail() {
  selectedCheck.value = null
  detailItems.value = []
  detailError.value = null
}

watch(activeTab, (tab) => {
  if (!settingsReady.value) return
  if (tab === 'shift' && !shiftLoadedOnce.value) {
    loadShiftSummary()
  }
  startPolling()
})

watch(pollIntervalSec, () => {
  startPolling()
})

onMounted(() => {
  syncPeriodPreset()
  if (settingsReady.value) {
    loadDepartments().then(() => {
      if (activeTab.value === 'checks') {
        loadChecks()
      } else {
        loadShiftSummary()
      }
      startPolling()
    })
  }
})

onUnmounted(stopPolling)
</script>
