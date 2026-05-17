<!-- resources/js/Components/Student/Attempt/NavigationButtons.vue -->
<template>
  <div class="flex justify-between items-center mt-8">

    <!-- Anterior -->
    <button
      type="button"
      @click="$emit('prev')"
      :disabled="currentPage === 0"
      class="flex items-center gap-1 px-5 py-2 bg-white border border-gray-300
             text-gray-700 rounded-lg hover:bg-gray-50
             disabled:opacity-40 disabled:cursor-not-allowed transition-all"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
      Anterior
    </button>

    <!-- Indicador de página -->
    <span class="text-sm text-gray-500">
      Página {{ currentPage + 1 }} de {{ totalPages }}
    </span>

    <!-- Siguiente -->
    <button
      v-if="currentPage < totalPages - 1"
      type="button"
      @click="$emit('next')"
      class="flex items-center gap-1 px-5 py-2 bg-indigo-600 text-white
             rounded-lg hover:bg-indigo-700 transition-all"
    >
      Siguiente
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </button>

    <!-- Finalizar (última página) -->
    <button
      v-else
      type="button"
      @click="$emit('submit')"
      :disabled="isSubmitting"
      class="flex items-center gap-2 px-6 py-2 font-semibold text-white
             bg-gradient-to-r from-indigo-600 to-purple-600
             hover:from-indigo-700 hover:to-purple-700
             rounded-lg shadow-md hover:shadow-lg
             disabled:opacity-50 disabled:cursor-not-allowed transition-all"
    >
      <svg
        v-if="isSubmitting"
        class="animate-spin w-4 h-4"
        fill="none" viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10"
          stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <svg
        v-else
        class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M5 13l4 4L19 7"/>
      </svg>
      {{ isSubmitting ? 'Enviando...' : 'Finalizar examen' }}
    </button>

  </div>
</template>

<script setup>
defineProps({
  currentPage:  { type: Number, required: true },
  totalPages:   { type: Number, required: true },
  isSubmitting: { type: Boolean, default: false },
});
defineEmits(['prev', 'next', 'submit']);
</script>