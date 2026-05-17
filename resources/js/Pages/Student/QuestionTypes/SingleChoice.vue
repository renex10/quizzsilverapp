<template>
  <div class="space-y-2">
    <label v-for="option in question.options" :key="option.id" class="flex items-center space-x-3">
      <input
        type="radio"
        :value="option.id"
        :checked="modelValue === option.id"
        @change="$emit('update:modelValue', option.id)"
        class="h-4 w-4 text-indigo-600"
      />
      <span>{{ option.text }}</span>
    </label>
  </div>
</template>
<script setup>
defineProps(['question', 'modelValue']);
defineEmits(['update:modelValue']);
</script>