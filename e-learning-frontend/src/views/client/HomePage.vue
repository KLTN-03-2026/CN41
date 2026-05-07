<template>
  <div>
    <HeroSection />
    <StatsSection />
    <FeaturedCategories :categories="categories" :loading="loading" />
    <FeaturedCourses :courses="featuredCourses" :loading="loading" />
    <WhyUsSection />
    <FeaturedTeachers :teachers="featuredTeachers" :loading="loading" />
    <LatestPosts :posts="latestPosts" :loading="loading" />
    <CtaSection />
  </div>
</template>

<script setup lang="ts">
import { useHomePage } from '@/composables/useHomePage'
import HeroSection from '@/components/client/home/HeroSection.vue'
import StatsSection from '@/components/client/home/StatsSection.vue'
import FeaturedCategories from '@/components/client/home/FeaturedCategories.vue'
import FeaturedCourses from '@/components/client/home/FeaturedCourses.vue'
import WhyUsSection from '@/components/client/home/WhyUsSection.vue'
import FeaturedTeachers from '@/components/client/home/FeaturedTeachers.vue'
import LatestPosts from '@/components/client/home/LatestPosts.vue'
import CtaSection from '@/components/client/home/CtaSection.vue'

const { featuredCourses, categories, latestPosts, featuredTeachers, loading } = useHomePage()
</script>
