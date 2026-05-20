<template>
  <aside
    :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-700 text-gray-900 h-screen transition-all duration-300 ease-in-out z-[99999] border-r border-gray-200',
      {
        'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
        'lg:w-[90px]': !isExpanded && !isHovered,
        'translate-x-0 w-[290px]': isMobileOpen,
        '-translate-x-full': !isMobileOpen,
        'lg:translate-x-0': true,
      },
    ]"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <div :class="['py-8 flex', !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start']">
      <router-link to="/admin/dashboard">
        <img
          v-if="isExpanded || isHovered || isMobileOpen"
          class="dark:hidden"
          src="/images/logo/logo.svg"
          alt="EduLearn"
          width="140"
          height="30"
        />
        <img
          v-if="isExpanded || isHovered || isMobileOpen"
          class="hidden dark:block"
          src="/images/logo/logo-dark.svg"
          alt="EduLearn"
          width="140"
          height="30"
        />
        <img v-else src="/images/logo/logo-icon.svg" alt="EduLearn" width="36" height="36" />
      </router-link>
    </div>
    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
      <nav class="mb-6">
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <h2
              :class="[
                'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
              ]"
            >
              <template v-if="isExpanded || isHovered || isMobileOpen">
                {{ menuGroup.title }}
              </template>
              <HorizontalDots v-else />
            </h2>
            <ul class="flex flex-col gap-4">
              <li v-for="(item, index) in menuGroup.items" :key="item.name">
                <button
                  v-if="item.subItems"
                  @click="toggleSubmenu(groupIndex, index)"
                  :class="[
                    'menu-item group w-full',
                    {
                      'menu-item-active': isSubmenuOpen(groupIndex, index),
                      'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                    },
                    !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start',
                  ]"
                >
                  <span
                    :class="[
                      isSubmenuOpen(groupIndex, index)
                        ? 'menu-item-icon-active'
                        : 'menu-item-icon-inactive',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">{{
                    item.name
                  }}</span>
                  <ChevronDownIcon
                    v-if="isExpanded || isHovered || isMobileOpen"
                    :class="[
                      'ml-auto w-5 h-5 transition-transform duration-200',
                      {
                        'rotate-180 text-blue-500': isSubmenuOpen(groupIndex, index),
                      },
                    ]"
                  />
                </button>
                <router-link
                  v-else-if="item.path"
                  :to="item.path"
                  :class="[
                    'menu-item group',
                    {
                      'menu-item-active': isActive(item.path),
                      'menu-item-inactive': !isActive(item.path),
                    },
                  ]"
                >
                  <span
                    :class="[
                      isActive(item.path) ? 'menu-item-icon-active' : 'menu-item-icon-inactive',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">{{
                    item.name
                  }}</span>
                </router-link>
                <transition
                  @enter="startTransition"
                  @after-enter="endTransition"
                  @before-leave="startTransition"
                  @after-leave="endTransition"
                >
                  <div
                    v-show="
                      isSubmenuOpen(groupIndex, index) && (isExpanded || isHovered || isMobileOpen)
                    "
                  >
                    <ul class="mt-2 space-y-1 ml-9">
                      <li v-for="subItem in item.subItems" :key="subItem.name">
                        <router-link
                          :to="subItem.path"
                          :class="[
                            'menu-dropdown-item',
                            {
                              'menu-dropdown-item-active': isActive(subItem.path),
                              'menu-dropdown-item-inactive': !isActive(subItem.path),
                            },
                          ]"
                        >
                          {{ subItem.name }}
                        </router-link>
                      </li>
                    </ul>
                  </div>
                </transition>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import {
  GridIcon,
  UserGroupIcon,
  PieChartIcon,
  ChevronDownIcon,
  HorizontalDots,
  PageIcon,
  BoxIcon,
  BoxCubeIcon,
  ListIcon,
  TaskIcon,
  BarChartIcon,
  SettingsIcon,
} from '@/components/icons'
import { useSidebar } from '@/composables/useSidebar'

const route = useRoute()

const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar()

type MenuItem = {
  icon?: unknown
  name: string
  path?: string
  permission?: string
  hideForRoles?: string[]
  showOnlyForRoles?: string[]
  subItems?: MenuItem[]
}

type MenuGroup = {
  title: string
  hideForRoles?: string[]
  showOnlyForRoles?: string[]
  items: MenuItem[]
}

const rawMenuGroups: MenuGroup[] = [
  {
    title: 'Quản trị',
    items: [
      {
        icon: GridIcon,
        name: 'Dashboard',
        path: '/admin/dashboard',
        permission: 'dashboard.view',
      },
      {
        icon: BoxCubeIcon,
        name: 'Khóa học',
        subItems: [
          { name: 'Danh sách', path: '/admin/courses', permission: 'courses.view' },
          { name: 'Thêm mới', path: '/admin/courses/create', permission: 'courses.create' },
        ],
      },
      {
        icon: ListIcon,
        name: 'Danh mục',
        path: '/admin/categories',
        permission: 'categories.view',
      },
      {
        icon: UserGroupIcon,
        name: 'Người dùng',
        subItems: [
          {
            name: 'Quản trị viên',
            path: '/admin/users',
            permission: 'users.view',
            hideForRoles: ['teacher'],
          },
          {
            name: 'Giảng viên',
            path: '/admin/teachers',
            permission: 'users.view',
            hideForRoles: ['teacher'],
          },
          { name: 'Học viên', path: '/admin/students', permission: 'students.view' },
        ],
      },
    ],
  },
  {
    title: 'Kinh doanh',
    items: [
      {
        icon: BoxIcon,
        name: 'Đơn hàng',
        path: '/admin/orders',
        permission: 'orders.view',
      },
      {
        icon: TaskIcon,
        name: 'Mã giảm giá',
        path: '/admin/coupons',
        permission: 'coupons.view',
      },
    ],
  },
  {
    title: 'Hoa hồng',
    hideForRoles: ['teacher'],
    items: [
      {
        icon: BarChartIcon,
        name: 'Yêu cầu rút tiền',
        path: '/admin/payouts',
      },
      {
        icon: PieChartIcon,
        name: 'Hoa hồng giảng viên',
        path: '/admin/teacher-earnings',
      },
      {
        icon: SettingsIcon,
        name: 'Cài đặt tỷ lệ',
        path: '/admin/commission-settings',
      },
    ],
  },
  {
    title: 'Hệ thống',
    items: [
      {
        icon: PieChartIcon,
        name: 'Phân quyền',
        path: '/admin/roles',
        permission: 'roles.view',
      },
      {
        icon: ListIcon,
        name: 'Lịch sử hoạt động',
        path: '/admin/system-logs',
        permission: 'system.logs.view',
      },
    ],
  },
  {
    title: 'Nội dung',
    items: [
      {
        icon: PageIcon,
        name: 'Tin tức',
        subItems: [
          { name: 'Bài viết', path: '/admin/posts', permission: 'posts.view' },
          { name: 'Danh mục', path: '/admin/post-categories', permission: 'categories.view' },
          { name: 'Thẻ (Tags)', path: '/admin/tags', permission: 'tags.view' },
          { name: 'Bình luận', path: '/admin/post-comments', permission: 'comments.view' },
        ],
      },
    ],
  },
]

import { useAdminAuthStore } from '@/stores/adminAuth.store'
const adminStore = useAdminAuthStore()

const hasPermission = (item: MenuItem) => {
  const userRoles = adminStore.user?.roles || []

  if (item.showOnlyForRoles?.length) {
    if (!item.showOnlyForRoles.some((r) => userRoles.includes(r))) return false
  }

  if (item.hideForRoles?.length) {
    if (item.hideForRoles.some((r) => userRoles.includes(r))) return false
  }

  if (!item.permission) return true
  if (userRoles.includes('super-admin')) return true
  return adminStore.user?.permissions?.includes(item.permission) || false
}

const menuGroups = computed(() => {
  const userRoles = adminStore.user?.roles || []

  return rawMenuGroups
    .filter((group) => {
      if (group.showOnlyForRoles?.length) {
        if (!group.showOnlyForRoles.some((r) => userRoles.includes(r))) return false
      }
      if (group.hideForRoles?.length) {
        if (group.hideForRoles.some((r) => userRoles.includes(r))) return false
      }
      return true
    })
    .map((group) => {
      const filteredItems = group.items
        .filter((item) => hasPermission(item))
        .map((item) => {
          if (item.subItems) {
            const filteredSub = item.subItems.filter((sub) => hasPermission(sub))
            return { ...item, subItems: filteredSub.length ? filteredSub : undefined }
          }
          return item
        })
        .filter((item) => !item.subItems || item.subItems.length > 0)

      return { ...group, items: filteredItems }
    })
    .filter((group) => group.items.length > 0)
})

const isActive = (path: string) => route.path === path || route.path.startsWith(path + '/')

const toggleSubmenu = (groupIndex: number, itemIndex: number) => {
  const key = `${groupIndex}-${itemIndex}`
  openSubmenu.value = openSubmenu.value === key ? null : key
}

const isAnySubmenuRouteActive = computed(() => {
  return menuGroups.value.some((group) =>
    group.items.some(
      (item) => item.subItems && item.subItems.some((subItem) => isActive(subItem.path)),
    ),
  )
})

const isSubmenuOpen = (groupIndex: number, itemIndex: number) => {
  const key = `${groupIndex}-${itemIndex}`
  return (
    openSubmenu.value === key ||
    (isAnySubmenuRouteActive.value &&
      menuGroups.value[groupIndex]?.items[itemIndex]?.subItems?.some((subItem) =>
        isActive(subItem.path),
      ))
  )
}

const startTransition = (el: Element) => {
  const htmlEl = el as HTMLElement
  htmlEl.style.height = 'auto'
  const height = htmlEl.scrollHeight
  htmlEl.style.height = '0px'
  void htmlEl.offsetHeight // force reflow
  htmlEl.style.height = height + 'px'
}

const endTransition = (el: Element) => {
  const htmlEl = el as HTMLElement
  htmlEl.style.height = ''
}
</script>
