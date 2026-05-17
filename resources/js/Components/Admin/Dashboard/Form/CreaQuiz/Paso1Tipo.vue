<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de evaluación</label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div
        v-for="tipo in tipos"
        :key="tipo.valor"
        @click="seleccionar(tipo.valor)"
        class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
        :class="{ 'border-indigo-500 bg-indigo-50': modelValue === tipo.valor }"
      >
        <input type="radio" :checked="modelValue === tipo.valor" class="mr-2" readonly />
        <div>
          <div class="font-medium capitalize">{{ tipo.etiqueta }}</div>
          <div class="text-xs text-gray-500">{{ tipo.descripcion }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: String,
});
const emit = defineEmits(['update:modelValue']);

const tipos = [
  { valor: 'single_choice',   etiqueta: 'Opción única',        descripcion: 'Una respuesta correcta entre 2-6 opciones' },
  { valor: 'multiple_choice', etiqueta: 'Opción múltiple',     descripcion: 'Una o más respuestas correctas, puntaje parcial opcional' },
  { valor: 'true_false',      etiqueta: 'Verdadero/Falso',     descripcion: 'Afirmación verdadera o falsa' },
  { valor: 'ordering',        etiqueta: 'Ordenamiento',        descripcion: 'Ordenar elementos secuencialmente' },
  { valor: 'matching',        etiqueta: 'Relacionar columnas', descripcion: 'Emparejar elementos de dos columnas' },
];

const seleccionar = (valor) => {
  emit('update:modelValue', valor);
};
</script>