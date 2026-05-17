<!-- resources/js/Components/Student/Dashboard/ItemsNavigation.vue -->
<template>
  <Link
    :href="href"
    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
           transition-all duration-150 group"
    :class="isActive
      ? 'bg-white/20 text-white'
      : 'text-white/70 hover:bg-white/10 hover:text-white'"
    @click="$emit('click')"
  >
    <!-- Ícono -->
    <span class="shrink-0 w-5 h-5 flex items-center justify-center">
      <slot name="icon" />
    </span>

    <!-- Label (oculto cuando el sidebar está cerrado) -->
    <span v-if="showLabel" class="truncate transition-opacity duration-200">
      {{ label }}
    </span>

    <!-- Badge de notificación opcional -->
    <span
      v-if="badge && showLabel"
      class="ml-auto shrink-0 text-xs bg-white/20 text-white
             px-1.5 py-0.5 rounded-full"
    >
      {{ badge }}
    </span>
  </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  href:      { type: String,  required: true },
  label:     { type: String,  required: true },
  showLabel: { type: Boolean, default: true },
  badge:     { type: [String, Number], default: null },
});

defineEmits(['click']);

const page = usePage();

// Detectar si este item está activo comparando la URL actual
const isActive = computed(() => {
  const currentUrl = page.url;
  return currentUrl.startsWith(props.href) || currentUrl === props.href;
});
</script>