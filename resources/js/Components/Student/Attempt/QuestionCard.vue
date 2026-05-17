<!-- resources\js\Components\Student\Attempt\QuestionCard.vue -->
<template>
  <div class="mb-6 bg-white shadow rounded-lg p-5">
    <div class="flex justify-between items-start mb-3">
      <h3 class="text-lg font-medium">
        {{ number }}. {{ question.question }}
        <span v-if="question.critical" class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">
          Crítica
        </span>
      </h3>
      <span class="text-sm text-gray-500">{{ difficultyLabel }}</span>
    </div>

    <component
      :is="questionComponent"
      :question="question"
      :name="question.id"
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SingleChoice from '@/Components/Student/QuestionTypes/SingleChoice.vue';
import MultipleChoice from '@/Components/Student/QuestionTypes/MultipleChoice.vue';
import TrueFalse from '@/Components/Student/QuestionTypes/TrueFalse.vue';
import Ordering from '@/Components/Student/QuestionTypes/Ordering.vue';
import Matching from '@/Components/Student/QuestionTypes/Matching.vue';

const props = defineProps({
  question: Object,
  number: Number,
  modelValue: null, // respuesta actual
});

defineEmits(['update:modelValue']);

const difficultyLabel = computed(() => {
  const labels = { baja: 'Fácil', media: 'Media', alta: 'Difícil' };
  return labels[props.question.difficulty] || props.question.difficulty;
});

const questionComponent = computed(() => {
  const components = {
    single_choice: SingleChoice,
    multiple_choice: MultipleChoice,
    true_false: TrueFalse,
    ordering: Ordering,
    matching: Matching,
  };
  return components[props.question.type] || SingleChoice;
});
</script>