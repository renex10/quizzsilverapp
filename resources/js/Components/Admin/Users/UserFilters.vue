<!-- resources/js/Components/Admin/Users/UserFilters.vue -->
<template>
  <div class="flex flex-col sm:flex-row gap-3">

    <!-- Búsqueda por nombre o email -->
    <div class="flex-1 relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
        fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
      </svg>
      <input
        type="text"
        :value="modelValue.search"
        @input="update('search', $event.target.value)"
        placeholder="Buscar por nombre o email..."
        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg
               focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
      />
    </div>

    <!-- Filtro por rol -->
    <select
      :value="modelValue.role"
      @change="update('role', $event.target.value)"
      class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
             focus:outline-none focus:ring-2 focus:ring-indigo-400 min-w-[160px]"
    >
      <option value="">Todos los roles</option>
      <option value="student">Estudiantes</option>
      <option value="admin">Administradores</option>
    </select>

    <!-- Limpiar -->
    <button
      v-if="hayFiltros"
      @click="limpiar"
      class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 border border-gray-300
             rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap"
    >
      ✕ Limpiar
    </button>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
  // { search, role }
});

const emit = defineEmits(['update:modelValue']);

const hayFiltros = computed(() =>
  props.modelValue.search || props.modelValue.role
);

const update = (field, value) => {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
};

const limpiar = () => {
  emit('update:modelValue', { search: '', role: '' });
};
</script>