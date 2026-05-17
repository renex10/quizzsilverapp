<!-- resources/js/Components/Student/Dashboard/NavigationStudent.vue -->
<template>
  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

    <!-- Dashboard -->
    <ItemsNavigation
      :href="route('student.dashboard')"
      label="Inicio"
      :show-label="isOpen"
      @click="$emit('close')"
    >
      <template #icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
      </template>
    </ItemsNavigation>

    <!-- Catálogo -->
    <ItemsNavigation
      :href="route('student.catalog.index')"
      label="Catálogo"
      :show-label="isOpen"
      @click="$emit('close')"
    >
      <template #icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168
               18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5
               5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746
               0-3.332.477-4.5 1.253"/>
        </svg>
      </template>
    </ItemsNavigation>

    <!-- Mis Estadísticas -->
    <ItemsNavigation
      :href="route('student.stats.index')"
      label="Mis Estadísticas"
      :show-label="isOpen"
      @click="$emit('close')"
    >
      <template #icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0
               0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0
               0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </template>
    </ItemsNavigation>

    <!-- Separador -->
    <div class="pt-3 pb-1">
      <p v-if="isOpen" class="px-3 text-xs font-semibold text-white/30 uppercase tracking-wider">
        Cuenta
      </p>
      <div v-else class="border-t border-white/10 mx-2"></div>
    </div>

    <!-- Perfil -->
    <ItemsNavigation
      :href="route('profile.edit')"
      label="Mi Perfil"
      :show-label="isOpen"
      @click="$emit('close')"
    >
      <template #icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </template>
    </ItemsNavigation>

    <!-- Cerrar sesión -->
    <button
      @click="logout"
      class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
             text-white/70 hover:bg-white/10 hover:text-white transition-all duration-150"
    >
      <span class="shrink-0 w-5 h-5 flex items-center justify-center">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
      </span>
      <span v-if="isOpen" class="truncate">Cerrar sesión</span>
    </button>

  </nav>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import ItemsNavigation from './ItemsNavigation.vue';

defineProps({
  isOpen: { type: Boolean, default: true },
});

defineEmits(['close']);

const logout = () => {
  router.post(route('logout'));
};
</script>