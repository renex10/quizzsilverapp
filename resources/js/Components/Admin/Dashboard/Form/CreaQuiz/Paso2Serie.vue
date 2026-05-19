<!-- resources\js\Components\Admin\Dashboard\Form\CreaQuiz\Paso2Serie.vue -->
<template>
  <div class="space-y-4">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Serie existente
      </label>

      <div v-if="isLoading" class="mt-1 text-gray-500 text-sm animate-pulse">
        Cargando series...
      </div>

      <select
        v-else
        v-model="seriesIdLocal"
        class="mt-1 w-full border rounded-lg px-3 py-2 focus:outline-none
               focus:ring-2 focus:ring-indigo-400 bg-white cursor-pointer"
        :class="errors?.series_id ? 'border-red-400 bg-red-50' : 'border-gray-300'"
      >
        <option value="">-- Selecciona una serie existente --</option>
        <option
          v-for="serie in seriesList"
          :key="serie.id"
          :value="String(serie.id)"
        >
          {{ serie.title }} ({{ serie.domain }})
        </option>
      </select>

      <p v-if="errors?.series_id" class="mt-1 text-xs text-red-600">
        {{ errors.series_id }}
      </p>

      <p v-if="!isLoading && seriesList.length === 0" class="text-sm text-gray-500 mt-1">
        No hay series creadas aún.
      </p>
    </div>

    <!-- Separador -->
    <div class="relative">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-200"></div>
      </div>
      <div class="relative flex justify-center text-xs">
        <span class="bg-white px-3 text-gray-400 font-medium">O CREA UNA NUEVA SERIE</span>
      </div>
    </div>

    <!-- DEBUG VISUAL — eliminar después de resolver -->
    <div class="p-2 bg-yellow-50 border border-yellow-300 rounded text-xs font-mono space-y-1">
      <p>seriesId (prop recibida): <strong>{{ seriesId }}</strong> (tipo: {{ typeof seriesId }})</p>
      <p>seriesIdLocal (computed): <strong>{{ seriesIdLocal }}</strong></p>
      <p>haySerieSeleccionada: <strong>{{ haySerieSeleccionada }}</strong></p>
      <p>canProceed paso 1 debería ser: <strong>{{ haySerieSeleccionada ? 'TRUE ✅' : 'FALSE ❌' }}</strong></p>
    </div>

    <!-- Formulario nueva serie -->
    <div
      class="space-y-3 p-4 rounded-lg border transition-all duration-200"
      :class="haySerieSeleccionada ? 'bg-gray-50 border-gray-200' : 'bg-white border-indigo-100'"
    >

      <div v-if="haySerieSeleccionada"
        class="flex items-start gap-2 text-xs text-indigo-700 bg-indigo-50
               border border-indigo-200 rounded-lg px-3 py-2"
      >
        <span class="shrink-0 mt-0.5">ℹ️</span>
        <span>
          Seleccionaste <strong>{{ labelSerieSeleccionada }}</strong>.
          Para crear una nueva, elegí el placeholder arriba.
        </span>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
          Título <span class="text-red-500">*</span>
        </label>
        <input
          type="text"
          placeholder="Ej. Licencia de Conducir Clase B"
          :value="nuevaSerie.title"
          @input="emitNuevaSerie('title', $event.target.value)"
          :disabled="haySerieSeleccionada"
          class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                 focus:ring-2 focus:ring-indigo-400 transition-colors
                 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
          :class="errors?.['new_series.title'] ? 'border-red-400 bg-red-50' : 'border-gray-300'"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
          Descripción <span class="text-gray-400 font-normal">(opcional)</span>
        </label>
        <input
          type="text"
          placeholder="Descripción breve de la serie"
          :value="nuevaSerie.description"
          @input="emitNuevaSerie('description', $event.target.value)"
          :disabled="haySerieSeleccionada"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors
                 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
          Dominio temático <span class="text-red-500">*</span>
        </label>
        <input
          type="text"
          placeholder="Ej. Seguridad Vial, Matemáticas, Salud"
          :value="nuevaSerie.domain"
          @input="emitNuevaSerie('domain', $event.target.value)"
          :disabled="haySerieSeleccionada"
          class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                 focus:ring-2 focus:ring-indigo-400 transition-colors
                 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
          :class="errors?.['new_series.domain'] ? 'border-red-400 bg-red-50' : 'border-gray-300'"
        />
        <p v-if="!haySerieSeleccionada" class="mt-1 text-xs text-gray-400">
          Categoría amplia para agrupar series relacionadas
        </p>
      </div>

    </div>

  </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';

const props = defineProps({
    seriesId:   { default: null },
    nuevaSerie: { type: Object,  default: () => ({ title: '', description: '', domain: '' }) },
    seriesList: { type: Array,   required: true },
    isLoading:  { type: Boolean, default: false },
    errors:     { type: Object,  default: () => ({}) },
});

const emit = defineEmits(['update:seriesId', 'update:nuevaSerie']);

// LOG A: confirmar el valor inicial al montarse
onMounted(() => {
    console.log('[Paso2Serie] montado — seriesId prop inicial:', props.seriesId, typeof props.seriesId);
    console.log('[Paso2Serie] seriesList recibida:', props.seriesList.length, 'items');
});

// LOG B: detectar cada cambio en seriesId prop
watch(() => props.seriesId, (nuevo, anterior) => {
    console.log('[Paso2Serie] seriesId prop cambió:', anterior, '→', nuevo, typeof nuevo);
    console.log('[Paso2Serie] haySerieSeleccionada ahora es:', nuevo !== null && nuevo !== undefined);
});

const seriesIdLocal = computed({
    get() {
        const val = props.seriesId != null ? String(props.seriesId) : '';
        console.log('[Paso2Serie] seriesIdLocal.get() →', val);
        return val;
    },
    set(value) {
        console.log('[Paso2Serie] seriesIdLocal.set() recibió:', value, typeof value);
        if (value === '' || value === null || value === undefined) {
            console.log('[Paso2Serie] emitiendo update:seriesId → null');
            emit('update:seriesId', null);
        } else {
            const parsed = parseInt(value, 10);
            const resultado = (!isNaN(parsed) && parsed > 0) ? parsed : null;
            console.log('[Paso2Serie] emitiendo update:seriesId →', resultado, typeof resultado);
            emit('update:seriesId', resultado);
        }
    },
});

const haySerieSeleccionada = computed(() => {
    const resultado = props.seriesId !== null && props.seriesId !== undefined;
    console.log('[Paso2Serie] haySerieSeleccionada computed:', props.seriesId, '→', resultado);
    return resultado;
});

const labelSerieSeleccionada = computed(() => {
    if (!haySerieSeleccionada.value) return '';
    const found = props.seriesList.find((s) => String(s.id) === String(props.seriesId));
    return found ? found.title : `Serie #${props.seriesId}`;
});

const emitNuevaSerie = (field, value) => {
    emit('update:nuevaSerie', { ...props.nuevaSerie, [field]: value });
};
</script>