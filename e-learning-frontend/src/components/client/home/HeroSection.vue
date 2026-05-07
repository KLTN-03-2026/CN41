<template>
  <section
    class="relative bg-gradient-to-br from-primary-700 via-primary-600 to-blue-500 text-white overflow-hidden"
  >
    <!-- Animated background blobs -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div
        class="blob blob-1 absolute -top-20 -right-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"
      ></div>
      <div
        class="blob blob-2 absolute -bottom-24 -left-16 w-72 h-72 bg-blue-300/20 rounded-full blur-3xl"
      ></div>
      <div
        class="blob blob-3 absolute top-1/2 left-1/3 w-56 h-56 bg-yellow-300/10 rounded-full blur-2xl"
      ></div>
    </div>

    <!-- Floating particles -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <span
        v-for="p in particles"
        :key="p.id"
        class="particle absolute rounded-full bg-white"
        :style="p.style"
      />
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
        <!-- Left: text + search -->
        <div>
          <span
            class="inline-block bg-yellow-400/20 text-yellow-300 text-xs font-semibold px-3 py-1 rounded-full mb-4 border border-yellow-400/30"
          >
            🎓 Nền tảng học trực tuyến #1
          </span>
          <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold leading-tight mb-4">
            Học bất cứ điều gì,<br />
            <span class="text-yellow-300">mọi lúc mọi nơi</span>
          </h1>
          <p class="text-base text-primary-100 mb-8 max-w-md leading-relaxed">
            Khám phá hàng trăm khóa học chất lượng cao từ các giảng viên hàng đầu. Nâng cao kỹ năng
            và phát triển sự nghiệp ngay hôm nay.
          </p>

          <!-- Search bar -->
          <form @submit.prevent="onSearch" class="flex gap-2 max-w-lg">
            <div class="relative flex-1">
              <svg
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
              <input
                v-model="keyword"
                type="text"
                placeholder="Tìm kiếm khóa học..."
                class="w-full pl-10 pr-4 py-3.5 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-300 text-sm shadow-lg"
              />
            </div>
            <button
              type="submit"
              class="px-6 py-3.5 bg-yellow-400 hover:bg-yellow-300 active:bg-yellow-500 text-gray-900 font-semibold rounded-xl transition-colors text-sm whitespace-nowrap shadow-lg"
            >
              Tìm kiếm
            </button>
          </form>

          <!-- Stats -->
          <div class="flex flex-wrap gap-5 mt-8 text-sm">
            <div class="flex items-center gap-2 text-primary-100">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"
                  />
                </svg>
              </div>
              <span>Hàng trăm khóa học</span>
            </div>
            <div class="flex items-center gap-2 text-primary-100">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"
                  />
                </svg>
              </div>
              <span>Hàng nghìn học viên</span>
            </div>
            <div class="flex items-center gap-2 text-primary-100">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <span>Chứng chỉ hoàn thành</span>
            </div>
          </div>
        </div>

        <!-- Right: Illustration -->
        <div class="hidden md:flex justify-center items-center">
          <div class="relative w-full max-w-md">
            <!-- Main illustration card — float up/down -->
            <div
              class="float-slow bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-6 shadow-2xl"
            >
              <svg
                viewBox="0 0 480 360"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="w-full"
              >
                <!-- Background screen -->
                <rect
                  x="40"
                  y="20"
                  width="400"
                  height="260"
                  rx="16"
                  fill="white"
                  fill-opacity="0.15"
                />
                <rect
                  x="40"
                  y="20"
                  width="400"
                  height="40"
                  rx="16"
                  fill="white"
                  fill-opacity="0.2"
                />
                <!-- Dots on top bar -->
                <circle cx="68" cy="40" r="6" fill="#FF6B6B" fill-opacity="0.8" />
                <circle cx="88" cy="40" r="6" fill="#FFD93D" fill-opacity="0.8" />
                <circle cx="108" cy="40" r="6" fill="#6BCB77" fill-opacity="0.8" />

                <!-- Video area -->
                <rect
                  x="60"
                  y="76"
                  width="220"
                  height="130"
                  rx="10"
                  fill="white"
                  fill-opacity="0.1"
                />
                <circle cx="170" cy="141" r="28" fill="white" fill-opacity="0.25" />
                <!-- Play button -->
                <path d="M162 131 L186 141 L162 151 Z" fill="white" fill-opacity="0.9" />

                <!-- Animated progress bar (via CSS on the rect) -->
                <rect
                  x="60"
                  y="220"
                  width="220"
                  height="6"
                  rx="3"
                  fill="white"
                  fill-opacity="0.15"
                />
                <rect
                  class="progress-bar"
                  x="60"
                  y="220"
                  width="0"
                  height="6"
                  rx="3"
                  fill="#FFD93D"
                  fill-opacity="0.9"
                />

                <!-- Right panel: lesson list -->
                <rect
                  x="300"
                  y="76"
                  width="124"
                  height="174"
                  rx="10"
                  fill="white"
                  fill-opacity="0.08"
                />
                <rect x="314" y="92" width="50" height="6" rx="3" fill="white" fill-opacity="0.5" />
                <rect
                  x="314"
                  y="104"
                  width="86"
                  height="4"
                  rx="2"
                  fill="white"
                  fill-opacity="0.25"
                />
                <rect
                  x="314"
                  y="114"
                  width="76"
                  height="4"
                  rx="2"
                  fill="white"
                  fill-opacity="0.25"
                />

                <rect
                  x="314"
                  y="132"
                  width="50"
                  height="6"
                  rx="3"
                  fill="#6BCB77"
                  fill-opacity="0.8"
                />
                <rect
                  x="314"
                  y="144"
                  width="86"
                  height="4"
                  rx="2"
                  fill="white"
                  fill-opacity="0.25"
                />
                <rect
                  x="314"
                  y="154"
                  width="60"
                  height="4"
                  rx="2"
                  fill="white"
                  fill-opacity="0.25"
                />

                <rect
                  x="314"
                  y="172"
                  width="50"
                  height="6"
                  rx="3"
                  fill="white"
                  fill-opacity="0.3"
                />
                <rect
                  x="314"
                  y="184"
                  width="86"
                  height="4"
                  rx="2"
                  fill="white"
                  fill-opacity="0.15"
                />
                <rect
                  x="314"
                  y="194"
                  width="70"
                  height="4"
                  rx="2"
                  fill="white"
                  fill-opacity="0.15"
                />

                <!-- Bottom: Student avatars + rating -->
                <circle cx="80" cy="310" r="18" fill="#FFD93D" fill-opacity="0.8" />
                <circle cx="104" cy="310" r="18" fill="#6BCB77" fill-opacity="0.8" />
                <circle cx="128" cy="310" r="18" fill="#4D96FF" fill-opacity="0.8" />
                <circle cx="152" cy="310" r="18" fill="#FF6B6B" fill-opacity="0.8" />
                <rect
                  x="178"
                  y="302"
                  width="80"
                  height="8"
                  rx="4"
                  fill="white"
                  fill-opacity="0.5"
                />
                <rect
                  x="178"
                  y="314"
                  width="56"
                  height="6"
                  rx="3"
                  fill="#FFD93D"
                  fill-opacity="0.7"
                />

                <!-- Floating badge top-right -->
                <rect
                  x="330"
                  y="8"
                  width="110"
                  height="44"
                  rx="12"
                  fill="#FFD93D"
                  fill-opacity="0.95"
                />
                <text x="350" y="28" font-size="11" fill="#1a1a1a" font-weight="700">
                  ⭐ Đánh giá cao
                </text>
                <text x="355" y="44" font-size="10" fill="#555">4.9 / 5.0</text>

                <!-- Floating badge bottom-left -->
                <rect
                  x="40"
                  y="296"
                  width="24"
                  height="24"
                  rx="6"
                  fill="#6BCB77"
                  fill-opacity="0.9"
                />
                <text x="47" y="313" font-size="13" fill="white">✓</text>
              </svg>
            </div>

            <!-- Floating card: học viên mới — bob ngược pha -->
            <div
              class="float-fast absolute -bottom-4 -left-6 bg-white rounded-2xl shadow-xl px-4 py-3 flex items-center gap-3 border border-gray-100"
            >
              <div
                class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center text-lg"
              >
                🎓
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-800">Học viên mới đăng ký</p>
                <p class="text-xs text-gray-400">Vừa xong · Lập trình Web</p>
              </div>
            </div>

            <!-- Floating card: tiến độ — bob lệch pha -->
            <div
              class="float-medium absolute -top-4 -right-4 bg-white rounded-2xl shadow-xl px-4 py-3 border border-gray-100"
            >
              <p class="text-xs text-gray-500 mb-1">Tiến độ học</p>
              <div class="flex items-center gap-2">
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full w-3/4 bg-primary-500 rounded-full"></div>
                </div>
                <span class="text-xs font-bold text-primary-600">75%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const keyword = ref('')

function onSearch() {
  if (keyword.value.trim()) {
    router.push({ path: '/courses', query: { search: keyword.value.trim() } })
  } else {
    router.push('/courses')
  }
}

// Generate random floating particles
const particles = Array.from({ length: 18 }, (_, i) => ({
  id: i,
  style: {
    width: `${Math.random() * 5 + 3}px`,
    height: `${Math.random() * 5 + 3}px`,
    top: `${Math.random() * 100}%`,
    left: `${Math.random() * 100}%`,
    opacity: `${Math.random() * 0.25 + 0.05}`,
    animationDuration: `${Math.random() * 8 + 6}s`,
    animationDelay: `-${Math.random() * 10}s`,
  },
}))
</script>

<style scoped>
/* ── Blobs ── */
.blob {
  animation: blob-pulse 8s ease-in-out infinite;
}
.blob-2 {
  animation-delay: -3s;
  animation-duration: 10s;
}
.blob-3 {
  animation-delay: -6s;
  animation-duration: 12s;
}

@keyframes blob-pulse {
  0%,
  100% {
    transform: scale(1) translate(0, 0);
  }
  33% {
    transform: scale(1.08) translate(12px, -8px);
  }
  66% {
    transform: scale(0.95) translate(-8px, 10px);
  }
}

/* ── Illustration float ── */
.float-slow {
  animation: float-y 6s ease-in-out infinite;
}
.float-fast {
  animation: float-y 4s ease-in-out infinite;
  animation-delay: -2s;
}
.float-medium {
  animation: float-y 5s ease-in-out infinite;
  animation-delay: -1s;
}

@keyframes float-y {
  0%,
  100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
}

/* ── SVG progress bar fill animation ── */
.progress-bar {
  animation: fill-progress 3s ease-out forwards;
  animation-delay: 0.5s;
}
@keyframes fill-progress {
  from {
    width: 0;
  }
  to {
    width: 140px;
  }
}

/* ── Particles ── */
.particle {
  animation: particle-float linear infinite;
}
@keyframes particle-float {
  0% {
    transform: translateY(0px) translateX(0px);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-80px) translateX(20px);
    opacity: 0;
  }
}
</style>
