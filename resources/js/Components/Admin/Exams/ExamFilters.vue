<template>
  <div class="flex flex-col sm:flex-row gap-3">

    <!-- Buscador por texto -->
    <div class="flex-1 relative">
      <svg
        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
        fill="none" stroke="currentColor" viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
      </svg>
      <input
        type="text"
        :value="search"
        @input="onSearch"
        placeholder="Buscar por título..."
        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg
               focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
      />
    </div>

    <!-- Filtro por serie -->
    <select
      :value="seriesId"
      @change="$emit('update:seriesId', $event.target.value || null)"
      class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
             focus:outline-none focus:ring-2 focus:ring-indigo-400 min-w-[180px]"
    >
      <option value="">Todas las series</option>
      <option
        v-for="serie in seriesList"
        :key="serie.id"
        :value="serie.id"
      >
        {{ serie.title }}
      </option>
    </select>

    <!-- Filtro por estado -->
    <select
      :value="status"
      @change="$emit('update:status', $event.target.value || null)"
      class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
             focus:outline-none focus:ring-2 focus:ring-indigo-400 min-w-[150px]"
    >
      <option value="">Todos los estados</option>
      <option value="published">Publicados</option>
      <option value="draft">Borradores</option>
    </select>

    <!-- Botón limpiar filtros -->
    <button
      v-if="hayFiltrosActivos"
      @click="limpiar"
      class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 border border-gray-300
             rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap"
    >
      ✕ Limpiar
    </button>

  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  seriesId:   { default: null },
  status:     { default: null },
  search:     { default: '' },
  seriesList: { type: Array, default: () => [] },
});

const emit = defineEmits([
  'update:seriesId',
  'update:status',
  'update:search',
]);

// Debounce para el buscador — no hace request en cada tecla
let searchTimer = null;
const onSearch = (event) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    emit('update:search', event.target.value);
  }, 350);
};

const hayFiltrosActivos = computed(() =>
  props.seriesId || props.status || props.search
);

const limpiar = () => {
  emit('update:seriesId', null);
  emit('update:status', null);
  emit('update:search', '');
};
</script>