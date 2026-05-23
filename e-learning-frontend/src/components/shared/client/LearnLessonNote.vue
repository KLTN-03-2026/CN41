<template>
  <div class="lesson-note-panel">
    <!-- Header -->
    <div class="note-panel-header">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
      </svg>
      <span>Ghi chú của tôi</span>
      <span v-if="notes.length" class="note-count">{{ notes.length }}</span>
    </div>

    <!-- Loading -->
    <div v-if="listLoading" class="note-empty">Đang tải...</div>

    <!-- Empty -->
    <div v-else-if="!notes.length && !showAddForm" class="note-empty">
      Chưa có ghi chú nào. Bấm nút bên dưới để thêm.
    </div>

    <!-- Notes list -->
    <div v-else-if="notes.length" class="note-list">
      <div v-for="note in notes" :key="note.id" class="note-item">
        <!-- View mode -->
        <template v-if="editingId !== note.id">
          <button
            v-if="note.timestamp_seconds !== null"
            class="note-timestamp"
            @click="emit('seek-to', note.timestamp_seconds!)"
            :title="`Nhảy đến ${formatTime(note.timestamp_seconds!)}`"
          >
            {{ formatTime(note.timestamp_seconds!) }}
          </button>
          <p class="note-content">{{ note.content }}</p>
          <div class="note-actions">
            <button class="note-btn-edit" @click="startEdit(note)" title="Sửa">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button class="note-btn-delete" @click="deleteNote(note.id)" :disabled="deletingId === note.id" title="Xóa">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </template>

        <!-- Edit mode -->
        <template v-else>
          <textarea
            v-model="editContent"
            class="note-textarea"
            rows="3"
            maxlength="10000"
            autofocus
          />
          <div class="note-edit-actions">
            <button class="btn-note-save" :disabled="saving" @click="saveEdit(note.id)">
              {{ saving ? 'Đang lưu...' : 'Lưu' }}
            </button>
            <button class="btn-note-cancel" @click="cancelEdit">Hủy</button>
          </div>
        </template>
      </div>
    </div>

    <!-- Add form -->
    <div v-if="showAddForm" class="note-add-form">
      <div v-if="currentVideoTime !== undefined && currentVideoTime > 0" class="note-add-timestamp">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Mốc thời gian: <strong>{{ formatTime(Math.floor(currentVideoTime)) }}</strong>
      </div>
      <textarea
        v-model="newContent"
        class="note-textarea"
        placeholder="Nhập ghi chú..."
        rows="3"
        maxlength="10000"
        ref="addTextareaRef"
      />
      <div class="note-add-footer">
        <span class="note-charcount">{{ newContent.length }} / 10.000</span>
        <div class="note-add-actions">
          <button class="btn-note-cancel" @click="cancelAdd">Hủy</button>
          <button class="btn-note-save" :disabled="saving || !newContent.trim()" @click="addNote">
            {{ saving ? 'Đang lưu...' : 'Thêm ghi chú' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add button -->
    <button v-if="!showAddForm" class="btn-add-note" @click="openAddForm">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
      </svg>
      Thêm ghi chú{{ currentVideoTime && currentVideoTime > 0 ? ` tại ${formatTime(Math.floor(currentVideoTime))}` : '' }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { lessonService } from '@/services/lesson.service'
import type { LessonNote } from '@/types'

const props = defineProps<{
  lessonId: number
  currentVideoTime?: number
}>()

const emit = defineEmits<{
  'seek-to': [seconds: number]
}>()

const notes = ref<LessonNote[]>([])
const listLoading = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)

const showAddForm = ref(false)
const newContent = ref('')
const addTextareaRef = ref<HTMLTextAreaElement | null>(null)

const editingId = ref<number | null>(null)
const editContent = ref('')

function formatTime(seconds: number): string {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${String(s).padStart(2, '0')}`
}

async function loadNotes() {
  listLoading.value = true
  try {
    const res = await lessonService.listNotes(props.lessonId)
    notes.value = res.data.data ?? []
  } catch {
    notes.value = []
  } finally {
    listLoading.value = false
  }
}

function openAddForm() {
  showAddForm.value = true
  newContent.value = ''
  nextTick(() => addTextareaRef.value?.focus())
}

function cancelAdd() {
  showAddForm.value = false
  newContent.value = ''
}

async function addNote() {
  if (!newContent.value.trim()) return
  saving.value = true
  try {
    const ts = props.currentVideoTime && props.currentVideoTime > 0
      ? Math.floor(props.currentVideoTime)
      : undefined
    const res = await lessonService.createNote(props.lessonId, newContent.value.trim(), ts)
    const created = res.data.data
    if (created) {
      const insertAt = notes.value.findIndex(
        (n) => (n.timestamp_seconds ?? Infinity) > (created.timestamp_seconds ?? Infinity),
      )
      if (insertAt === -1) {
        notes.value.push(created)
      } else {
        notes.value.splice(insertAt, 0, created)
      }
    }
    cancelAdd()
  } catch {
    // keep form open on error
  } finally {
    saving.value = false
  }
}

function startEdit(note: LessonNote) {
  editingId.value = note.id
  editContent.value = note.content
}

function cancelEdit() {
  editingId.value = null
  editContent.value = ''
}

async function saveEdit(noteId: number) {
  if (!editContent.value.trim()) return
  saving.value = true
  try {
    const res = await lessonService.updateNote(noteId, editContent.value.trim())
    const updated = res.data.data
    if (updated) {
      const idx = notes.value.findIndex((n) => n.id === noteId)
      if (idx !== -1) notes.value[idx] = { ...notes.value[idx], ...updated }
    }
    cancelEdit()
  } catch {
    // keep edit mode on error
  } finally {
    saving.value = false
  }
}

async function deleteNote(noteId: number) {
  deletingId.value = noteId
  try {
    await lessonService.deleteNote(noteId)
    notes.value = notes.value.filter((n) => n.id !== noteId)
  } catch {
    // ignore
  } finally {
    deletingId.value = null
  }
}

watch(
  () => props.lessonId,
  () => {
    cancelAdd()
    cancelEdit()
    loadNotes()
  },
  { immediate: true },
)
</script>
