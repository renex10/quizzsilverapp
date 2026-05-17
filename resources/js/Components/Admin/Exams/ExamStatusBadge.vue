<template>
  <span
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
    :class="config.classes"
  >
    <span>{{ config.dot }}</span>
    {{ config.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
});

const config = computed(() => {
  const map = {
    published: {
      label:   'Publicado',
      dot:     '●',
      classes: 'bg-green-100 text-green-700',
    },
    draft: {
      label:   'Borrador',
      dot:     '●',
      classes: 'bg-yellow-100 text-yellow-700',
    },
  };
  return map[props.status] ?? {
    label:   props.status,
    dot:     '●',
    classes: 'bg-gray-100 text-gray-600',
  };
});
</script>