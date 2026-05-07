<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Câu hỏi bài kiểm tra</h4>
        <p class="text-xs text-gray-500 mt-0.5">{{ questions.length }} câu hỏi</p>
      </div>
      <button
        @click="showGeneratePanel = !showGeneratePanel"
        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-300 transition-colors"
      >
        <span>✨</span> {{ showGeneratePanel ? 'Đóng' : 'Sinh câu hỏi AI' }}
      </button>
    </div>

    <!-- Generate Panel -->
    <div
      v-if="showGeneratePanel"
      class="rounded-xl border border-purple-200 dark:border-purple-700 bg-purple-50 dark:bg-purple-900/10 p-4 space-y-3"
    >
      <p class="text-xs font-medium text-purple-700 dark:text-purple-300">Nguồn dữ liệu cho AI</p>

      <div class="flex gap-2">
        <button
          @click="genSource = 'upload'"
          :class="
            genSource === 'upload'
              ? 'bg-purple-500 text-white'
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700'
          "
          class="flex-1 py-2 text-xs rounded-lg transition-colors"
        >
          📄 Upload PDF mới
        </button>
        <button
          @click="selectChapterSource()"
          :class="
            genSource === 'chapter'
              ? 'bg-purple-500 text-white'
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700'
          "
          class="flex-1 py-2 text-xs rounded-lg transition-colors"
        >
          📚 PDF trong chương
        </button>
      </div>

      <!-- Upload PDF -->
      <div v-if="genSource === 'upload'">
        <div
          v-if="!uploadedFile"
          @dragover.prevent
          @drop.prevent="
            (e) => {
              uploadedFile = e.dataTransfer?.files?.[0] ?? null
            }
          "
          @click="pdfInput?.click()"
          class="border-2 border-dashed border-purple-300 dark:border-purple-600 rounded-xl p-6 text-center cursor-pointer hover:border-purple-400 transition-colors"
        >
          <p class="text-sm text-purple-600 dark:text-purple-400">Nhấp hoặc kéo thả file PDF</p>
          <p class="text-xs text-gray-400 mt-1">Tối đa 20MB</p>
        </div>
        <div
          v-else
          class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
        >
          <span class="text-lg">📄</span>
          <span class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{
            uploadedFile.name
          }}</span>
          <button @click="uploadedFile = null" class="text-gray-400 hover:text-red-500">✕</button>
        </div>
        <input
          ref="pdfInput"
          type="file"
          accept=".pdf"
          class="hidden"
          @change="
            (e) => {
              uploadedFile = (e.target as HTMLInputElement).files?.[0] ?? null
            }
          "
        />
      </div>

      <!-- Chapter PDFs -->
      <div v-if="genSource === 'chapter'">
        <div v-if="loadingPdfs" class="text-center py-3 text-xs text-gray-400">Đang tải...</div>
        <div v-else-if="!chapterPdfs.length" class="text-center py-3 text-xs text-gray-400">
          Chương này chưa có tài liệu PDF nào.
        </div>
        <div v-else class="space-y-1">
          <p class="text-xs text-gray-500 mb-2">
            AI sẽ đọc tất cả {{ chapterPdfs.length }} file PDF dưới đây:
          </p>
          <div
            v-for="pdf in chapterPdfs"
            :key="pdf.id"
            class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300"
          >
            <span>📄</span> {{ pdf.name }}
          </div>
        </div>
      </div>

      <!-- Options -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-600 dark:text-gray-400 block mb-1">Số câu hỏi</label>
          <input
            v-model.number="genCount"
            type="number"
            min="1"
            max="20"
            class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-purple-400"
          />
        </div>
        <div>
          <label class="text-xs text-gray-600 dark:text-gray-400 block mb-1"
            >Số lần thử tối đa</label
          >
          <input
            v-model.number="maxAttempts"
            type="number"
            min="1"
            max="10"
            class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-purple-400"
          />
        </div>
      </div>

      <div>
        <label class="text-xs text-gray-600 dark:text-gray-400 block mb-1"
          >Yêu cầu bổ sung (tuỳ chọn)</label
        >
        <textarea
          v-model="customPrompt"
          rows="2"
          placeholder="VD: Tập trung vào phần định nghĩa và ví dụ, độ khó trung bình..."
          class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-purple-400 resize-none"
        />
      </div>

      <button
        @click="doGenerate"
        :disabled="
          generating ||
          (genSource === 'upload' && !uploadedFile) ||
          (genSource === 'chapter' && !chapterPdfs.length)
        "
        class="w-full py-2 text-sm font-medium rounded-lg bg-purple-500 text-white hover:bg-purple-600 disabled:opacity-50 flex items-center justify-center gap-2 transition-colors"
      >
        <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
        {{ generating ? generatingStep || 'Đang xử lý...' : '✨ Sinh câu hỏi' }}
      </button>
    </div>

    <!-- Questions List -->
    <div v-if="questions.length" class="space-y-3">
      <div
        v-for="(q, i) in questions"
        :key="q.id"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4"
      >
        <div class="flex items-start justify-between gap-2 mb-3">
          <span class="text-xs font-medium text-gray-500">Câu {{ i + 1 }}</span>
          <button
            @click="deleteQuestion(q.id)"
            class="text-gray-300 hover:text-red-500 text-xs transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- Editable question -->
        <textarea
          v-model="q.question"
          @blur="saveQuestion(q)"
          rows="2"
          class="w-full text-sm font-medium text-gray-800 dark:text-white/90 bg-transparent border-0 border-b border-dashed border-gray-200 dark:border-gray-700 focus:outline-none focus:border-blue-400 resize-none mb-3"
        />

        <div class="grid grid-cols-2 gap-2">
          <div
            v-for="opt in ['A', 'B', 'C', 'D'] as const"
            :key="opt"
            :class="
              q.correct_option === opt
                ? 'border-green-400 bg-green-50 dark:bg-green-900/20'
                : 'border-gray-200 dark:border-gray-700'
            "
            class="rounded-lg border p-2"
          >
            <div class="flex items-center gap-1.5 mb-1">
              <button
                @click="setCorrect(q, opt)"
                :class="
                  q.correct_option === opt
                    ? 'bg-green-500 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400'
                "
                class="w-5 h-5 rounded-full text-xs font-bold flex-shrink-0 flex items-center justify-center transition-colors"
                :title="q.correct_option === opt ? 'Đáp án đúng' : 'Chọn làm đáp án đúng'"
              >
                {{ opt }}
              </button>
              <span
                v-if="q.correct_option === opt"
                class="text-xs text-green-600 dark:text-green-400"
                >✓ Đúng</span
              >
            </div>
            <input
              v-model="q[`option_${opt.toLowerCase()}` as keyof typeof q]"
              @blur="saveQuestion(q)"
              type="text"
              class="w-full text-xs text-gray-700 dark:text-gray-300 bg-transparent border-0 border-b border-dashed border-gray-200 dark:border-gray-700 focus:outline-none focus:border-blue-400"
            />
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="!showGeneratePanel" class="text-center py-6 text-xs text-gray-400">
      Chưa có câu hỏi nào. Bấm "Sinh câu hỏi AI" để bắt đầu.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import http from '@/plugins/axios'

interface Question {
  id: number
  question: string
  option_a: string
  option_b: string
  option_c: string
  option_d: string
  correct_option: 'A' | 'B' | 'C' | 'D'
  order: number
}

interface ChapterPdf {
  id: number
  name: string
  url: string
}

const props = defineProps<{ lessonId: number }>()
const toast = useToast()

const questions = ref<Question[]>([])
const showGeneratePanel = ref(false)
const generating = ref(false)
const generatingStep = ref('')
const genSource = ref<'upload' | 'chapter'>('upload')
const genCount = ref(5)
const maxAttempts = ref(3)
const customPrompt = ref('')
const uploadedFile = ref<File | null>(null)
const pdfInput = ref<HTMLInputElement>()
const chapterPdfs = ref<ChapterPdf[]>([])
const loadingPdfs = ref(false)

onMounted(() => loadQuiz())

async function loadQuiz() {
  try {
    const res = await http.get(`/admin/lesson-quiz/${props.lessonId}`)
    if (res.data.data?.questions) {
      questions.value = res.data.data.questions
    }
  } catch {
    // Quiz chưa có — bình thường
  }
}

function selectChapterSource() {
  genSource.value = 'chapter'
  loadChapterPdfs()
}

async function loadChapterPdfs() {
  loadingPdfs.value = true
  try {
    const res = await http.get(`/admin/lesson-quiz/${props.lessonId}/chapter-pdfs`)
    chapterPdfs.value = res.data.data ?? []
  } catch {
    toast.error('Không thể tải danh sách PDF')
  } finally {
    loadingPdfs.value = false
  }
}

async function doGenerate() {
  generating.value = true
  generatingStep.value = 'Đang gửi yêu cầu...'
  try {
    const formData = new FormData()
    formData.append('source', genSource.value)
    formData.append('count', String(genCount.value))
    formData.append('max_attempts', String(maxAttempts.value))
    if (customPrompt.value) formData.append('custom_prompt', customPrompt.value)
    if (genSource.value === 'upload' && uploadedFile.value) {
      formData.append('file', uploadedFile.value)
    }

    const res = await http.post(`/admin/lesson-quiz/${props.lessonId}/generate`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    const jobId: number = res.data.data.job_id
    generatingStep.value = 'AI đang sinh câu hỏi...'
    await pollJobStatus(jobId)
  } catch (err: any) {
    const data = err.response?.data
    if (data?.errors) {
      const firstError = Object.values(data.errors)[0] as string[]
      toast.error(firstError[0] || data.message || 'Sinh câu hỏi thất bại')
    } else {
      toast.error(err.message || data?.message || 'Sinh câu hỏi thất bại')
    }
  } finally {
    generating.value = false
    generatingStep.value = ''
  }
}

async function pollJobStatus(jobId: number): Promise<void> {
  const MAX_POLLS = 60
  const INTERVAL_MS = 2000

  for (let i = 0; i < MAX_POLLS; i++) {
    await new Promise((r) => setTimeout(r, INTERVAL_MS))

    const res = await http.get(`/admin/lesson-quiz/jobs/${jobId}`)
    const payload = res.data.data

    if (payload.status === 'done') {
      questions.value = payload.questions
      showGeneratePanel.value = false
      toast.success(`Đã sinh ${payload.questions.length} câu hỏi thành công!`)
      return
    }

    if (payload.status === 'failed') {
      throw new Error(res.data.message || 'Sinh câu hỏi thất bại')
    }
  }
  throw new Error('Hết thời gian chờ. Vui lòng thử lại.')
}

async function saveQuestion(q: Question) {
  try {
    await http.patch(`/admin/quiz-questions/${q.id}`, {
      question: q.question,
      option_a: q.option_a,
      option_b: q.option_b,
      option_c: q.option_c,
      option_d: q.option_d,
      correct_option: q.correct_option,
    })
  } catch {
    toast.error('Lưu câu hỏi thất bại')
  }
}

async function setCorrect(q: Question, opt: 'A' | 'B' | 'C' | 'D') {
  q.correct_option = opt
  await saveQuestion(q)
}

async function deleteQuestion(id: number) {
  try {
    await http.delete(`/admin/quiz-questions/${id}`)
    questions.value = questions.value.filter((q) => q.id !== id)
    toast.success('Đã xóa câu hỏi')
  } catch {
    toast.error('Xóa thất bại')
  }
}
</script>
