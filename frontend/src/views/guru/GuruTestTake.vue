<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="flex items-center gap-4 mb-4">
      <router-link to="/learning" class="text-gray-500 hover:text-gray-700">← Меню Гуру</router-link>
      <router-link :to="{ name: 'GuruTestView', params: { id: testId } }" class="text-gray-500 hover:text-gray-700">Тест</router-link>
    </div>
    <p v-if="loading" class="py-8 text-center text-gray-500">Загрузка...</p>
    <p v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-700">{{ error }}</p>
    <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-lg font-semibold text-gray-800">{{ testTitle }}</h2>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">Вопрос {{ currentIndex + 1 }} из {{ questions.length }}</span>
          <div class="w-24 h-2 bg-gray-200 rounded overflow-hidden">
            <div class="h-full bg-primary transition-all" :style="{ width: progressPercent + '%' }"></div>
          </div>
          <span v-if="timeLimit > 0" class="px-2 py-1 rounded bg-amber-100 text-amber-800 font-mono text-sm">{{ formatTime(timeLeft) }}</span>
        </div>
      </div>
      <div class="p-6">
        <template v-if="currentQuestion">
          <p class="font-medium text-gray-800 mb-1">{{ currentQuestion.questionText }}</p>
          <p v-if="isMultipleQuestion" class="text-sm text-gray-500 mb-3">Можно выбрать несколько вариантов</p>
          <img v-if="currentQuestion.image" :src="currentQuestion.image" alt="" class="rounded-lg max-h-48 object-contain mb-4" />
          <div class="space-y-2">
            <label
              v-for="opt in currentQuestion.options"
              :key="opt.id"
              class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer hover:bg-gray-50"
              :class="{
                'border-primary bg-primary/5': isMultipleQuestion
                  ? selectedAnswers.includes(opt.id)
                  : selectedAnswer === opt.id,
              }"
            >
              <input
                v-if="isMultipleQuestion"
                type="checkbox"
                :value="opt.id"
                :checked="selectedAnswers.includes(opt.id)"
                class="mt-1"
                @change="toggleAnswer(opt.id)"
              />
              <input
                v-else
                type="radio"
                :name="'q' + currentQuestion.id"
                :value="opt.id"
                v-model="selectedAnswer"
                class="mt-1"
              />
              <span class="text-gray-800">{{ opt.text }}</span>
            </label>
          </div>
          <p v-if="questionTimeLimit > 0" class="text-sm text-gray-500 mt-2">Осталось на вопрос: {{ questionTimeLeft }} сек</p>
        </template>
      </div>
      <div class="px-6 py-4 border-t border-gray-200 flex justify-between">
        <button
          v-if="currentIndex > 0"
          type="button"
          @click="prev"
          class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
        >
          ← Назад
        </button>
        <div v-else></div>
        <div class="flex gap-2">
          <button
            v-if="currentIndex < questions.length - 1"
            type="button"
            @click="next"
            class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90"
          >
            Далее →
          </button>
          <button
            v-else
            type="button"
            @click="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700 disabled:opacity-50"
          >
            {{ submitting ? 'Отправка...' : 'Завершить тест' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const testId = route.params.id
const testTitle = ref('')
const questions = ref([])
const currentIndex = ref(0)
const answers = ref({})
const selectedAnswer = ref('')
const selectedAnswers = ref([])
const loading = ref(true)
const error = ref('')
const submitting = ref(false)
const timeLimit = ref(0)
const timeLeft = ref(0)
const questionTimeLimit = ref(0)
const questionTimeLeft = ref(0)
const startedAt = ref('')
let timerInterval = null
let questionTimerInterval = null

const currentQuestion = computed(() => questions.value[currentIndex.value] || null)
const isMultipleQuestion = computed(() => currentQuestion.value?.answerType === 'multiple')
const progressPercent = computed(() =>
  questions.value.length ? ((currentIndex.value + 1) / questions.value.length) * 100 : 0
)

function toggleAnswer(optionId) {
  const i = selectedAnswers.value.indexOf(optionId)
  if (i === -1) {
    selectedAnswers.value.push(optionId)
  } else {
    selectedAnswers.value.splice(i, 1)
  }
}

function loadAnswerForCurrent() {
  const q = currentQuestion.value
  if (!q) return
  const saved = answers.value[String(q.id)]
  if (q.answerType === 'multiple') {
    selectedAnswers.value = Array.isArray(saved) ? [...saved] : saved ? [saved] : []
    selectedAnswer.value = ''
  } else {
    selectedAnswer.value = saved ?? ''
    selectedAnswers.value = []
  }
}

function formatTime(sec) {
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

function next() {
  saveCurrentAnswer()
  if (currentIndex.value < questions.value.length - 1) {
    currentIndex.value++
    loadAnswerForCurrent()
    resetQuestionTimer()
  }
}

function prev() {
  saveCurrentAnswer()
  if (currentIndex.value > 0) {
    currentIndex.value--
    loadAnswerForCurrent()
    resetQuestionTimer()
  }
}

function saveCurrentAnswer() {
  if (!currentQuestion.value) return
  const qid = String(currentQuestion.value.id)
  if (currentQuestion.value.answerType === 'multiple') {
    answers.value[qid] = [...selectedAnswers.value]
  } else if (selectedAnswer.value) {
    answers.value[qid] = selectedAnswer.value
  }
}

function resetQuestionTimer() {
  if (questionTimerInterval) clearInterval(questionTimerInterval)
  if (questionTimeLimit.value > 0) {
    questionTimeLeft.value = questionTimeLimit.value
    questionTimerInterval = setInterval(() => {
      questionTimeLeft.value--
      if (questionTimeLeft.value <= 0) {
        clearInterval(questionTimerInterval)
        if (currentIndex.value < questions.value.length - 1) next()
        else submit()
      }
    }, 1000)
  }
}

async function submit() {
  saveCurrentAnswer()
  if (submitting.value) return
  submitting.value = true
  const start = startedAt.value ? new Date(startedAt.value).getTime() : Date.now() - timeLeft.value * 1000
  const timeSpent = Math.round((Date.now() - start) / 1000)
  if (timerInterval) clearInterval(timerInterval)
  if (questionTimerInterval) clearInterval(questionTimerInterval)
  try {
    const { api } = await import('@/api/client')
    const res = await api.guru.testSubmit({
      testId: Number(testId),
      answers: answers.value,
      timeSpent,
      startedAt: startedAt.value || new Date().toISOString().slice(0, 19).replace('T', ' '),
    })
    router.push({ name: 'GuruTestResult', params: { id: res.resultId } })
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка отправки'
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const { api } = await import('@/api/client')
    const [testRes, questionsRes] = await Promise.all([
      api.guru.test(testId),
      api.guru.testQuestions(testId),
    ])
    testTitle.value = testRes.title
    timeLimit.value = testRes.timeLimit || 0
    questionTimeLimit.value = testRes.questionTimeLimit || 0
    timeLeft.value = timeLimit.value
    questionTimeLeft.value = questionTimeLimit.value
    startedAt.value = new Date().toISOString().slice(0, 19).replace('T', ' ')
    questions.value = questionsRes.questions || []
    if (questions.value.length) {
      loadAnswerForCurrent()
    }
    if (timeLimit.value > 0) {
      timerInterval = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
          clearInterval(timerInterval)
          submit()
        }
      }, 1000)
    }
    resetQuestionTimer()
  } catch (e) {
    error.value = e.data?.message || e.message || 'Ошибка загрузки'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  if (questionTimerInterval) clearInterval(questionTimerInterval)
})
</script>
