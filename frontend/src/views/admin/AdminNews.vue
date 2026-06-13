<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <router-link to="/admin" class="text-gray-500 hover:text-gray-700">← Админ-панель</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Новости</h1>
    </div>

    <p v-if="error" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">{{ error }}</p>
    <p v-if="success" class="p-4 rounded-lg bg-green-50 text-green-700 mb-4">{{ success }}</p>

    <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок *</label>
        <input
          v-model="form.title"
          type="text"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Текст новости *</label>
        <!-- Тулбар редактора -->
        <div
          v-if="editor"
          class="flex flex-wrap gap-1 p-2 border border-gray-300 border-b-0 rounded-t-lg bg-gray-50"
        >
          <button
            type="button"
            class="px-2 py-1.5 rounded text-sm font-medium"
            :class="editor.isActive('bold') ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'"
            @click="editor.chain().focus().toggleBold().run()"
          >
            Ж
          </button>
          <button
            type="button"
            class="px-2 py-1.5 rounded text-sm font-medium"
            :class="editor.isActive('italic') ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'"
            @click="editor.chain().focus().toggleItalic().run()"
          >
            К
          </button>
          <span class="w-px bg-gray-300 self-stretch my-1" />
          <button
            type="button"
            class="px-2 py-1.5 rounded text-sm font-medium text-gray-600 hover:bg-gray-200"
            title="Вставить изображение"
            @click="triggerImageUpload"
          >
            🖼 Изображение
          </button>
          <input
            ref="imageInputRef"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="hidden"
            @change="onImageSelected"
          />
        </div>
        <div
          class="min-h-[220px] border border-gray-300 rounded-b-lg px-3 py-2 focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary prose prose-sm max-w-none"
          :class="editor ? '' : 'rounded-t-lg'"
        >
          <editor-content :editor="editor" class="news-editor" />
        </div>
        <p class="mt-1 text-xs text-gray-500">Абзацы, жирный/курсив, изображения между блоками текста.</p>
      </div>
      <div class="flex items-center gap-2">
        <input id="isPublished" v-model="form.isPublished" type="checkbox" class="rounded border-gray-300" />
        <label for="isPublished" class="text-sm text-gray-700">Сразу опубликовать</label>
      </div>
      <div class="pt-2 flex items-center gap-3">
        <button
          type="submit"
          :disabled="saving"
          class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50"
        >
          {{ saving ? 'Сохранение...' : editingId ? 'Сохранение изменений…' : 'Добавить новость' }}
        </button>
        <button
          v-if="editingId"
          type="button"
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
          @click="resetForm"
        >
          Отмена редактирования
        </button>
      </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">Опубликованные новости</h2>
        <span class="text-sm text-gray-500">{{ items.length }} шт.</span>
      </div>
      <div v-if="loading" class="px-6 py-4 text-gray-500">Загрузка...</div>
      <div v-else>
        <p v-if="items.length === 0" class="px-6 py-4 text-gray-500">Пока нет новостей.</p>
        <ul v-else class="divide-y divide-gray-100">
          <li v-for="n in items" :key="n.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <h3 class="font-medium text-gray-900 truncate">
                    {{ n.title }}
                  </h3>
                  <span
                    class="px-2 py-0.5 rounded-full text-xs"
                    :class="n.isPublished ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                  >
                    {{ n.isPublished ? 'Опубликовано' : 'Черновик' }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-gray-600 line-clamp-3">
                  {{ previewText(n.body) }}
                </p>
              </div>
              <div class="flex flex-col items-end gap-2 text-xs text-gray-400 whitespace-nowrap">
                <div v-if="n.publishedAt">Опубликовано: {{ formatDate(n.publishedAt) }}</div>
                <div>Создано: {{ formatDate(n.createdAt) }}</div>
                <div class="flex gap-2 mt-1">
                  <button
                    type="button"
                    class="px-2 py-1 rounded-md border border-gray-300 text-gray-700 text-xs hover:bg-gray-50"
                    @click="editNews(n)"
                  >
                    Редактировать
                  </button>
                  <button
                    type="button"
                    class="px-2 py-1 rounded-md border border-red-300 text-red-600 text-xs hover:bg-red-50"
                    @click="deleteNews(n)"
                  >
                    Удалить
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import { api } from '@/api/client'

const items = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const imageInputRef = ref(null)
const editingId = ref(null)

const form = reactive({
  title: '',
  body: '',
  isPublished: true,
})

const editor = useEditor({
  content: '',
  extensions: [
    StarterKit.configure({
      paragraph: { HTMLAttributes: { class: 'mb-4' } },
    }),
    Image.configure({
      HTMLAttributes: { class: 'max-w-full h-auto rounded-lg my-4' },
    }),
    Placeholder.configure({
      placeholder: 'Напишите текст новости… Можно добавлять абзацы и вставлять изображения между ними.',
    }),
  ],
  editorProps: {
    attributes: {
      class: 'outline-none min-h-[180px]',
    },
  },
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function previewText(html) {
  if (!html) return ''
  const div = typeof document !== 'undefined' ? document.createElement('div') : null
  if (!div) return html
  div.innerHTML = html
  return (div.textContent || div.innerText || '').trim().slice(0, 200) + (html.length > 200 ? '…' : '')
}

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

function triggerImageUpload() {
  imageInputRef.value?.click()
}

async function onImageSelected(ev) {
  const file = ev.target.files?.[0]
  ev.target.value = ''
  if (!file || !editor.value) return
  try {
    const res = await api.admin.uploadNewsImage(file)
    const url = res.url
    if (url) editor.value.chain().focus().setImage({ src: url }).run()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки изображения'
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.admin.news()
    items.value = res.items || []
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки новостей'
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!editor.value) return
  const html = editor.value.getHTML()
  const textOnly = html.replace(/<[^>]+>/g, '').trim()
  if (!form.title.trim() || !textOnly) {
    error.value = 'Заполните заголовок и текст новости.'
    return
  }
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    const payload = {
      title: form.title.trim(),
      body: html,
      isPublished: form.isPublished,
    }
    if (editingId.value) {
      await api.admin.updateNews(editingId.value, payload)
      success.value = 'Новость обновлена.'
    } else {
      await api.admin.createNews(payload)
      success.value = 'Новость создана.'
    }
    resetForm()
    await load()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения новости'
  } finally {
    saving.value = false
  }
}

function resetForm() {
  form.title = ''
  form.body = ''
  form.isPublished = true
  editingId.value = null
  editor.value?.commands.setContent('')
}

function editNews(n) {
  error.value = ''
  success.value = ''
  editingId.value = n.id
  form.title = n.title
  form.isPublished = !!n.isPublished
  form.body = n.body || ''
  editor.value?.commands.setContent(n.body || '')
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

async function deleteNews(n) {
  if (!confirm(`Удалить новость «${n.title}»?`)) return
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    await api.admin.deleteNews(n.id)
    if (editingId.value === n.id) {
      resetForm()
    }
    await load()
    success.value = 'Новость удалена.'
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка удаления новости'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style>
.news-editor .tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  pointer-events: none;
  height: 0;
}
.news-editor .tiptap img {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  margin: 1rem 0;
}
</style>
