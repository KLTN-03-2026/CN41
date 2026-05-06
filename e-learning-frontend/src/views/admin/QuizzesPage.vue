<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Quản lý Quiz</h2>
        <p class="text-sm text-gray-500 mt-0.5">Tạo và quản lý quiz AI cho các bài học</p>
      </div>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Thêm quiz
      </button>
    </div>

    <!-- Filter -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <input
          v-model="filters.search"
          @input="fetchQuizzes(1)"
          type="text"
          placeholder="Tìm theo tiêu đề..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
        />
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
      </div>
      <select
        v-model="filters.status"
        @change="fetchQuizzes(1)"
        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:ring-2 focus:ring-blue-500/20"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đang hoạt động</option>
        <option value="0">Vô hiệu hoá</option>
      </select>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div
          class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"
        />
      </div>

      <div v-else-if="!quizzes.length" class="text-center py-12 text-gray-500 text-sm">
        Chưa có quiz nào.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Tiêu đề
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Lesson ID
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                Câu hỏi
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                Lượt thử
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                Trạng thái
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="quiz in quizzes"
              :key="quiz.id"
              class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-4 py-3 font-medium text-gray-800">{{ quiz.title }}</td>
              <td class="px-4 py-3 text-gray-500">{{ quiz.lesson_id }}</td>
              <td class="px-4 py-3 text-center text-gray-600">{{ quiz.questions_count }}</td>
              <td class="px-4 py-3 text-center text-gray-600">{{ quiz.max_attempts }}</td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="toggleStatus(quiz)"
                  :class="
                    quiz.status === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                  "
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ quiz.status === 1 ? 'Hoạt động' : 'Ẩn' }}
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openGenerateModal(quiz)"
                    class="px-2 py-1 text-xs rounded-md bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors font-medium"
                    title="Sinh câu hỏi AI"
                  >
                    ✨ AI
                  </button>
                  <button
                    @click="openEdit(quiz)"
                    class="px-2 py-1 text-xs rounded-md bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors"
                  >
                    Sửa
                  </button>
                  <button
                    @click="softDelete.confirm(quiz)"
                    class="px-2 py-1 text-xs rounded-md bg-red-100 text-red-700 hover:bg-red-200 transition-colors"
                  >
                    Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between px-4 py-3 border-t border-gray-100"
      >
        <p class="text-xs text-gray-500">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }}
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in pagination.last_page"
            :key="p"
            @click="fetchQuizzes(p)"
            :class="
              p === pagination.current_page
                ? 'bg-blue-500 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            "
            class="w-7 h-7 text-xs rounded-md transition-colors"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div
      v-if="softDelete.show.value"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      @click.self="softDelete.cancel()"
    >
      <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Xóa quiz</h3>
        <p class="text-sm text-gray-500 mb-5">Bạn có chắc muốn xóa quiz này?</p>
        <div class="flex justify-end gap-3">
          <button
            @click="softDelete.cancel()"
            class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200"
          >
            Hủy
          </button>
          <button
            @click="softDelete.execute()"
            :disabled="softDelete.loading.value"
            class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50"
          >
            Xóa
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
        <h3 class="font-semibold text-gray-800 mb-4">
          {{ editingId ? 'Cập nhật quiz' : 'Tạo quiz mới' }}
        </h3>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div v-if="!editingId">
            <label class="block text-sm font-medium text-gray-700 mb-1">Lesson ID</label>
            <input
              v-model.number="form.lesson_id"
              type="number"
              class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              :class="formErrors['lesson_id'] ? 'border-red-400' : 'border-gray-200'"
            />
            <p v-if="formErrors['lesson_id']" class="text-xs text-red-500 mt-1">
              {{ formErrors['lesson_id'][0] }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="VD: Quiz chương 1"
              class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              :class="formErrors['title'] ? 'border-red-400' : 'border-gray-200'"
            />
            <p v-if="formErrors['title']" class="text-xs text-red-500 mt-1">
              {{ formErrors['title'][0] }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả (tuỳ chọn)</label>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 resize-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Số lần thử tối đa</label>
              <input
                v-model.number="form.max_attempts"
                type="number"
                min="1"
                max="10"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Thời gian (phút, để trống = không giới hạn)</label
              >
              <input
                v-model.number="form.time_limit"
                type="number"
                min="1"
                placeholder="Không giới hạn"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
            <select
              v-model.number="form.status"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            >
              <option :value="1">Hoạt động</option>
              <option :value="0">Ẩn</option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50"
            >
              {{ submitting ? 'Đang lưu...' : editingId ? 'Cập nhật' : 'Tạo quiz' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- AI Generate Modal -->
    <div
      v-if="showGenerateModal"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      @click.self="closeGenerateModal"
    >
      <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-lg">
            ✨
          </div>
          <div>
            <h3 class="font-semibold text-gray-800">Sinh câu hỏi AI</h3>
            <p class="text-xs text-gray-500">
              Gemini sẽ tự động tạo câu hỏi trắc nghiệm từ nội dung bài học
            </p>
          </div>
        </div>

        <form @submit.prevent="doGenerate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng câu hỏi</label>
            <input
              v-model.number="generateForm.count"
              type="number"
              min="1"
              max="10"
              class="w-32 px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Prompt tuỳ chỉnh (tuỳ chọn)</label
            >
            <textarea
              v-model="generateForm.custom_prompt"
              rows="3"
              placeholder="Ví dụ: Tập trung vào các khái niệm cơ bản, độ khó trung bình..."
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 resize-none"
            />
            <p class="text-xs text-gray-400 mt-1">
              AI sẽ dùng tiêu đề + mô tả bài học làm context chính. Prompt này là bổ sung thêm.
            </p>
          </div>

          <!-- Generated questions preview -->
          <div v-if="generatedQuestions.length" class="space-y-3">
            <p class="text-sm font-medium text-gray-700">
              {{ generatedQuestions.length }} câu hỏi đã sinh:
            </p>
            <div
              v-for="(q, i) in generatedQuestions"
              :key="i"
              class="bg-gray-50 rounded-xl p-4 text-sm"
            >
              <p class="font-medium text-gray-800 mb-2">{{ i + 1 }}. {{ q.question }}</p>
              <div class="grid grid-cols-2 gap-1">
                <span
                  v-for="opt in ['a', 'b', 'c', 'd']"
                  :key="opt"
                  :class="
                    q.correct_option === opt.toUpperCase()
                      ? 'bg-green-100 text-green-700 font-medium'
                      : 'text-gray-600'
                  "
                  class="px-2 py-1 rounded-md text-xs"
                >
                  {{ opt.toUpperCase() }}) {{ q[`option_${opt}` as keyof typeof q] }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="closeGenerateModal"
              class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200"
            >
              Đóng
            </button>
            <button
              type="submit"
              :disabled="generating"
              class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-purple-500 text-white hover:bg-purple-600 disabled:opacity-50"
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
              {{
                generating
                  ? 'Đang sinh câu hỏi...'
                  : generatedQuestions.length
                    ? '✨ Sinh lại'
                    : '✨ Sinh câu hỏi'
              }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useQuizzes } from '@/composables/useQuizzes'
import { quizService } from '@/services/quiz.service'
import type { Quiz, QuizQuestion } from '@/services/quiz.service'

const toast = useToast()

const {
  quizzes,
  pagination,
  loading,
  filters,
  fetchQuizzes,
  showModal,
  editingId,
  submitting,
  formErrors,
  form,
  openCreate,
  openEdit,
  closeModal,
  submitForm,
  softDelete,
  toggleStatus,
} = useQuizzes()

onMounted(() => fetchQuizzes())

// ── AI Generate ─────────────────────────────────────────────
const showGenerateModal = ref(false)
const generating = ref(false)
const selectedQuiz = ref<Quiz | null>(null)
const generatedQuestions = ref<QuizQuestion[]>([])
const generateForm = ref({ count: 5, custom_prompt: '' })

function openGenerateModal(quiz: Quiz) {
  selectedQuiz.value = quiz
  generatedQuestions.value = []
  generateForm.value = { count: 5, custom_prompt: '' }
  showGenerateModal.value = true
}

function closeGenerateModal() {
  showGenerateModal.value = false
  fetchQuizzes(pagination.value?.current_page || 1)
}

async function doGenerate() {
  if (!selectedQuiz.value) return
  generating.value = true
  try {
    const res = await quizService.generate(selectedQuiz.value.id, {
      count: generateForm.value.count,
      custom_prompt: generateForm.value.custom_prompt || undefined,
    })
    generatedQuestions.value = res.data.data
    toast.success(`Đã sinh ${generatedQuestions.value.length} câu hỏi thành công!`)
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    toast.error(
      axiosError.response?.data?.message || 'Sinh câu hỏi thất bại. Kiểm tra lại GEMINI_API_KEY.',
    )
  } finally {
    generating.value = false
  }
}
</script>
