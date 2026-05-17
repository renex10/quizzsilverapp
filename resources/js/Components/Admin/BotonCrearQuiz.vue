<!-- resources/js/Components/Admin/BotonCrearQuiz.vue -->
<template>
  <button
    @click="emit('openModal')"
    class="group relative inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5"
  >
    <!-- Icono de Heroicons: PlusCircleIcon -->
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
    </svg>
    <span class="text-base">Crear nueva evaluación</span>
  </button>
</template>

<script setup>
const emit = defineEmits(['openModal']);
</script>