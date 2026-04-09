<template>
  <div :class="isSidebar ? 'space-y-5' : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5'">
    <div 
      v-for="(feature, index) in features" 
      :key="index"
      :class="[
        'transition-all duration-300 group',
        isSidebar 
          ? 'flex items-start gap-3' 
          : 'bg-white border border-gray-100 rounded-2xl p-6 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:border-blue-100 flex flex-col items-center text-center'
      ]"
    >
      <div 
        :class="[
          'text-slate-400 group-hover:text-blue-600 transition-colors duration-300 shrink-0',
          isSidebar ? 'mt-0.5' : 'mb-6'
        ]"
      >
        <div v-html="feature.icon" :class="isSidebar ? 'w-5 h-5' : 'w-10 h-10'"></div>
      </div>
      <div :class="isSidebar ? 'flex-1' : ''">
        <h3 :class="[
          'font-bold text-gray-900 group-hover:text-blue-600 transition-colors',
          isSidebar ? 'text-[13px] mb-0.5' : 'text-lg mb-4'
        ]">{{ feature.title }}</h3>
        <p :class="[
          'text-gray-500 leading-relaxed',
          isSidebar ? 'text-[11px]' : 'text-[15px]'
        ]">{{ feature.description }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
export interface FeatureItem {
  icon: string
  title: string
  description: string
}

withDefaults(defineProps<{
  features: FeatureItem[]
  isSidebar?: boolean
}>(), {
  isSidebar: false
})
</script>
