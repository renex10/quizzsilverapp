<!-- resources/js/Components/Student/Catalog/ExamStatusBadge.vue -->
<template>
  <span
    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="config.classes"
  >
    {{ config.icon }} {{ config.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true,
    // 'new' | 'in_progress' | 'passed' | 'failed' | 'abandoned'
  },
});

const config = computed(() => ({
  new:         { label: 'Nuevo',       icon: '📌', classes: 'bg-gray-100 text-gray-600' },
  in_progress: { label: 'En progreso', icon: '⏳', classes: 'bg-yellow-100 text-yellow-700' },
  passed:      { label: 'Aprobado',    icon: '✅', classes: 'bg-green-100 text-green-700' },
  failed:      { label: 'Reprobado',   icon: '❌', classes: 'bg-red-100 text-red-700' },
  abandoned:   { label: 'Retomable',   icon: '🔄', classes: 'bg-blue-100 text-blue-700' },
}[props.status] ?? { label: props.status, icon: '●', classes: 'bg-gray-100 text-gray-600' }));
</script>