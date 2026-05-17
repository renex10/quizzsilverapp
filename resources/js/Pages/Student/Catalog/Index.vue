<!-- resources/js/Pages/Student/Catalog/Index.vue -->
<template>
  <StudentLayout title="Catálogo de Evaluaciones">

    <template #header>
      <div>
        <h2 class="text-2xl font-bold text-gray-800">📚 Catálogo de Evaluaciones</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          Encontrá y respondé las evaluaciones disponibles
        </p>
      </div>
    </template>

    <div class="space-y-2">

      <!-- Filtros -->
      <CatalogFilters
        v-model="filters"
        :available-domains="availableDomains"
        :available-types="availableTypes"
        @update:model-value="onFilterChange"
      />

      <!-- Estado vacío — sin resultados -->
      <div
        v-if="seriesGroups.length === 0"
        class="text-center py-20 text-gray-400"
      >
        <div class="text-5xl mb-4">🔍</div>
        <p class="text-lg font-medium text-gray-500">
          No hay evaluaciones que coincidan con los filtros
        </p>
        <p class="text-sm mt-1">
          Probá cambiando los criterios de búsqueda
        </p>
        <button
          v-if="hayFiltrosActivos"
          @click="limpiarFiltros"
          class="mt-4 text-sm text-indigo-600 hover:text-indigo-800 underline"
        >
          Limpiar filtros
        </button>
      </div>

      <!-- Listado agrupado por serie -->
      <SeriesGroup
        v-for="group in seriesGroups"
        :key="group.series.id"
        :group="group"
      />

    </div>

  </StudentLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentLayout  from '@/Layouts/StudentLayout.vue';
import CatalogFilters from '@/Components/Student/Catalog/CatalogFilters.vue';
import SeriesGroup    from '@/Components/Student/Catalog/SeriesGroup.vue';

// ─────────────────────────────────────────────────────────────────────────────
// Props desde CatalogController@index
// ─────────────────────────────────────────────────────────────────────────────
const props = defineProps({
  seriesGroups: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({ search: '', domain: '', type: '', personal_status: '' }),
  },
  availableDomains: { type: Array, default: () => [] },
  availableTypes:   { type: Array, default: () => [] },
});

// ─────────────────────────────────────────────────────────────────────────────
// Estado local de filtros — copia reactiva de los props
// ─────────────────────────────────────────────────────────────────────────────
const filters = ref({
  search:          props.filters.search          ?? '',
  domain:          props.filters.domain          ?? '',
  type:            props.filters.type            ?? '',
  personal_status: props.filters.personal_status ?? '',
});

const hayFiltrosActivos = computed(() =>
  filters.value.search ||
  filters.value.domain ||
  filters.value.type ||
  filters.value.personal_status
);

// ─────────────────────────────────────────────────────────────────────────────
// Aplicar filtros — request al servidor con preserveState
// ─────────────────────────────────────────────────────────────────────────────
const applyFilters = () => {
  router.get(
    route('student.catalog.index'),
    {
      search:          filters.value.search          || undefined,
      domain:          filters.value.domain          || undefined,
      type:            filters.value.type            || undefined,
      personal_status: filters.value.personal_status || undefined,
    },
    {
      preserveState:  true,
      preserveScroll: true,
      replace:        true,
    }
  );
};

// Debounce para la búsqueda por texto
let debounceTimer = null;
const onFilterChange = (newFilters) => {
  filters.value = newFilters;
};

watch(() => filters.value.search, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(applyFilters, 400);
});

watch([
  () => filters.value.domain,
  () => filters.value.type,
  () => filters.value.personal_status,
], applyFilters);

const limpiarFiltros = () => {
  filters.value = { search: '', domain: '', type: '', personal_status: '' };
  applyFilters();
};
</script>