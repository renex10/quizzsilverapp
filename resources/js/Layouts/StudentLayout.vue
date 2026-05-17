<!-- resources/js/Layouts/StudentLayout.vue -->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import SidebarStudent from '@/Components/Student/Dashboard/SidebarStudent.vue';
import UserDropdown   from '@/Components/Student/Dashboard/UserDropdown.vue';

defineProps({
  title: { type: String, default: 'QuizzSilver' },
});

// ─── Estado del sidebar ──────────────────────────────────────────────────────
const sidebarOpen = ref(true);
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
const isMobile    = computed(() => windowWidth.value < 1024);

const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value; };
const closeSidebar  = () => { if (isMobile.value) sidebarOpen.value = false; };

const handleResize = () => {
  windowWidth.value = window.innerWidth;
  sidebarOpen.value = window.innerWidth >= 1024;
};

onMounted(() => {
  handleResize();
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});
</script>

<template>
  <Head :title="title" />

  <div class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <SidebarStudent
      :is-open="sidebarOpen"
      :is-mobile="isMobile"
      @toggle="toggleSidebar"
      @close="closeSidebar"
    />

    <!-- Área de contenido — se desplaza según el sidebar -->
    <div
      class="flex-1 flex flex-col min-w-0 min-h-screen transition-all duration-300"
      :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-16'"
    >

      <!-- ─── Topbar ──────────────────────────────────────────────────────── -->
      <header class="sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-4 sm:px-6 h-14">

          <!-- Hamburger -->
          <button
            @click="toggleSidebar"
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
            aria-label="Toggle sidebar"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>

          <!-- Título central (slot opcional) -->
          <div class="flex-1 px-4">
            <slot name="topbar-title">
              <span class="text-sm font-medium text-gray-500">{{ title }}</span>
            </slot>
          </div>

          <!-- Acciones del topbar + UserDropdown -->
          <div class="flex items-center gap-2">
            <slot name="topbar-actions" />
            <!-- Mini panel de usuario -->
            <UserDropdown />
          </div>

        </div>
      </header>

      <!-- ─── Header de la página ──────────────────────────────────────────── -->
      <div
        v-if="$slots.header"
        class="bg-white border-b border-gray-100 px-4 sm:px-6 lg:px-8 py-5"
      >
        <slot name="header" />
      </div>

      <!-- ─── Contenido principal ──────────────────────────────────────────── -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <slot />
      </main>

      <!-- ─── Footer ───────────────────────────────────────────────────────── -->
      <footer class="px-6 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-400 text-center">
          QuizzSilver — Plataforma de Evaluaciones
        </p>
      </footer>

    </div>
  </div>
</template>