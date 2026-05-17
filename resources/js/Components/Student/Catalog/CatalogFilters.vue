<!-- resources/js/Components/Student/Catalog/CatalogFilters.vue -->
<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

    <!-- Búsqueda por texto -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
          fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
        </svg>
        <input
          type="text"
          :value="modelValue.search"
          @input="update('search', $event.target.value)"
          placeholder="Título, descripción o serie..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg
                 focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
        />
      </div>
    </div>

    <!-- Filtro por dominio -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Dominio</label>
      <select
        :value="modelValue.domain"
        @change="update('domain', $event.target.value)"
        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
               focus:outline-none focus:ring-2 focus:ring-indigo-400"
      >
        <option value="">Todos los dominios</option>
        <option v-for="domain in availableDomains" :key="domain" :value="domain">
          {{ domain }}
        </option>
      </select>
    </div>

    <!-- Filtro por tipo -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
      <select
        :value="modelValue.type"
        @change="update('type', $event.target.value)"
        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
               focus:outline-none focus:ring-2 focus:ring-indigo-400"
      >
        <option value="">Todos los tipos</option>
        <option v-for="type in availableTypes" :key="type" :value="type">
          {{ typeLabel(type) }}
        </option>
      </select>
    </div>

    <!-- Filtro por estado personal -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Mi progreso</label>
      <select
        :value="modelValue.personal_status"
        @change="update('personal_status', $event.target.value)"
        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
               focus:outline-none focus:ring-2 focus:ring-indigo-400"
      >
        <option value="">Todos</option>
        <option value="new">Nuevas</option>
        <option value="in_progress">En progreso</option>
        <option value="passed">Aprobadas</option>
        <option value="failed">Reprobadas</option>
        <option value="abandoned">Con intento abandonado</option>
      </select>
    </div>

    <!-- Botón limpiar filtros (visible solo si hay filtros activos) -->
    <div v-if="hayFiltrosActivos" class="md:col-span-4 flex justify-end">
      <button
        @click="limpiar"
        class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 border
               border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
      >
        ✕ Limpiar filtros
      </button>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
    // { search, domain, type, personal_status }
  },
  availableDomains: { type: Array, default: () => [] },
  availableTypes:   { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const typeLabel = (type) => ({
  single_choice:   'Opción única',
  multiple_choice: 'Opción múltiple',
  true_false:      'Verdadero / Falso',
  ordering:        'Ordenamiento',
  matching:        'Relacionar columnas',
}[type] ?? type);

const update = (field, value) => {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
};

const hayFiltrosActivos = computed(() =>
  props.modelValue.search ||
  props.modelValue.domain ||
  props.modelValue.type ||
  props.modelValue.personal_status
);

const limpiar = () => {
  emit('update:modelValue', { search: '', domain: '', type: '', personal_status: '' });
};
</script>