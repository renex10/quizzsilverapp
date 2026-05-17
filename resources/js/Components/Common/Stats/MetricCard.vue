<!-- resources/js/Components/Common/Stats/MetricCard.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5
              flex items-center gap-4 hover:shadow-md transition-shadow duration-200">
    <div
      class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-xl"
      :class="colorClass"
    >
      {{ icon }}
    </div>
    <div class="min-w-0">
      <p class="text-xs text-gray-500 font-medium uppercase tracking-wide truncate">
        {{ label }}
      </p>
      <p class="text-2xl font-black text-gray-800 leading-tight mt-0.5">
        {{ value }}<span v-if="suffix" class="text-base font-semibold text-gray-400 ml-0.5">{{ suffix }}</span>
      </p>
      <p v-if="subtext" class="text-xs text-gray-400 mt-0.5 truncate">{{ subtext }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  icon:    { type: String,  required: true },
  label:   { type: String,  required: true },
  value:   { required: true },
  suffix:  { type: String,  default: null },
  subtext: { type: String,  default: null },
  color:   { type: String,  default: 'indigo' },
});

const colorClass = computed(() => ({
  indigo: 'bg-indigo-50 text-indigo-600',
  green:  'bg-green-50  text-green-600',
  red:    'bg-red-50    text-red-600',
  yellow: 'bg-yellow-50 text-yellow-600',
  blue:   'bg-blue-50   text-blue-600',
  purple: 'bg-purple-50 text-purple-600',
  orange: 'bg-orange-50 text-orange-600',
}[props.color] ?? 'bg-gray-50 text-gray-600'));
</script>