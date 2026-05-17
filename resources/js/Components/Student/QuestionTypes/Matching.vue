<!-- resources/js/Components/Student/QuestionTypes/Matching.vue -->
<template>
  <div class="space-y-3">

    <p class="text-xs text-gray-400 mb-2">
      Para cada elemento de la columna izquierda, seleccioná su correspondiente en la derecha.
    </p>

    <div
      v-for="leftItem in question.leftColumn"
      :key="leftItem.id"
      class="flex items-center gap-3 p-3 rounded-lg border bg-white
             transition-all duration-200"
      :class="currentMatch(leftItem.id)
        ? 'border-indigo-300 bg-indigo-50/50'
        : 'border-gray-200'"
    >
      <!-- Columna izquierda — concepto -->
      <div class="flex-1 flex items-center gap-2 min-w-0">
        <span
          class="shrink-0 w-6 h-6 rounded border text-xs font-bold
                 flex items-center justify-center bg-white border-gray-300 text-gray-600"
        >
          {{ leftItem.id }}
        </span>
        <span class="text-sm text-gray-800 truncate">{{ leftItem.text }}</span>
      </div>

      <!-- Flecha -->
      <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none"
        stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 8l4 4m0 0l-4 4m4-4H3"/>
      </svg>

      <!-- Columna derecha — select de opciones -->
      <div class="flex-1">
        <select
          :value="currentMatch(leftItem.id)"
          @change="onMatch(leftItem.id, $event.target.value)"
          class="w-full text-sm border rounded-lg px-3 py-2 bg-white
                 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors"
          :class="currentMatch(leftItem.id)
            ? 'border-indigo-400 text-gray-800'
            : 'border-gray-300 text-gray-400'"
        >
          <option value="">-- Seleccioná --</option>
          <option
            v-for="rightItem in availableOptions(leftItem.id)"
            :key="rightItem.id"
            :value="rightItem.id"
          >
            {{ rightItem.id }}. {{ rightItem.text }}
          </option>
        </select>
      </div>
    </div>

    <!-- Indicador de pares completados -->
    <p class="text-xs text-right"
      :class="completedPairs === question.leftColumn.length
        ? 'text-green-600 font-medium'
        : 'text-gray-400'"
    >
      {{ completedPairs }} / {{ question.leftColumn.length }} pares relacionados
    </p>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  question:   { type: Object, required: true },
  // modelValue es un objeto: { "L1": "R3", "L2": "R1", "L3": "R2" }
  modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const matches = computed(() => props.modelValue ?? {});

// Obtener el valor seleccionado para un elemento izquierdo
const currentMatch = (leftId) => matches.value[leftId] ?? '';

// Opciones disponibles para un elemento izquierdo:
// - La opción ya seleccionada para este mismo elemento (siempre disponible)
// - Opciones no seleccionadas por ningún otro elemento
const availableOptions = (leftId) => {
  const usedByOthers = Object.entries(matches.value)
    .filter(([key]) => key !== leftId)
    .map(([, val]) => val);

  return props.question.rightColumn.filter(
    item => !usedByOthers.includes(item.id)
  );
};

// Al seleccionar un match
const onMatch = (leftId, rightId) => {
  const updated = { ...matches.value };
  if (rightId === '') {
    delete updated[leftId];
  } else {
    updated[leftId] = rightId;
  }
  emit('update:modelValue', updated);
};

// Cuántos pares están completados
const completedPairs = computed(() =>
  Object.values(matches.value).filter(Boolean).length
);
</script>