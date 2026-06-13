<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-4">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link to="/learning/cards" class="text-gray-500 hover:text-gray-700">Каталог карточек</router-link>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else-if="card" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <img v-if="card.image" :src="card.image" alt="" class="w-full object-cover max-h-72" />
      <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ card.title }}</h1>
        <span v-if="card.category" class="inline-block mt-2 px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-sm">{{ card.category }}</span>
        <p v-if="card.prepTime" class="mt-2 text-gray-600"><strong>Время приготовления:</strong> {{ card.prepTime }} мин</p>
        <p v-if="card.serving" class="text-gray-600"><strong>Подача:</strong> {{ card.serving }}</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-800">Ингредиенты</h2>
        <div class="overflow-x-auto my-2">
          <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 font-medium text-gray-600">Наименование</th>
                <th v-for="s in ingredientTable.sizes" :key="'h-'+s" class="px-3 py-2 font-medium text-gray-600 text-center">
                  {{ sizeLabel(s) }}
                </th>
                <th class="px-3 py-2 font-medium text-gray-600">Ед. измерения</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in ingredientTable.rows" :key="row.name" class="border-b border-gray-100">
                <td class="px-3 py-2 text-gray-800">{{ row.name }}</td>
                <td v-for="s in ingredientTable.sizes" :key="'q-'+row.name+s" class="px-3 py-2 text-gray-800 text-center">
                  {{ row.quantities[s] != null ? formatNum(row.quantities[s]) : '—' }}
                </td>
                <td class="px-3 py-2 text-gray-800">{{ row.unit }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="card.description" class="mt-6">
          <h2 class="text-lg font-semibold text-gray-800">Описание / технология</h2>
          <div class="mt-2 text-gray-600 prose max-w-none" v-html="sanitizedDescription"></div>
        </div>
      </div>
      <div class="px-6 pb-6 flex flex-wrap items-center gap-2">
        <router-link to="/learning/cards" class="inline-flex px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
          ← К каталогу
        </router-link>
        <template v-if="card.isAdmin">
          <router-link
            :to="{ name: 'GuruCardEdit', params: { id: card.id } }"
            class="inline-flex px-4 py-2 rounded-lg border border-amber-300 text-amber-700 font-medium hover:bg-amber-50"
          >
            Редактировать
          </router-link>
          <button
            type="button"
            class="inline-flex px-4 py-2 rounded-lg border border-red-300 text-red-700 font-medium hover:bg-red-50"
            @click="confirmDelete"
          >
            Удалить
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { sanitizeHtml } from '@/utils/security'

const route = useRoute()
const router = useRouter()
const card = ref(null)
const loading = ref(true)
const error = ref('')
const sanitizedDescription = computed(() => sanitizeHtml(card.value?.description))

const sizeLabels = {
  _base: 'Базовая рецептура',
  S: 'Размер S',
  M: 'Размер M',
  L: 'Размер L',
}

function sizeLabel(code) {
  return sizeLabels[code] || ('Размер ' + code)
}

const ingredientTable = computed(() => {
  if (!card.value?.ingredientsBySize) return { sizes: [], rows: [] }
  const bySize = card.value.ingredientsBySize
  const sizeOrder = ['S', 'M', 'L']
  const sizes = Object.keys(bySize).filter(k => k !== '_base').sort((a, b) => {
    const ai = sizeOrder.indexOf(a), bi = sizeOrder.indexOf(b)
    return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi)
  })

  const ingredientMap = new Map()

  const ensureRow = (name, unit) => {
    if (!ingredientMap.has(name)) {
      ingredientMap.set(name, { unit, quantities: {} })
    }
  }

  if (bySize._base) {
    for (const ing of bySize._base) {
      ensureRow(ing.ingredientName, ing.unit)
      const entry = ingredientMap.get(ing.ingredientName)
      for (const s of sizes) {
        entry.quantities[s] = ing.quantity
      }
    }
  }

  for (const size of sizes) {
    if (!bySize[size]) continue
    for (const ing of bySize[size]) {
      ensureRow(ing.ingredientName, ing.unit)
      ingredientMap.get(ing.ingredientName).quantities[size] = ing.quantity
    }
  }

  const rows = Array.from(ingredientMap.entries()).map(([name, data]) => ({ name, ...data }))
  return { sizes, rows }
})

function formatNum(n) {
  if (n == null) return '—'
  return Number(n).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function confirmDelete() {
  if (!card.value?.isAdmin || !confirm(`Удалить карточку «${card.value.title}»?`)) return
  try {
    const { api } = await import('@/api/client')
    await api.guru.deleteCard(card.value.id)
    router.push({ name: 'GuruCardsList' })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка удаления'
  }
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.card(route.params.id)
    card.value = res
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})
</script>
