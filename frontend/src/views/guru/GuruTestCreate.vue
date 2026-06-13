<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-6">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link to="/learning/tests" class="text-gray-500 hover:text-gray-700">Список тестов</router-link>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4">➕ Создать тест</h1>
    <p v-if="error" class="mb-4 p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>

    <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Название теста *</label>
          <input v-model.trim="form.title" required type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
          <input v-model.trim="form.category" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Проходной балл (%)</label>
          <input v-model.number="form.passScore" min="0" max="100" step="0.01" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Лимит на тест (сек)</label>
          <input v-model.number="form.timeLimit" min="0" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Лимит на вопрос (сек)</label>
          <input v-model.number="form.questionTimeLimit" min="0" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
          <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Изображение теста</label>
          <input type="file" accept="image/*" @change="onTestImageChange" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white" />
        </div>
      </div>

      <div class="flex items-center gap-2">
        <input id="testActive" v-model="form.isActive" type="checkbox" class="rounded border-gray-300" />
        <label for="testActive" class="text-sm text-gray-700">Активен</label>
      </div>

      <div class="pt-2 border-t border-gray-200">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold text-gray-800">Вопросы</h2>
          <button type="button" @click="addQuestion" class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">
            + Добавить вопрос
          </button>
        </div>

        <div class="space-y-4">
          <div v-for="(q, qIndex) in form.questions" :key="q.uid" class="p-4 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-800">Вопрос {{ qIndex + 1 }}</h3>
              <button type="button" @click="removeQuestion(qIndex)" class="px-2 py-1 rounded-md border border-red-300 text-red-700 hover:bg-red-50">
                Удалить
              </button>
            </div>

            <div class="space-y-2">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Текст вопроса *</label>
                <textarea v-model="q.questionText" rows="2" class="w-full px-2 py-1.5 border border-gray-300 rounded-md"></textarea>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Баллы</label>
                  <input v-model.number="q.points" min="1" type="number" class="w-full px-2 py-1.5 border border-gray-300 rounded-md" />
                </div>
                <div class="md:col-span-2">
                  <label class="block text-xs text-gray-500 mb-1">Изображение к вопросу</label>
                  <input type="file" accept="image/*" @change="onQuestionImageChange($event, qIndex)" class="w-full px-2 py-1.5 border border-gray-300 rounded-md bg-white" />
                </div>
              </div>

              <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                  <label class="block text-xs text-gray-500">Варианты ответов</label>
                  <div class="flex items-center gap-2">
                    <select
                      v-model="q.answerType"
                      class="text-xs px-2 py-1 rounded border border-gray-300 bg-white"
                      @change="onAnswerTypeChange(q)"
                    >
                      <option value="single">Один правильный</option>
                      <option value="multiple">Несколько правильных</option>
                    </select>
                    <button type="button" @click="addOption(qIndex)" class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">
                      + Вариант
                    </button>
                  </div>
                </div>
                <p class="text-xs text-gray-400 mb-2">
                  {{ q.answerType === 'multiple' ? 'Отметьте все правильные варианты' : 'Отметьте один правильный вариант' }}
                </p>
                <div class="space-y-1">
                  <div v-for="(opt, oIndex) in q.options" :key="opt.id" class="flex items-center gap-2">
                    <input
                      v-if="q.answerType === 'single'"
                      :name="'correct-' + q.uid"
                      type="radio"
                      :value="opt.id"
                      v-model="q.correctAnswer"
                    />
                    <input
                      v-else
                      type="checkbox"
                      :value="opt.id"
                      :checked="q.correctAnswers.includes(opt.id)"
                      @change="toggleCorrectAnswer(q, opt.id)"
                    />
                    <input v-model="opt.text" type="text" :placeholder="'Вариант ' + (oIndex + 1)" class="flex-1 px-2 py-1.5 border border-gray-300 rounded-md" />
                    <button type="button" @click="removeOption(qIndex, oIndex)" class="px-2 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50">
                      ×
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <button :disabled="saving" type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 disabled:opacity-50">
          {{ saving ? 'Сохранение...' : 'Сохранить тест' }}
        </button>
        <router-link to="/learning/tests" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Отмена</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const saving = ref(false)
const error = ref('')

let uidCounter = 1
function makeQuestion() {
  return {
    uid: uidCounter++,
    questionText: '',
    points: 1,
    answerType: 'single',
    correctAnswer: '1',
    correctAnswers: [],
    options: [
      { id: '1', text: '' },
      { id: '2', text: '' },
      { id: '3', text: '' },
      { id: '4', text: '' },
    ],
    imageFile: null,
  }
}

const form = reactive({
  title: '',
  category: '',
  description: '',
  timeLimit: '',
  questionTimeLimit: '',
  passScore: 70,
  isActive: true,
  imageFile: null,
  questions: [makeQuestion()],
})

function onTestImageChange(e) {
  form.imageFile = e.target.files?.[0] || null
}

function onQuestionImageChange(e, index) {
  form.questions[index].imageFile = e.target.files?.[0] || null
}

function addQuestion() {
  form.questions.push(makeQuestion())
}

function removeQuestion(index) {
  form.questions.splice(index, 1)
  if (form.questions.length === 0) addQuestion()
}

function addOption(questionIndex) {
  const question = form.questions[questionIndex]
  if (question.options.length >= 6) return
  const nextId = String(question.options.length + 1)
  question.options.push({ id: nextId, text: '' })
}

function onAnswerTypeChange(question) {
  if (question.answerType === 'multiple') {
    question.correctAnswers = question.correctAnswer ? [question.correctAnswer] : []
  } else {
    question.correctAnswer = question.correctAnswers[0] || question.options[0]?.id || '1'
    question.correctAnswers = []
  }
}

function toggleCorrectAnswer(question, optionId) {
  const i = question.correctAnswers.indexOf(optionId)
  if (i === -1) {
    question.correctAnswers.push(optionId)
  } else {
    question.correctAnswers.splice(i, 1)
  }
}

function removeOption(questionIndex, optionIndex) {
  const question = form.questions[questionIndex]
  if (question.options.length <= 2) return
  const removed = question.options.splice(optionIndex, 1)[0]
  if (question.answerType === 'multiple') {
    question.correctAnswers = question.correctAnswers.filter((id) => id !== removed.id)
  } else if (question.correctAnswer === removed.id) {
    question.correctAnswer = question.options[0]?.id || '1'
  }
}

function buildNormalizedQuestions() {
  const normalized = []
  const images = {}
  for (const q of form.questions) {
    const questionText = q.questionText.trim()
    if (!questionText) continue

    const options = q.options
      .map((opt, i) => ({ id: String(i + 1), text: (opt.text || '').trim() }))
      .filter((opt) => opt.text !== '')

    if (options.length < 2) {
      throw new Error('В каждом вопросе должно быть минимум 2 варианта ответа.')
    }

    const answerType = q.answerType === 'multiple' ? 'multiple' : 'single'
    let correctAnswer = q.correctAnswer
    let correctAnswers = [...(q.correctAnswers || [])].map(String)

    if (answerType === 'multiple') {
      correctAnswers = correctAnswers.filter((id) => options.some((opt) => opt.id === id))
      if (correctAnswers.length === 0) {
        throw new Error('В вопросах с несколькими ответами отметьте хотя бы один правильный вариант.')
      }
    } else {
      if (!options.some((opt) => opt.id === correctAnswer)) {
        correctAnswer = options[0].id
      }
    }

    const normalizedIndex = normalized.length
    normalized.push({
      questionText,
      points: q.points || 1,
      answerType,
      correctAnswer: answerType === 'single' ? correctAnswer : undefined,
      correctAnswers: answerType === 'multiple' ? correctAnswers : undefined,
      options,
    })
    if (q.imageFile) {
      images[normalizedIndex] = q.imageFile
    }
  }
  if (normalized.length === 0) {
    throw new Error('Добавьте хотя бы один корректный вопрос.')
  }
  return { questions: normalized, images }
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const { questions: normalizedQuestions, images: questionImages } = buildNormalizedQuestions()
    const { api } = await import('@/api/client')
    const fd = new FormData()
    fd.append('title', form.title.trim())
    fd.append('category', form.category.trim())
    fd.append('description', form.description)
    fd.append('timeLimit', form.timeLimit === '' ? '' : String(form.timeLimit))
    fd.append('questionTimeLimit', form.questionTimeLimit === '' ? '' : String(form.questionTimeLimit))
    fd.append('passScore', String(form.passScore ?? 70))
    fd.append('isActive', form.isActive ? '1' : '0')
    fd.append('questions', JSON.stringify(normalizedQuestions))
    if (form.imageFile) fd.append('imageFile', form.imageFile)
    Object.entries(questionImages).forEach(([index, file]) => fd.append(`questionImage_${index}`, file))

    const res = await api.guru.createTest(fd)
    router.push({ name: 'GuruTestView', params: { id: res.id } })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка сохранения теста'
  } finally {
    saving.value = false
  }
}
</script>
