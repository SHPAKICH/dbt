<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-6">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link to="/learning/cards" class="text-gray-500 hover:text-gray-700">Каталог карточек</router-link>
      <router-link v-if="cardId" :to="{ name: 'GuruCardView', params: { id: cardId } }" class="text-gray-500 hover:text-gray-700">К карточке</router-link>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4">✏️ Редактировать карточку (ТТК)</h1>
    <p v-if="loading" class="py-8 text-gray-500">Загрузка...</p>
    <template v-else>
      <p v-if="error" class="mb-4 p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>

      <form v-if="!loading" @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
            <input v-model.trim="form.title" required type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
            <input v-model.trim="form.category" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Время приготовления (мин)</label>
            <input v-model.number="form.prepTime" min="0" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Подача</label>
            <input v-model.trim="form.serving" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Описание / технология</label>
            <textarea v-model="form.description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Изображение</label>
            <input type="file" accept="image/*" @change="onImageChange" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white" />
            <p v-if="form.imageUrl" class="mt-1 text-sm text-gray-500">Текущее: <a :href="form.imageUrl" target="_blank" rel="noopener" class="text-primary underline">просмотр</a></p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <input id="cardActiveEdit" v-model="form.isActive" type="checkbox" class="rounded border-gray-300" />
          <label for="cardActiveEdit" class="text-sm text-gray-700">Активна</label>
        </div>

        <div class="pt-2 border-t border-gray-200">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-800">Ингредиенты</h2>
            <button type="button" @click="addIngredient" class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">
              + Добавить строку
            </button>
          </div>
          <div class="space-y-3">
            <div v-for="(ing, idx) in form.ingredients" :key="idx" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start p-3 rounded-lg border border-gray-200">
              <div class="md:col-span-4">
                <label class="block text-xs text-gray-500 mb-1">Наименование *</label>
                <input v-model.trim="ing.ingredientName" type="text" class="w-full px-2 py-1.5 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Размер</label>
                <select v-model="ing.sizeCode" class="w-full px-2 py-1.5 border border-gray-300 rounded-md">
                  <option value="">Общий</option>
                  <option value="S">S</option>
                  <option value="M">M</option>
                  <option value="L">L</option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Количество</label>
                <input v-model="ing.quantity" type="text" class="w-full px-2 py-1.5 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Ед. изм.</label>
                <select v-model="ing.unit" class="w-full px-2 py-1.5 border border-gray-300 rounded-md">
                  <option value="г">г</option>
                  <option value="мл">мл</option>
                  <option value="шт">шт</option>
                  <option value="порц.">порц.</option>
                </select>
              </div>
              <div class="md:col-span-1">
                <label class="block text-xs text-gray-500 mb-1">Цена</label>
                <input v-model="ing.pricePerUnit" type="text" class="w-full px-2 py-1.5 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-1 flex md:justify-end">
                <button type="button" @click="removeIngredient(idx)" class="mt-5 px-2 py-1.5 rounded-md border border-red-300 text-red-700 hover:bg-red-50">×</button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-2 pt-2">
          <button :disabled="saving" type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50">
            {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
          </button>
          <router-link v-if="cardId" :to="{ name: 'GuruCardView', params: { id: cardId } }" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Отмена</router-link>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api/client'

const route = useRoute()
const router = useRouter()
const cardId = ref(route.params.id)
const loading = ref(true)
const saving = ref(false)
const error = ref('')

const form = reactive({
  title: '',
  category: '',
  prepTime: '',
  serving: '',
  description: '',
  isActive: true,
  imageFile: null,
  imageUrl: null,
  ingredients: [],
})

const sizeOrder = ['_base', 'S', 'M', 'L']

function flattenIngredients(ingredientsBySize) {
  if (!ingredientsBySize || typeof ingredientsBySize !== 'object') return []
  const list = []
  for (const sizeKey of sizeOrder) {
    const arr = ingredientsBySize[sizeKey]
    if (!Array.isArray(arr)) continue
    for (const ing of arr) {
      list.push({
        ingredientName: ing.ingredientName ?? '',
        sizeCode: sizeKey === '_base' ? '' : sizeKey,
        quantity: ing.quantity != null ? String(ing.quantity) : '',
        unit: ing.unit ?? 'г',
        pricePerUnit: ing.pricePerUnit != null ? String(ing.pricePerUnit) : '',
      })
    }
  }
  const rest = Object.keys(ingredientsBySize).filter((k) => !sizeOrder.includes(k))
  for (const sizeKey of rest) {
    const arr = ingredientsBySize[sizeKey]
    if (!Array.isArray(arr)) continue
    for (const ing of arr) {
      list.push({
        ingredientName: ing.ingredientName ?? '',
        sizeCode: sizeKey === '_base' ? '' : sizeKey,
        quantity: ing.quantity != null ? String(ing.quantity) : '',
        unit: ing.unit ?? 'г',
        pricePerUnit: ing.pricePerUnit != null ? String(ing.pricePerUnit) : '',
      })
    }
  }
  return list.length ? list : [{ ingredientName: '', sizeCode: '', quantity: '', unit: 'г', pricePerUnit: '' }]
}

function onImageChange(e) {
  form.imageFile = e.target.files?.[0] || null
}

function addIngredient() {
  form.ingredients.push({
    ingredientName: '',
    sizeCode: '',
    quantity: '',
    unit: 'г',
    pricePerUnit: '',
  })
}

function removeIngredient(index) {
  form.ingredients.splice(index, 1)
  if (form.ingredients.length === 0) addIngredient()
}

async function load() {
  const id = cardId.value
  if (!id) return
  loading.value = true
  error.value = ''
  try {
    const res = await api.guru.card(id)
    form.title = res.title ?? ''
    form.category = res.category ?? ''
    form.prepTime = res.prepTime ?? ''
    form.serving = res.serving ?? ''
    form.description = res.description ?? ''
    form.isActive = res.isActive !== false
    form.imageUrl = res.image || null
    form.ingredients = flattenIngredients(res.ingredientsBySize)
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки карточки'
  } finally {
    loading.value = false
  }
}

async function submit() {
  const id = cardId.value
  if (!id) return
  error.value = ''
  const nonEmptyIngredients = form.ingredients.filter((i) => (i.ingredientName || '').trim() !== '')
  if (nonEmptyIngredients.length === 0) {
    error.value = 'Добавьте хотя бы один ингредиент.'
    return
  }

  saving.value = true
  try {
    const fd = new FormData()
    fd.append('title', form.title.trim())
    fd.append('category', form.category.trim())
    fd.append('prepTime', form.prepTime === '' ? '' : String(form.prepTime))
    fd.append('serving', form.serving.trim())
    fd.append('description', form.description)
    fd.append('isActive', form.isActive ? '1' : '0')
    if (form.imageFile) fd.append('imageFile', form.imageFile)
    fd.append('ingredients', JSON.stringify(nonEmptyIngredients))

    await api.guru.updateCard(id, fd)
    router.push({ name: 'GuruCardView', params: { id } })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения карточки'
  } finally {
    saving.value = false
  }
}

onMounted(load)
watch(() => route.params.id, (newId) => {
  cardId.value = newId
  if (newId) load()
})
</script>
