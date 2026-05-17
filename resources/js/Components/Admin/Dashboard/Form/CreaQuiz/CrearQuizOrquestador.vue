<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import MultiStepModal from '@/Components/Common/MultiStepModal.vue';
import Paso1Tipo from './Paso1Tipo.vue';
import Paso2Serie from './Paso2Serie.vue';
import Paso3Metadatos from './Paso3Metadatos.vue';
import Paso4GuiaJson from './Paso4GuiaJson.vue';
import Paso5CargarJson from './Paso5CargarJson.vue';

const showModal    = ref(false);
const isSubmitting = ref(false);
const backendErrors  = ref({});
const backendMessage = ref('');

// El MultiStepModal que tenés en producción espera:
// - :current-step="currentStep"  → controlado externamente aquí
// - v-model:current-step         → emite update:currentStep para actualizar
// - :can-proceed="booleano"      → NO función, booleano reactivo
// - :is-submitting="booleano"    → para deshabilitar botones

const currentStep = ref(0);

const steps = [
    { title: 'Tipo' },
    { title: 'Serie' },
    { title: 'Metadatos' },
    { title: 'Guía JSON' },
    { title: 'Cargar JSON' },
];

const formData = ref({
    tipo:        '',
    seriesId:    null,
    nuevaSerie:  { title: '', description: '', domain: '' },
    titulo:      '',
    descripcion: '',
    version:     '',
    jsonSchema:  '',
});

const jsonValidado    = ref(false);
const seriesList      = ref([]);
const isLoadingSeries = ref(false);

// ---------------------------------------------------------------
// canProceed — BOOLEANO computed, reactivo al paso actual
// El modal usa :disabled="!canProceed" directamente en el template
// Como es computed, Vue re-evalúa el botón cuando currentStep
// o cualquier campo de formData cambia
// ---------------------------------------------------------------
const canProceed = computed(() => {
    switch (currentStep.value) {
        case 0:
            return formData.value.tipo !== '';

        case 1:
            return (
                (formData.value.seriesId !== null &&
                 formData.value.seriesId !== undefined) ||
                (formData.value.nuevaSerie.title.trim() !== '' &&
                 formData.value.nuevaSerie.domain.trim() !== '')
            );

        case 2:
            return (
                formData.value.titulo.trim() !== '' &&
                formData.value.version.trim() !== ''
            );

        case 3:
            return true;

        case 4:
            return (
                formData.value.jsonSchema !== '' &&
                jsonValidado.value === true
            );

        default:
            return true;
    }
});

// ---------------------------------------------------------------
// El modal emite update:currentStep — lo capturamos aquí
// Esto mantiene currentStep sincronizado para que canProceed
// computed sepa en qué paso está y evalúe correctamente
// ---------------------------------------------------------------
const onCurrentStepUpdate = (step) => {
    currentStep.value = step;
};

const onSeriesIdUpdate = (value) => {
    formData.value.seriesId = value;
};

const onJsonValidado = (estado) => {
    jsonValidado.value = estado;
    if (!estado) delete backendErrors.value.json_schema;
};

const onJsonSchemaUpdate = (value) => {
    formData.value.jsonSchema = value;
    jsonValidado.value = false;
};

const loadSeries = async () => {
    isLoadingSeries.value = true;
    try {
        const response   = await axios.get(route('admin.form.series'));
        seriesList.value = response.data.series;
    } catch (error) {
        console.error('Error al cargar series:', error);
        seriesList.value = [];
    } finally {
        isLoadingSeries.value = false;
    }
};

const openModal = async () => {
    resetForm();
    await loadSeries();
    showModal.value = true;
};

const resetForm = () => {
    formData.value = {
        tipo:        '',
        seriesId:    null,
        nuevaSerie:  { title: '', description: '', domain: '' },
        titulo:      '',
        descripcion: '',
        version:     '',
        jsonSchema:  '',
    };
    currentStep.value    = 0;
    jsonValidado.value   = false;
    backendErrors.value  = {};
    backendMessage.value = '';
    isSubmitting.value   = false;
};

const finish = async () => {
    if (isSubmitting.value) return;
    backendErrors.value  = {};
    backendMessage.value = '';
    isSubmitting.value   = true;

    router.post(
        route('admin.exams.store'),
        {
            type:        formData.value.tipo,
            series_id:   formData.value.seriesId || null,
            new_series:  formData.value.seriesId ? null : formData.value.nuevaSerie,
            title:       formData.value.titulo,
            description: formData.value.descripcion,
            version:     formData.value.version,
            json_schema: formData.value.jsonSchema,
            status:      'published',
        },
        {
            onSuccess: () => {
                showModal.value = false;
                resetForm();
                Swal.fire({
                    icon:              'success',
                    title:             '¡Evaluación creada!',
                    text:              'La evaluación fue guardada y está disponible en el catálogo.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#6366f1',
                    timer:             4000,
                    timerProgressBar:  true,
                });
            },
            onError: (errors) => {
                backendErrors.value = errors;
                if (errors.json_schema) {
                    backendMessage.value = 'El JSON contiene errores.';
                    currentStep.value = 4;
                } else if (errors.title || errors.version || errors.description) {
                    backendMessage.value = 'Hay errores en los metadatos.';
                    currentStep.value = 2;
                } else if (
                    errors['new_series.title'] ||
                    errors['new_series.domain'] ||
                    errors.series_id
                ) {
                    backendMessage.value = 'Hay errores en los datos de la serie.';
                    currentStep.value = 1;
                } else if (errors.type) {
                    backendMessage.value = 'Hay un problema con el tipo.';
                    currentStep.value = 0;
                } else {
                    backendMessage.value = errors.general?.[0] ?? 'Error inesperado.';
                }
                Swal.fire({
                    icon: 'error', title: 'Error al guardar',
                    text: backendMessage.value,
                    confirmButtonText: 'Revisar',
                    confirmButtonColor: '#ef4444',
                });
            },
            onFinish: () => { isSubmitting.value = false; },
        }
    );
};

defineExpose({ openModal });
</script>

<template>
    <!--
        MultiStepModal en producción espera:
        - v-model:current-step  → controla el paso externamente
        - :can-proceed          → booleano (computed reactivo)
        - :is-submitting        → booleano para deshabilitar botones
    -->
    <MultiStepModal
        v-model:show="showModal"
        v-model:current-step="currentStep"
        title="Crear nueva evaluación"
        :steps="steps"
        :can-proceed="canProceed"
        :is-submitting="isSubmitting"
        finish-text="Crear evaluación"
        @finish="finish"
        @close="resetForm"
        @update:current-step="onCurrentStepUpdate"
    >
        <!-- Mensaje de error del backend -->
        <template #header-error>
            <div
                v-if="backendMessage"
                class="mx-1 mb-4 px-4 py-2 bg-red-50 border border-red-200 rounded-lg
                       text-sm text-red-700 flex items-start gap-2"
            >
                <span class="mt-0.5 shrink-0">⚠️</span>
                <span>{{ backendMessage }}</span>
            </div>
        </template>

        <template #step-0>
            <Paso1Tipo v-model="formData.tipo" />
        </template>

        <template #step-1>
            <Paso2Serie
                :series-id="formData.seriesId"
                :nueva-serie="formData.nuevaSerie"
                :series-list="seriesList"
                :is-loading="isLoadingSeries"
                :errors="backendErrors"
                @update:series-id="onSeriesIdUpdate"
                @update:nueva-serie="formData.nuevaSerie = $event"
            />
        </template>

        <template #step-2>
            <Paso3Metadatos
                :title="formData.titulo"
                :description="formData.descripcion"
                :version="formData.version"
                :errors="backendErrors"
                @update:title="formData.titulo = $event"
                @update:description="formData.descripcion = $event"
                @update:version="formData.version = $event"
            />
        </template>

        <template #step-3>
            <Paso4GuiaJson :tipo="formData.tipo" />
        </template>

        <template #step-4>
            <Paso5CargarJson
                :json-schema="formData.jsonSchema"
                :tipo="formData.tipo"
                :backend-errors="backendErrors.json_schema"
                @update:json-schema="onJsonSchemaUpdate"
                @validado="onJsonValidado"
            />
        </template>
    </MultiStepModal>
</template>