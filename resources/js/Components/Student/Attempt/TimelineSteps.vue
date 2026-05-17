
<!-- resources\js\Components\Student\Attempt\TimelineSteps.vue -->
<template>
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <div
        v-for="(page, idx) in total"
        :key="idx"
        class="flex-1 min-w-[60px] text-center relative"
      >
        <div
          class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300"
          :class="{
            'bg-indigo-600 text-white ring-4 ring-indigo-200': current === idx,
            'bg-green-500 text-white': current > idx,
            'bg-gray-300 text-gray-600': current < idx
          }"
        >
          {{ idx + 1 }}
        </div>
        <div class="text-xs mt-1 text-gray-500 hidden sm:block">
          {{ getPageRange(idx) }}
        </div>
        <div
          v-if="idx < total - 1"
          class="absolute top-4 left-1/2 w-full h-0.5 -z-10 transition-all duration-500"
          :class="{ 'bg-green-500': current > idx, 'bg-gray-300': current <= idx }"
          style="transform: translateX(50%);"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  current: Number,
  total: Number,
  questionsPerPage: Number,
  totalQuestions: Number,
});

const getPageRange = (pageIdx) => {
  const start = pageIdx * props.questionsPerPage + 1;
  const end = Math.min((pageIdx + 1) * props.questionsPerPage, props.totalQuestions);
  return `${start}-${end}`;
};
</script>