<!-- resources\js\Components\Student\Attempt\ProgressBar.vue -->
<template>
  <div class="mb-6">
    <div class="flex justify-between text-sm mb-1">
      <span>Progreso de respuestas</span>
      <span>{{ answered }} / {{ total }}</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
      <div
        class="bg-indigo-600 h-2 rounded-full transition-all duration-500 ease-out"
        :style="{ width: `${percentage}%` }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  answered: Number,
  total: Number,
});

const percentage = computed(() => (props.total ? (props.answered / props.total) * 100 : 0));
</script>