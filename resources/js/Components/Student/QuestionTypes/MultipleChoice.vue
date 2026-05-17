<!-- resources/js/Components/Student/QuestionTypes/MultipleChoice.vue -->
<template>
  <div class="space-y-3">

    <p class="text-xs text-gray-400 mb-1">
      Podés seleccionar una o más opciones correctas.
    </p>

    <label
      v-for="option in question.options"
      :key="option.id"
      class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer
             transition-all duration-200 select-none"
      :class="isSelected(option.id)
        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200'
        : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40'"
    >
      <input
        type="checkbox"
        :value="option.id"
        :checked="isSelected(option.id)"
        @change="toggle(option.id)"
        class="w-4 h-4 text-indigo-600 border-gray-300 rounded
               focus:ring-indigo-500 shrink-0"
      />
      <div class="flex items-center gap-2 flex-1">
        <span
          class="shrink-0 w-6 h-6 rounded border text-xs font-bold
                 flex items-center justify-center transition-colors"
          :class="isSelected(option.id)
            ? 'bg-indigo-600 border-indigo-600 text-white'
            : 'bg-white border-gray-300 text-gray-500'"
        >
          {{ option.id }}
        </span>
        <span class="text-sm text-gray-800">{{ option.text }}</span>
      </div>
    </label>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  question:   { type: Object, required: true },
  // modelValue es un array de IDs seleccionados: ["A", "C"]
  modelValue: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed(() => props.modelValue ?? []);

const isSelected = (id) => selected.value.includes(id);

const toggle = (id) => {
  const current = [...selected.value];
  const idx = current.indexOf(id);
  if (idx >= 0) {
    current.splice(idx, 1);
  } else {
    current.push(id);
  }
  emit('update:modelValue', current);
};
</script>