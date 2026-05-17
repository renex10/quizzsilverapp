<!-- resources/js/Components/Student/QuestionTypes/Ordering.vue -->
<template>
  <div class="space-y-2">

    <p class="text-xs text-gray-400 mb-2">
      Arrastrá los elementos para ordenarlos correctamente.
    </p>

    <div
      class="space-y-2"
      @dragover.prevent
      @drop="onDrop"
    >
      <div
        v-for="(item, index) in orderedItems"
        :key="item.id"
        draggable="true"
        @dragstart="onDragStart(index)"
        @dragover.prevent="onDragOver(index)"
        @dragend="onDragEnd"
        class="flex items-center gap-3 p-3 rounded-lg border bg-white
               cursor-grab active:cursor-grabbing select-none
               transition-all duration-200"
        :class="{
          'border-indigo-400 bg-indigo-50 shadow-md scale-[1.02]': dragIndex === index,
          'border-gray-200 hover:border-indigo-200':               dragIndex !== index,
          'opacity-50': dragIndex !== null && dragIndex !== index && overIndex === index,
        }"
      >
        <!-- Handle de arrastre -->
        <div class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 6a2 2 0 100-4 2 2 0 000 4zM8 14a2 2 0 100-4 2 2 0 000 4zM8 22a2 2 0 100-4 2 2 0 000 4zM16 6a2 2 0 100-4 2 2 0 000 4zM16 14a2 2 0 100-4 2 2 0 000 4zM16 22a2 2 0 100-4 2 2 0 000 4z"/>
          </svg>
        </div>

        <!-- Número de posición -->
        <span
          class="shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-700
                 text-xs font-bold flex items-center justify-center"
        >
          {{ index + 1 }}
        </span>

        <!-- Texto del elemento -->
        <span class="text-sm text-gray-800 flex-1">{{ item.text }}</span>

        <!-- ID del elemento -->
        <span class="text-xs text-gray-400 font-mono shrink-0">{{ item.id }}</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  question:   { type: Object, required: true },
  // modelValue es un array de IDs en el orden actual: ["C", "A", "D", "B"]
  modelValue: { type: Array, default: null },
});

const emit = defineEmits(['update:modelValue']);

// Estado interno del orden — inicializar desde modelValue o desde las opciones originales
const orderedItems = ref(
  props.modelValue
    ? props.question.options.slice().sort((a, b) => {
        const ai = props.modelValue.indexOf(a.id);
        const bi = props.modelValue.indexOf(b.id);
        return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
      })
    : [...props.question.options]
);

// Sincronizar si llega un modelValue externo (ej. retomar intento)
watch(() => props.modelValue, (val) => {
  if (!val) return;
  orderedItems.value = props.question.options.slice().sort((a, b) => {
    const ai = val.indexOf(a.id);
    const bi = val.indexOf(b.id);
    return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
  });
}, { immediate: false });

// Estado del drag
const dragIndex = ref(null);
const overIndex = ref(null);

const onDragStart = (index) => {
  dragIndex.value = index;
};

const onDragOver = (index) => {
  overIndex.value = index;
};

const onDrop = () => {
  if (dragIndex.value === null || overIndex.value === null) return;
  if (dragIndex.value === overIndex.value) return;

  const items = [...orderedItems.value];
  const [moved] = items.splice(dragIndex.value, 1);
  items.splice(overIndex.value, 0, moved);
  orderedItems.value = items;

  // Emitir el nuevo orden como array de IDs
  emit('update:modelValue', items.map(i => i.id));

  dragIndex.value = null;
  overIndex.value = null;
};

const onDragEnd = () => {
  dragIndex.value = null;
  overIndex.value = null;
};
</script>