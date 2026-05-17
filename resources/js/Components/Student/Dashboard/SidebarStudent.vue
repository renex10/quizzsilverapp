<!-- resources/js/Components/Student/Dashboard/SidebarStudent.vue -->
<template>
  <!-- Overlay para móvil -->
  <div
    v-if="isMobile && isOpen"
    class="fixed inset-0 bg-black/40 z-20 lg:hidden"
    @click="$emit('close')"
  />

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 h-full z-30 flex flex-col
           bg-gradient-to-b from-indigo-900 to-purple-900
           transition-all duration-300 ease-in-out"
    :class="isOpen ? 'w-64' : 'w-0 lg:w-16 overflow-hidden'"
  >

    <!-- Logo y nombre -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-white/10 shrink-0">
      <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
        <span class="text-white font-bold text-sm">QS</span>
      </div>
      <span
        v-if="isOpen"
        class="text-white font-bold text-sm truncate transition-opacity duration-200"
      >
        QuizzSilver
      </span>
    </div>

    <!-- Navegación -->
    <NavigationStudent :is-open="isOpen" @close="$emit('close')" />

    <!-- Footer del sidebar: datos del usuario -->
    <div
      v-if="isOpen"
      class="mt-auto px-4 py-4 border-t border-white/10 shrink-0"
    >
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center
                    justify-center text-white text-xs font-bold shrink-0">
          {{ userInitial }}
        </div>
        <div class="min-w-0">
          <p class="text-white text-xs font-medium truncate">{{ userName }}</p>
          <p class="text-white/50 text-xs truncate">Estudiante</p>
        </div>
      </div>
    </div>

  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NavigationStudent from './NavigationStudent.vue';

defineProps({
  isOpen:   { type: Boolean, default: true },
  isMobile: { type: Boolean, default: false },
});

defineEmits(['toggle', 'close']);

const page     = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? 'Estudiante');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
</script>