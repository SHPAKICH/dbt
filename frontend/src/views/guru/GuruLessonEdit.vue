<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <router-link :to="{ name: 'GuruLessonView', params: { id } }" class="text-gray-500 hover:text-gray-700">← К уроку</router-link>
      <h1 class="text-2xl font-bold text-gray-800">Редактировать урок</h1>
    </div>

    <p v-if="error" class="p-4 rounded-lg bg-red-50 text-red-700 mb-4">{{ error }}</p>
    <p v-if="success" class="p-4 rounded-lg bg-green-50 text-green-700 mb-4">{{ success }}</p>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>

    <form v-else @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
        <input
          v-model="form.category"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Содержание урока *</label>
        <div
          v-if="editor"
          class="flex flex-wrap gap-1 p-2 border border-gray-300 border-b-0 rounded-t-lg bg-gray-50"
        >
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium" :class="editor.isActive('bold') ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'" @click="editor.chain().focus().toggleBold().run()" title="Жирный">Ж</button>
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium" :class="editor.isActive('italic') ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'" @click="editor.chain().focus().toggleItalic().run()" title="Курсив">К</button>
          <span class="w-px bg-gray-300 self-stretch my-1" />
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium" :class="editor.isActive('heading', { level: 2 }) ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" title="Заголовок 2">H2</button>
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium" :class="editor.isActive('heading', { level: 3 }) ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" title="Заголовок 3">H3</button>
          <span class="w-px bg-gray-300 self-stretch my-1" />
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium" :class="editor.isActive('bulletList') ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'" @click="editor.chain().focus().toggleBulletList().run()" title="Маркированный список">• Список</button>
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium" :class="editor.isActive('orderedList') ? 'bg-primary/20 text-primary' : 'text-gray-600 hover:bg-gray-200'" @click="editor.chain().focus().toggleOrderedList().run()" title="Нумерованный список">1. Список</button>
          <span class="w-px bg-gray-300 self-stretch my-1" />
          <select class="px-2 py-1.5 rounded text-sm border border-gray-300 bg-white text-gray-700" title="Размер шрифта" @change="applyFontSize($event)">
            <option value="">Размер</option>
            <option value="0.875rem">Меньше</option>
            <option value="1rem">Обычный</option>
            <option value="1.125rem">Больше</option>
            <option value="1.25rem">Крупный</option>
          </select>
          <button type="button" class="px-2 py-1.5 rounded text-sm font-medium text-gray-600 hover:bg-gray-200" title="Вставить изображение" @click="triggerImageUpload">🖼 Изображение</button>
          <input ref="imageInputRef" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onImageSelected" />
        </div>
        <div class="min-h-[280px] border border-gray-300 rounded-b-lg px-3 py-2 focus-within:ring-2 focus-within:ring-primary/20 prose prose-sm max-w-none" :class="editor ? '' : 'rounded-t-lg'">
          <editor-content :editor="editor" class="lesson-editor" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Связанный тест</label>
        <select v-model="form.testId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 bg-white">
          <option :value="null">— Не привязан —</option>
          <option v-for="t in tests" :key="t.id" :value="t.id">{{ t.title }}</option>
        </select>
      </div>
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2">
          <input v-model="form.isActive" type="checkbox" class="rounded border-gray-300" />
          <span class="text-sm text-gray-700">Опубликован</span>
        </label>
      </div>
      <div class="pt-2">
        <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import { Mark } from '@tiptap/core'
import { api } from '@/api/client'

const FontSizeMark = Mark.create({
  name: 'fontSize',
  addAttributes() {
    return {
      fontSize: {
        default: null,
        parseHTML: (el) => el.style.fontSize || null,
        renderHTML: (attrs) => (attrs.fontSize ? { style: `font-size: ${attrs.fontSize}` } : {}),
      },
    }
  },
  parseHTML() {
    return [{ style: 'font-size', getAttrs: (value) => ({ fontSize: value }) }]
  },
  renderHTML({ HTMLAttributes }) {
    return ['span', HTMLAttributes, 0]
  },
  addCommands() {
    return {
      setFontSize: (fontSize) => ({ chain }) => chain().focus().setMark('fontSize', { fontSize }).run(),
      unsetFontSize: () => ({ chain }) => chain().focus().unsetMark('fontSize').run(),
    }
  },
})

const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id)
const tests = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const imageInputRef = ref(null)

const form = reactive({
  title: '',
  category: '',
  testId: null,
  isActive: true,
})

const editor = useEditor({
  content: '',
  extensions: [
    StarterKit.configure({
      paragraph: { HTMLAttributes: { class: 'mb-4' } },
      heading: { levels: [2, 3] },
    }),
    FontSizeMark,
    Image.configure({ HTMLAttributes: { class: 'max-w-full h-auto rounded-lg my-4' } }),
    Placeholder.configure({ placeholder: 'Содержание урока…' }),
  ],
  editorProps: { attributes: { class: 'outline-none min-h-[220px]' } },
})

onBeforeUnmount(() => editor.value?.destroy())

function applyFontSize(ev) {
  const size = ev.target.value
  ev.target.value = ''
  if (!size || !editor.value) return
  editor.value.chain().focus().setMark('fontSize', { fontSize: size }).run()
}
function triggerImageUpload() {
  imageInputRef.value?.click()
}
async function onImageSelected(ev) {
  const file = ev.target.files?.[0]
  ev.target.value = ''
  if (!file || !editor.value) return
  try {
    const res = await api.guru.uploadLessonImage(file)
    if (res.url) editor.value.chain().focus().setImage({ src: res.url }).run()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки изображения'
  }
}

async function load() {
  if (!id.value) return
  loading.value = true
  error.value = ''
  try {
    const [lesson, testsRes] = await Promise.all([
      api.guru.lesson(id.value),
      api.guru.tests().catch(() => ({ tests: [] })),
    ])
    tests.value = testsRes.tests || []
    form.title = lesson.title
    form.category = lesson.category || ''
    form.testId = lesson.testId || null
    form.isActive = lesson.isActive !== false
    if (editor.value) {
      editor.value.commands.setContent(lesson.content || '')
    }
  } catch (e) {
    error.value = e.data?.message || e.message || 'Урок не найден'
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(id, load)

async function submit() {
  if (!editor.value) return
  const html = editor.value.getHTML()
  const textOnly = html.replace(/<[^>]+>/g, '').trim()
  if (!form.title.trim() || !textOnly) {
    error.value = 'Заполните заголовок и содержание.'
    return
  }
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    await api.guru.updateLesson(id.value, {
      title: form.title.trim(),
      category: form.category.trim() || undefined,
      content: html,
      testId: form.testId || undefined,
      isActive: form.isActive ? 1 : 0,
    })
    success.value = 'Урок сохранён.'
    router.push({ name: 'GuruLessonView', params: { id: id.value } })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}
</script>

<style>
.lesson-editor .tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  height: 0;
}
.lesson-editor .tiptap img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1rem 0; }
.lesson-editor .tiptap ul { list-style-type: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
.lesson-editor .tiptap ol { list-style-type: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
.lesson-editor .tiptap h2 { font-size: 1.25rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
.lesson-editor .tiptap h3 { font-size: 1.125rem; font-weight: 600; margin-top: 0.75rem; margin-bottom: 0.5rem; }
</style>
