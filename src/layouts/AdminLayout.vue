<template>
  <div class="min-h-screen xl:flex">
    <AppSidebar />
    <Backdrop />
    <div
      class="flex-1 transition-all duration-300 ease-in-out"
      :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
    >
      <AppHeader />
      <div class="p-4 mx-auto max-w-screen-2xl md:p-6">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import { useSidebar } from '@/composables/useSidebar'
import Backdrop from '@/components/layout/Backdrop.vue'

const { isExpanded, isHovered } = useSidebar()
</script>
