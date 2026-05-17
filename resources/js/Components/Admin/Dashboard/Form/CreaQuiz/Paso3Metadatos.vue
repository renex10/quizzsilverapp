<template>
  <div class="space-y-3">
    <div>
      <label class="block text-sm font-medium text-gray-700">Título del examen</label>
      <input
        type="text"
        :value="title"
        @input="$emit('update:title', $event.target.value)"
        class="w-full border rounded-lg px-3 py-2"
        placeholder="Ej. Licencia Clase B - Versión 2024"
      />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Descripción</label>
      <textarea
        :value="description"
        @input="$emit('update:description', $event.target.value)"
        rows="2"
        class="w-full border rounded-lg px-3 py-2"
        placeholder="Descripción opcional"
      ></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Versión</label>
      <input
        type="text"
        :value="version"
        @input="$emit('update:version', $event.target.value)"
        class="w-full border rounded-lg px-3 py-2"
        placeholder="Ej. 2024, 1.0, Álgebra I"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  description: String,
  version: String
});
defineEmits(['update:title', 'update:description', 'update:version']);
</script>