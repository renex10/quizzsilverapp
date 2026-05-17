<!-- resources/js/Components/Common/Stats/FailedQuestionsTable.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="text-base font-semibold text-gray-800 mb-4">🔍 Preguntas más falladas</h3>

    <div v-if="questions.length === 0"
      class="text-center py-8 text-gray-400 text-sm">
      No hay suficientes datos todavía.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="(q, i) in questions"
        :key="q.question_id ?? i"
        class="flex items-start gap-3 p-3 rounded-lg bg-gray-50
               border border-gray-100 hover:border-red-100 transition-colors"
      >
        <!-- Número de veces fallada (badge) -->
        <div class="shrink-0 w-8 h-8 rounded-full bg-red-100 text-red-700
                    flex items-center justify-center text-xs font-bold">
          {{ q.count }}x
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-800 leading-snug">
            {{ q.question_text }}
          </p>
          <p class="text-xs text-gray-500 mt-0.5">
            Categoría: <span class="font-medium">{{ q.category }}</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  questions: { type: Array, default: () => [] },
  // [{ question_id, question_text, category, count }]
});
</script>