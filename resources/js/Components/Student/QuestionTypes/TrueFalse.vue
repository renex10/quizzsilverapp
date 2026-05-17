<!-- resources/js/Components/Student/QuestionTypes/TrueFalse.vue -->
<template>
  <div class="flex gap-4">

    <!-- Verdadero -->
    <button
      type="button"
      @click="$emit('update:modelValue', true)"
      class="flex-1 flex items-center justify-center gap-2 py-4 rounded-xl border-2
             font-semibold text-sm transition-all duration-200"
      :class="modelValue === true
        ? 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-200'
        : 'border-gray-200 bg-white text-gray-600 hover:border-green-300 hover:bg-green-50/40'"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
          d="M5 13l4 4L19 7"/>
      </svg>
      Verdadero
    </button>

    <!-- Falso -->
    <button
      type="button"
      @click="$emit('update:modelValue', false)"
      class="flex-1 flex items-center justify-center gap-2 py-4 rounded-xl border-2
             font-semibold text-sm transition-all duration-200"
      :class="modelValue === false
        ? 'border-red-500 bg-red-50 text-red-700 ring-2 ring-red-200'
        : 'border-gray-200 bg-white text-gray-600 hover:border-red-300 hover:bg-red-50/40'"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
          d="M6 18L18 6M6 6l12 12"/>
      </svg>
      Falso
    </button>

  </div>
</template>

<script setup>
defineProps({
  question:   { type: Object, required: true },
  // null = sin responder, true = verdadero, false = falso
  modelValue: { default: null },
});
defineEmits(['update:modelValue']);
</script>