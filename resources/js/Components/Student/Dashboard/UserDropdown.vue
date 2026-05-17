<!-- resources/js/Components/Student/Dashboard/UserDropdown.vue -->
<template>
  <div class="relative" ref="dropdownRef">

    <!-- Botón disparador -->
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100
             transition-colors duration-150 focus:outline-none focus:ring-2
             focus:ring-indigo-300"
    >
      <!-- Avatar con inicial -->
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm
                  font-bold bg-gradient-to-br from-indigo-500 to-purple-600
                  text-white shrink-0">
        {{ userInitial }}
      </div>

      <!-- Nombre visible solo en desktop -->
      <div class="hidden sm:block text-left leading-tight">
        <p class="text-sm font-medium text-gray-700">{{ userName }}</p>
        <p class="text-xs text-gray-400">Estudiante</p>
      </div>

      <!-- Chevron -->
      <svg
        class="w-4 h-4 text-gray-400 transition-transform duration-200 hidden sm:block"
        :class="isOpen ? 'rotate-180' : ''"
        fill="none" stroke="currentColor" viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <!-- Panel desplegable -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl
               border border-gray-100 z-50 overflow-hidden origin-top-right"
      >

        <!-- Cabecera con info del usuario -->
        <div class="px-4 py-4 bg-gradient-to-br from-indigo-50 to-purple-50
                    border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full flex items-center justify-center
                        text-lg font-bold bg-gradient-to-br from-indigo-500
                        to-purple-600 text-white shrink-0">
              {{ userInitial }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-800 truncate">{{ userName }}</p>
              <p class="text-xs text-gray-500 truncate">{{ userEmail }}</p>
              <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full
                           font-medium bg-indigo-100 text-indigo-700">
                Estudiante
              </span>
            </div>
          </div>
        </div>

        <!-- Opciones -->
        <div class="py-1.5">

          <!-- Mi Perfil -->
          <Link
            :href="route('profile.edit')"
            @click="close"
            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700
                   hover:bg-indigo-50 hover:text-indigo-700 transition-colors group"
          >
            <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Mi Perfil
          </Link>

          <!-- Mis Estadísticas -->
          <Link
            :href="route('student.stats.index')"
            @click="close"
            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700
                   hover:bg-indigo-50 hover:text-indigo-700 transition-colors group"
          >
            <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0
                   002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2
                   2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2
                   2 0 01-2-2z"/>
            </svg>
            Mis Estadísticas
          </Link>

          <!-- Separador -->
          <div class="my-1.5 border-t border-gray-100"></div>

          <!-- Cerrar sesión -->
          <button
            @click="logout"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600
                   hover:bg-red-50 transition-colors group"
          >
            <svg class="w-4 h-4 text-red-400"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0
                   01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Cerrar sesión
          </button>

        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const isOpen      = ref(false);
const dropdownRef = ref(null);

const page      = usePage();
const userName  = computed(() => page.props.auth?.user?.name  ?? 'Estudiante');
const userEmail = computed(() => page.props.auth?.user?.email ?? '');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

const close = () => { isOpen.value = false; };

const logout = () => {
  close();
  router.post(route('logout'));
};

// Cerrar al hacer clic fuera del dropdown
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

// Cerrar con Escape
const handleEscape = (event) => {
  if (event.key === 'Escape') isOpen.value = false;
};

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
  document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside);
  document.removeEventListener('keydown', handleEscape);
});
</script>