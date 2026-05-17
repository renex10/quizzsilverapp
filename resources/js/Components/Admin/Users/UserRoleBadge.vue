<!-- resources/js/Components/Admin/Users/UserRoleBadge.vue -->
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
  role: { type: String, required: true },
});

const config = computed(() => ({
  admin:   { label: 'Admin',      icon: '🛡️', classes: 'bg-purple-100 text-purple-700' },
  student: { label: 'Estudiante', icon: '🎓', classes: 'bg-indigo-100 text-indigo-700' },
}[props.role] ?? { label: props.role, icon: '●', classes: 'bg-gray-100 text-gray-600' }));
</script>