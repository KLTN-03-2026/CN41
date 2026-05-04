<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Vai trò & Quyền hạn</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Quản lý các nhóm vai trò và phân quyền hệ thống
        </p>
      </div>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Thêm vai trò
      </button>
    </div>

    <!-- Table -->
    <div
      class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
          <thead>
            <tr
              class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-white/[0.02]"
            >
              <th
                class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4 w-[250px]"
              >
                Tên vai trò
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Số quyền hạn
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Số người dùng
              </th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <!-- Loading -->
            <template v-if="loading">
              <tr v-for="i in 3" :key="i">
                <td class="px-6 py-4">
                  <div class="h-4 w-32 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div
                    class="h-6 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse ml-auto"
                  ></div>
                </td>
              </tr>
            </template>

            <!-- Data rows -->
            <template v-else-if="roles.length">
              <tr
                v-for="role in roles"
                :key="role.id"
                class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{
                      formatRole(role.name)
                    }}</span>
                    <span
                      v-if="role.name === 'super-admin'"
                      class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 border border-red-200 dark:border-red-500/30"
                      >Mặc định</span
                    >
                  </div>
                  <div class="text-xs text-gray-400 mt-0.5">{{ role.name }}</div>
                </td>
                <td class="px-6 py-4">
                  <span v-if="role.name === 'super-admin'" class="text-green-600 font-medium"
                    >Toàn quyền</span
                  >
                  <span v-else class="text-gray-600 dark:text-gray-300 font-medium"
                    >{{ role.permissions?.length || 0 }} quyền</span
                  >
                </td>
                <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-medium">
                  {{ role.users_count || 0 }} người
                </td>
                <td class="px-6 py-4 text-right">
                  <div
                    class="flex items-center justify-end gap-1"
                    v-if="role.name !== 'super-admin'"
                  >
                    <button
                      @click="openEdit(role)"
                      class="p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-500/10 text-gray-500 hover:text-blue-500 transition-colors"
                      title="Sửa"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(role)"
                      class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-colors"
                      title="Xóa"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                  <div v-else>
                    <span class="text-xs text-gray-400 italic">Khóa bảo vệ</span>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-else>
              <td colspan="4" class="px-6 py-10 text-center text-gray-500">Không có dữ liệu.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 overflow-y-auto"
    >
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-3xl border border-gray-100 dark:border-gray-800 my-8"
      >
        <div
          class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center"
        >
          <h3 class="text-xl font-bold text-gray-800 dark:text-white">
            {{ editingRole ? 'Cập nhật Vai trò' : 'Thêm Vai trò mới' }}
          </h3>
          <button
            @click="showModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="p-6 space-y-6">
            <!-- Tên Role -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Mã Vai trò (Name) *</label
              >
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="VD: content-creator"
                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
              />
              <p v-if="formErrors.name" class="text-xs text-red-500 mt-1">
                {{ formErrors.name[0] }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                Nên viết liền không dấu, dùng dấu gạch ngang (VD: `editor`, `sales-manager`).
              </p>
            </div>

            <!-- Permission Checkboxes -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Gán Quyền (Permissions)</label
                >
                <button
                  type="button"
                  @click="toggleSelectAllPermissions"
                  class="text-xs text-blue-500 hover:underline"
                >
                  {{ isAllPermissionsSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="(perms, groupName) in groupedPermissions"
                  :key="groupName"
                  class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.02]"
                >
                  <h4
                    class="font-semibold text-gray-800 dark:text-gray-200 mb-3 capitalize flex items-center gap-2"
                  >
                    <svg
                      class="w-4 h-4 text-gray-400"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                      ></path>
                    </svg>
                    Quản lý {{ groupName }}
                  </h4>
                  <div class="space-y-2">
                    <label
                      v-for="p in perms"
                      :key="p.id"
                      class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 p-1 -ml-1 rounded transition-colors"
                    >
                      <input
                        type="checkbox"
                        :value="p.name"
                        v-model="form.permissions"
                        class="rounded border-gray-300 text-blue-500 focus:ring-blue-500/20"
                      />
                      <span class="text-gray-700 dark:text-gray-300">{{
                        formatPermissionAction(p.name)
                      }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="formError" class="text-sm text-red-500 font-medium">{{ formError }}</p>
          </div>

          <div
            class="p-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3 bg-gray-50/50 dark:bg-white/[0.01] rounded-b-2xl"
          >
            <button
              type="button"
              @click="showModal = false"
              class="px-5 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
            >
              Huỷ
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 text-sm font-medium rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"
            >
              <svg
                v-if="submitting"
                class="animate-spin h-4 w-4 text-white"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ submitting ? 'Đang lưu...' : 'Lưu Vai trò' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Xác nhận xóa"
      :loading="deleteLoading"
      confirm-text="Xóa"
      loading-text="Đang xử lý..."
      @cancel="showDeleteModal = false"
      @confirm="doDelete"
    >
      <p>
        Bạn có chắc muốn xoá vai trò
        <strong class="text-gray-800 dark:text-white/90">{{ deletingRole?.name }}</strong
        >?
      </p>
      <div
        class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 rounded-lg text-sm flex gap-2"
      >
        <svg
          class="w-5 h-5 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <span>Lưu ý: Bạn không thể xóa vai trò nếu đang có người dùng được gán vai trò này.</span>
      </div>
    </ConfirmModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { roleService } from '@/services/role.service'
import type { Role, Permission } from '@/services/role.service'
import { PlusIcon } from '@/components/icons'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

// ── State ──
const roles = ref<Role[]>([])
const allPermissions = ref<Permission[]>([])
const loading = ref(false)

// ── Helpers ──
function formatRole(roleName: string) {
  const map: Record<string, string> = {
    'super-admin': 'Super Admin',
    admin: 'Admin',
    teacher: 'Giáo viên',
    manager: 'Quản lý',
    staff: 'Nhân viên',
  }
  return map[roleName] || roleName
}

function formatPermissionAction(permName: string) {
  const parts = permName.split('.')
  if (parts.length < 2) return permName
  const action = parts[1]
  const map: Record<string, string> = {
    view: 'Xem',
    create: 'Tạo mới',
    edit: 'Sửa',
    delete: 'Xóa',
  }
  return map[action] || action
}

// Group permissions by prefix (e.g. users.view -> users)
const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}
  allPermissions.value.forEach((p) => {
    const parts = p.name.split('.')
    const groupName = parts.length > 1 ? parts[0] : 'other'
    if (!groups[groupName]) groups[groupName] = []
    groups[groupName].push(p)
  })
  return groups
})

// ── Fetch ──
async function fetchData() {
  loading.value = true
  try {
    const [rolesRes, permsRes] = await Promise.all([
      roleService.index(),
      roleService.getPermissions(),
    ])
    roles.value = rolesRes.data.data
    allPermissions.value = permsRes.data.data
  } catch (_err) {
    toast.error('Lỗi khi tải dữ liệu vai trò.')
  } finally {
    loading.value = false
  }
}

// ── Form Modal ──
const showModal = ref(false)
const editingRole = ref<Role | null>(null)
const submitting = ref(false)
const formError = ref('')
const formErrors = ref<Record<string, string[]>>({})
const form = reactive({ name: '', permissions: [] as string[] })

const isAllPermissionsSelected = computed({
  get: () =>
    form.permissions.length === allPermissions.value.length && allPermissions.value.length > 0,
  set: (val: boolean) => {
    form.permissions = val ? allPermissions.value.map((p) => p.name) : []
  },
})

function toggleSelectAllPermissions() {
  isAllPermissionsSelected.value = !isAllPermissionsSelected.value
}

function openCreate() {
  editingRole.value = null
  Object.assign(form, { name: '', permissions: [] })
  formError.value = ''
  formErrors.value = {}
  showModal.value = true
}

function openEdit(role: Role) {
  editingRole.value = role
  Object.assign(form, {
    name: role.name,
    permissions: role.permissions?.map((p) => p.name) || [],
  })
  formError.value = ''
  formErrors.value = {}
  showModal.value = true
}

async function submitForm() {
  submitting.value = true
  formError.value = ''
  formErrors.value = {}

  try {
    const data = { name: form.name, permissions: form.permissions }
    if (editingRole.value) {
      await roleService.update(editingRole.value.id, data)
      toast.success('Cập nhật vai trò thành công!')
    } else {
      await roleService.store(data)
      toast.success('Thêm vai trò mới thành công!')
    }
    showModal.value = false
    fetchData()
  } catch (err: unknown) {
    const error = err as any
    if (error.response?.status === 422) {
      formErrors.value = error.response.data.errors
    } else {
      formError.value = error.response?.data?.message || 'Có lỗi xảy ra khi lưu.'
    }
  } finally {
    submitting.value = false
  }
}

// ── Delete ──
const showDeleteModal = ref(false)
const deletingRole = ref<Role | null>(null)
const deleteLoading = ref(false)

function confirmDelete(role: Role) {
  deletingRole.value = role
  showDeleteModal.value = true
}

async function doDelete() {
  if (!deletingRole.value) return
  deleteLoading.value = true
  try {
    await roleService.destroy(deletingRole.value.id)
    toast.success('Đã xóa vai trò thành công!')
    showDeleteModal.value = false
    fetchData()
  } catch (err: unknown) {
    const error = err as any
    toast.error(error.response?.data?.message || 'Không thể xóa vai trò này.')
  } finally {
    deleteLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
