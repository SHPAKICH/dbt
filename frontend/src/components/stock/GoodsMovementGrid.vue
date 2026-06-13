<template>
  <div>
    <div class="flex flex-wrap items-end gap-3 mb-4">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">С</label>
        <input v-model="fromDate" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">По</label>
        <input v-model="toDate" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="min-w-[160px]">
        <label class="block text-xs font-medium text-gray-600 mb-1">Тип документа</label>
        <select v-model="filterType" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option v-for="opt in DOCUMENT_FILTER_OPTIONS" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
      <div class="min-w-[140px]">
        <label class="block text-xs font-medium text-gray-600 mb-1">Статус</label>
        <select v-model="filterStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option v-for="opt in STATUS_FILTER_OPTIONS" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
      <div class="min-w-[200px]">
        <label class="block text-xs font-medium text-gray-600 mb-1">Склад</label>
        <select v-model="filterStoreId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="">Все склады</option>
          <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <button
        type="button"
        class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
        :disabled="loading"
        @click="reload"
      >
        {{ loading ? 'Загрузка…' : 'Применить' }}
      </button>
    </div>

    <p v-if="error" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 mb-4">
      {{ error }}
    </p>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
          <thead>
            <tr class="bg-gray-50 text-left text-xs text-gray-600 border-b border-gray-100">
              <th class="px-4 py-3 font-medium">Дата</th>
              <th class="px-4 py-3 font-medium">Номер</th>
              <th class="px-4 py-3 font-medium">Тип</th>
              <th class="px-4 py-3 font-medium">Склады</th>
              <th class="px-4 py-3 font-medium text-right">Сумма</th>
              <th class="px-4 py-3 font-medium">Статус</th>
              <th class="px-4 py-3 font-medium w-36"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading && !rows.length">
              <td colspan="7" class="px-4 py-10 text-center text-gray-500">Загрузка документов…</td>
            </tr>
            <tr v-else-if="!rows.length">
              <td colspan="7" class="px-4 py-10 text-center text-gray-500">Документы не найдены</td>
            </tr>
            <tr
              v-for="doc in rows"
              :key="`${doc.documentType}-${doc.id}`"
              class="border-b border-gray-50 hover:bg-gray-50/80"
            >
              <td class="px-4 py-2.5 whitespace-nowrap">{{ formatDate(doc.date) }}</td>
              <td class="px-4 py-2.5 font-medium text-gray-800">{{ doc.number || '—' }}</td>
              <td class="px-4 py-2.5">{{ documentTypeLabel(doc.documentType) }}</td>
              <td class="px-4 py-2.5 text-gray-600 text-xs max-w-[220px]">
                {{ storeLine(doc) }}
              </td>
              <td class="px-4 py-2.5 text-right tabular-nums">{{ formatMoney(doc.sum) }}</td>
              <td class="px-4 py-2.5">
                <span
                  class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="statusClass(doc.status)"
                >
                  {{ statusLabel(doc.status) }}
                </span>
              </td>
              <td class="px-4 py-2.5">
                <button
                  type="button"
                  class="text-primary text-sm font-medium hover:underline"
                  @click="openItems(doc)"
                >
                  Посмотреть состав
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="hasMore" class="px-4 py-3 border-t border-gray-100 flex justify-center">
        <button
          type="button"
          class="text-sm text-primary font-medium hover:underline disabled:opacity-50"
          :disabled="loadingMore"
          @click="loadMore"
        >
          {{ loadingMore ? 'Загрузка…' : 'Загрузить ещё' }}
        </button>
      </div>
    </div>

    <DocumentItemsModal
      :open="modalOpen"
      :document-id="selectedDoc?.id || ''"
      :document-type="selectedDoc?.documentType || ''"
      :summary="selectedDoc"
      @close="modalOpen = false"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import DocumentItemsModal from '@/components/stock/DocumentItemsModal.vue'
import { fetchCloudStores, fetchDocumentsList } from '@/services/iikoCloudDocuments'
import {
  DOCUMENT_FILTER_OPTIONS,
  STATUS_FILTER_OPTIONS,
  documentTypeLabel,
  statusLabel,
} from '@/types/iikoDocument'

const fromDate = ref('')
const toDate = ref('')
const filterType = ref('')
const filterStatus = ref('')
const filterStoreId = ref('')
const rows = ref([])
const stores = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const error = ref('')
const page = ref(0)
const hasMore = ref(false)
const modalOpen = ref(false)
const selectedDoc = ref(null)

function initDates() {
  const to = new Date()
  const from = new Date(to.getTime() - 30 * 86400000)
  toDate.value = to.toISOString().slice(0, 10)
  fromDate.value = from.toISOString().slice(0, 10)
}

function listParams(p = 0) {
  /** @type {import('@/types/iikoDocument').IikoDocumentsListParams} */
  const params = {
    fromDate: fromDate.value,
    toDate: toDate.value,
    page: p,
    pageSize: 50,
  }
  if (filterType.value) params.documentTypes = [filterType.value]
  if (filterStatus.value) params.statuses = [filterStatus.value]
  if (filterStoreId.value) params.storeId = filterStoreId.value
  return params
}

async function loadStores() {
  try {
    const res = await fetchCloudStores()
    stores.value = res.stores || []
  } catch {
    stores.value = []
  }
}

async function loadPage(p, append) {
  const busy = append ? loadingMore : loading
  busy.value = true
  error.value = ''
  try {
    const res = await fetchDocumentsList(listParams(p))
    const docs = res.documents || []
    rows.value = append ? [...rows.value, ...docs] : docs
    hasMore.value = Boolean(res.hasMore)
    page.value = p
    mergeStoresFromDocs(docs)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
    if (!append) rows.value = []
  } finally {
    busy.value = false
  }
}

function mergeStoresFromDocs(docs) {
  const map = new Map(stores.value.map((s) => [s.id, s]))
  for (const d of docs) {
    for (const [id, name] of [
      [d.storeFromId, d.storeFromName],
      [d.storeToId, d.storeToName],
      [d.storeId, null],
    ]) {
      if (id && !map.has(id)) {
        map.set(id, { id, name: name || id })
      }
    }
  }
  stores.value = [...map.values()].sort((a, b) => a.name.localeCompare(b.name, 'ru'))
}

function reload() {
  loadPage(0, false)
}

function loadMore() {
  if (!hasMore.value || loadingMore.value) return
  loadPage(page.value + 1, true)
}

function openItems(doc) {
  selectedDoc.value = doc
  modalOpen.value = true
}

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('ru-RU')
  } catch {
    return iso
  }
}

function formatMoney(n) {
  const v = Number(n)
  if (Number.isNaN(v)) return '—'
  return v.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function storeLine(doc) {
  const from = doc.storeFromName || doc.storeFromId
  const to = doc.storeToName || doc.storeToId
  if (from && to) return `${from} → ${to}`
  if (from) return from
  if (to) return to
  return doc.storeId || '—'
}

function statusClass(status) {
  if (status === 'posted') return 'bg-emerald-50 text-emerald-800'
  if (status === 'draft') return 'bg-amber-50 text-amber-800'
  if (status === 'deleted') return 'bg-gray-100 text-gray-500'
  return 'bg-gray-50 text-gray-600'
}

onMounted(() => {
  initDates()
  loadStores()
  reload()
})
</script>
