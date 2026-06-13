<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[85vh] flex flex-col">
      <div class="px-5 py-4 border-b border-gray-100 flex items-start gap-3">
        <div class="flex-1 min-w-0">
          <h3 class="text-lg font-semibold text-gray-800">Состав документа</h3>
          <p v-if="document" class="text-sm text-gray-500 mt-0.5">
            {{ documentTypeLabel(document.documentType) }}
            № {{ document.number || '—' }}
            · {{ formatDate(document.date) }}
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 text-xl leading-none"
          aria-label="Закрыть"
          @click="$emit('close')"
        >
          ×
        </button>
      </div>

      <div class="px-5 py-3 overflow-y-auto flex-1">
        <p v-if="loading" class="text-sm text-gray-500 py-8 text-center">Загрузка состава…</p>
        <p v-else-if="error" class="text-sm text-rose-700 py-4">{{ error }}</p>
        <p v-else-if="!items.length" class="text-sm text-gray-500 py-8 text-center">Позиции не найдены</p>
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
              <th class="py-2 pr-3 font-medium">Товар</th>
              <th class="py-2 pr-3 font-medium text-right w-24">Кол-во</th>
              <th class="py-2 pr-3 font-medium text-right w-28">Цена</th>
              <th class="py-2 font-medium text-right w-28">Сумма</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in items"
              :key="`${row.productId}-${idx}`"
              class="border-b border-gray-50"
            >
              <td class="py-2.5 pr-3 text-gray-800">
                {{ row.productName || row.productId }}
              </td>
              <td class="py-2.5 pr-3 text-right tabular-nums">{{ formatNum(row.amount) }}</td>
              <td class="py-2.5 pr-3 text-right tabular-nums">
                {{ row.price != null ? formatMoney(row.price) : '—' }}
              </td>
              <td class="py-2.5 text-right tabular-nums">
                {{ row.sum != null ? formatMoney(row.sum) : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="document && !loading" class="px-5 py-3 border-t border-gray-100 text-sm text-gray-600 flex justify-between">
        <span v-if="document.comment" class="truncate max-w-[60%]" :title="document.comment">
          {{ document.comment }}
        </span>
        <span class="font-medium text-gray-800 ml-auto">
          Итого: {{ formatMoney(document.sum) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { fetchDocumentById } from '@/services/iikoCloudDocuments'
import { documentTypeLabel } from '@/types/iikoDocument'

const props = defineProps({
  open: { type: Boolean, default: false },
  documentId: { type: String, default: '' },
  documentType: { type: String, default: '' },
  summary: { type: Object, default: null },
})

defineEmits(['close'])

const loading = ref(false)
const error = ref('')
const document = ref(null)
const items = ref([])

watch(
  () => [props.open, props.documentId, props.documentType],
  async ([isOpen, id, type]) => {
    if (!isOpen || !id || !type) {
      return
    }
    loading.value = true
    error.value = ''
    document.value = props.summary
    items.value = props.summary?.items?.length ? [...props.summary.items] : []
    try {
      const res = await fetchDocumentById(id, type)
      if (res.document) {
        document.value = res.document
        items.value = res.document.items || []
      }
    } catch (e) {
      error.value = e.data?.message || e.message || 'Ошибка загрузки'
      if (!items.value.length && props.summary) {
        error.value += ' (показана только строка из списка)'
      }
    } finally {
      loading.value = false
    }
  },
)

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('ru-RU')
  } catch {
    return iso
  }
}

function formatNum(n) {
  const v = Number(n)
  if (Number.isNaN(v)) return '—'
  return v.toLocaleString('ru-RU', { maximumFractionDigits: 3 })
}

function formatMoney(n) {
  const v = Number(n)
  if (Number.isNaN(v)) return '—'
  return v.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
