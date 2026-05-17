<template>
  <div class="space-y-3">

    <!-- Textarea del JSON -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        JSON del examen
      </label>
      <textarea
        :value="jsonSchema"
        @input="onInput"
        rows="12"
        class="w-full font-mono text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors"
        :class="{
          'border border-green-400 bg-green-50': validado === true,
          'border border-red-400 bg-red-50':    validado === false || tieneErroresBackend,
          'border border-gray-300':              validado === null && !tieneErroresBackend,
        }"
        placeholder="Pega aquí el JSON generado por IA..."
      ></textarea>
    </div>

    <!-- Botón validar + indicador -->
    <div class="flex items-center justify-between">
      <button
        @click="validar"
        :disabled="!jsonSchema || validando"
        class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm
               hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed
               transition-colors flex items-center gap-2"
      >
        <span v-if="validando" class="inline-block w-3 h-3 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></span>
        {{ validando ? 'Validando...' : '🔍 Validar JSON' }}
      </button>

      <div class="text-sm font-medium">
        <span v-if="validado === true"  class="text-green-600">✅ JSON válido — podés continuar</span>
        <span v-if="validado === false" class="text-red-600">❌ JSON inválido</span>
        <span v-if="validado === null && jsonSchema" class="text-gray-400 text-xs">Pendiente de validación</span>
      </div>
    </div>

    <!-- Errores de validación local (del endpoint validate-json) -->
    <div
      v-if="erroresLocales.length"
      class="bg-red-50 border-l-4 border-red-500 rounded-r-lg p-3 text-sm text-red-700"
    >
      <p class="font-semibold mb-1">Errores en el JSON:</p>
      <ul class="list-disc pl-5 space-y-0.5">
        <li v-for="(err, i) in erroresLocales" :key="i">{{ err }}</li>
      </ul>
    </div>

    <!-- Errores del backend (del store final) -->
    <div
      v-if="tieneErroresBackend"
      class="bg-orange-50 border-l-4 border-orange-500 rounded-r-lg p-3 text-sm text-orange-700"
    >
      <p class="font-semibold mb-1">El servidor rechazó el JSON al guardar:</p>
      <ul class="list-disc pl-5 space-y-0.5">
        <li v-for="(err, i) in erroresBackendNormalizados" :key="i">{{ err }}</li>
      </ul>
      <p class="mt-2 text-xs text-orange-500">
        Corregí el JSON y volvé a validar antes de intentar guardar nuevamente.
      </p>
    </div>

    <!-- Mensaje de éxito -->
    <p v-if="validado === true" class="text-xs text-green-600 bg-green-50 border border-green-200 rounded px-3 py-2">
      El JSON cumple el contrato para el tipo <strong>{{ tipo }}</strong>.
      Hacé clic en "Crear evaluación" para guardar.
    </p>

    <!-- Instrucción cuando el textarea está vacío -->
    <p v-if="!jsonSchema" class="text-xs text-gray-400">
      Copiá la plantilla del paso anterior, generá el JSON con una IA y pegalo aquí. Luego validalo antes de continuar.
    </p>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    jsonSchema:    { type: String,  default: '' },
    tipo:          { type: String,  default: '' },
    backendErrors: { default: null },
});

const emit = defineEmits(['update:jsonSchema', 'validado']);

const validando      = ref(false);
const validado       = ref(null);
const erroresLocales = ref([]);

// Normalizar errores del backend (array, string, u objeto Laravel)
const erroresBackendNormalizados = computed(() => {
    if (!props.backendErrors) return [];
    if (Array.isArray(props.backendErrors))        return props.backendErrors;
    if (typeof props.backendErrors === 'string')   return [props.backendErrors];
    if (typeof props.backendErrors === 'object')   return Object.values(props.backendErrors).flat();
    return [];
});

const tieneErroresBackend = computed(() => erroresBackendNormalizados.value.length > 0);

// Si llegan errores del backend, marcar como inválido
watch(() => props.backendErrors, (val) => {
    if (val && (Array.isArray(val) ? val.length > 0 : true)) {
        validado.value = false;
        emit('validado', false);
    }
});

// Al editar el textarea, resetear validación
const onInput = (event) => {
    emit('update:jsonSchema', event.target.value);
    validado.value       = null;
    erroresLocales.value = [];
    emit('validado', false);
};

// Validar JSON contra el contrato del tipo
const validar = async () => {
    if (!props.jsonSchema || validando.value) return;

    validando.value      = true;
    validado.value       = null;
    erroresLocales.value = [];

    try {
        const response = await axios.post(route('admin.form.validate-json'), {
            json_schema: props.jsonSchema,
            type:        props.tipo,
        });

        if (response.data.valid) {
            validado.value       = true;
            erroresLocales.value = [];
            emit('validado', true);
        } else {
            validado.value       = false;
            erroresLocales.value = normalizarErrores(response.data.errors);
            emit('validado', false);
        }
    } catch (error) {
        validado.value = false;

        if (error.response?.data?.errors) {
            erroresLocales.value = normalizarErrores(error.response.data.errors);
        } else if (error.response?.status === 422) {
            erroresLocales.value = ['El JSON no es válido sintácticamente o falta el campo "type".'];
        } else {
            erroresLocales.value = ['Error de conexión al validar. Intentá nuevamente.'];
        }

        emit('validado', false);
    } finally {
        validando.value = false;
    }
};

const normalizarErrores = (errors) => {
    if (!errors)                              return ['JSON inválido según el contrato.'];
    if (Array.isArray(errors))                return errors.map((e) => typeof e === 'object' ? (e.message || JSON.stringify(e)) : e);
    if (typeof errors === 'object')           return Object.values(errors).flat();
    if (typeof errors === 'string')           return [errors];
    return ['Error desconocido en la validación.'];
};

// Exponer reset para que el orquestador limpie el estado al cerrar el modal
const resetEstado = () => {
    validado.value       = null;
    erroresLocales.value = [];
    validando.value      = false;
};

defineExpose({ resetEstado });
</script>