<script setup>
import { ref, computed, watch } from 'vue';
import BaseModal from './BaseModal.vue';
import ConfirmModal from './ConfirmModal.vue';
import { CheckIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

/**
 * CORRECCIONES APLICADAS:
 * 1. currentStep ahora es controlado externamente por el orquestador
 *    vía v-model:currentStep — el modal no lo maneja internamente.
 * 2. canProceed recibe el valor booleano calculado por el orquestador
 *    en lugar de ser una función — más predecible y reactivo.
 * 3. Se agrega slot #header-error para mostrar errores del backend.
 * 4. isSubmitting prop para deshabilitar botones durante el envío.
 */
const props = defineProps({
    show: Boolean,
    title: String,
    steps: {
        type: Array,
        required: true,
    },
    // CORRECCIÓN 1: currentStep controlado por el padre
    currentStep: {
        type: Number,
        default: 0,
    },
    size: {
        type: String,
        default: 'lg',
    },
    finishText: {
        type: String,
        default: 'Crear evaluación',
    },
    // CORRECCIÓN 2: booleano simple en lugar de función
    // El orquestador calcula si se puede avanzar y lo pasa aquí
    canProceed: {
        type: Boolean,
        default: true,
    },
    // CORRECCIÓN 4: estado de envío para deshabilitar botones
    isSubmitting: {
        type: Boolean,
        default: false,
    },
    confirmOnClose: {
        type: Boolean,
        default: true,
    },
    confirmCloseMessage: {
        type: String,
        default: 'Si cierras ahora, perderás todo el progreso. ¿Estás seguro?',
    },
});

const emit = defineEmits([
    'update:show',
    'update:currentStep',
    'finish',
    'next',
    'prev',
    'step-change',
    'close',
]);

const showCloseConfirm = ref(false);

const totalSteps      = computed(() => props.steps.length);
const isFirstStep     = computed(() => props.currentStep === 0);
const isLastStep      = computed(() => props.currentStep === totalSteps.value - 1);
const progressPercent = computed(
    () => ((props.currentStep + 1) / totalSteps.value) * 100
);
const hasProgress = computed(() => props.currentStep > 0);

// CORRECCIÓN 1: ya no resetea internamente — el orquestador controla el paso
// El watch solo actúa si el padre no maneja el reset (compatibilidad)
watch(
    () => props.show,
    (val) => {
        if (!val) {
            showCloseConfirm.value = false;
        }
    }
);

const next = () => {
    if (!props.canProceed || props.isSubmitting) return;

    if (isLastStep.value) {
        emit('finish');
    } else {
        const newStep = props.currentStep + 1;
        emit('update:currentStep', newStep);
        emit('next');
        emit('step-change', newStep);
    }
};

const prev = () => {
    if (isFirstStep.value || props.isSubmitting) return;
    const newStep = props.currentStep - 1;
    emit('update:currentStep', newStep);
    emit('prev');
    emit('step-change', newStep);
};

// Navegar a paso anterior (clic en círculo)
const goToStep = (index) => {
    if (index < props.currentStep && !props.isSubmitting) {
        emit('update:currentStep', index);
        emit('step-change', index);
    }
};

const handleClose = () => {
    if (props.confirmOnClose && hasProgress.value) {
        showCloseConfirm.value = true;
    } else {
        closeModal();
    }
};

const closeModal = () => {
    showCloseConfirm.value = false;
    emit('update:show', false);
    emit('close');
};

const cancelClose = () => {
    showCloseConfirm.value = false;
};
</script>

<template>
    <BaseModal
        :show="show"
        :title="title"
        :size="size"
        :persistent="false"
        :close-on-backdrop="!isSubmitting"
        :close-on-escape="!isSubmitting"
        :show-close-button="!isSubmitting"
        @update:show="(val) => { if (!val) handleClose(); }"
    >
        <!-- CORRECCIÓN 3: slot para errores del backend encima del stepper -->
        <slot name="header-error" />

        <!-- Stepper visual -->
        <div class="mb-6">
            <!-- Barra de progreso -->
            <div class="relative mb-4">
                <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 -translate-y-1/2 rounded-full"></div>
                <div
                    :style="{ width: `${progressPercent}%` }"
                    class="absolute top-1/2 left-0 h-1 bg-admin-gradient -translate-y-1/2 rounded-full transition-all duration-300"
                ></div>

                <!-- Círculos de pasos -->
                <div class="relative flex justify-between">
                    <button
                        v-for="(step, index) in steps"
                        :key="index"
                        type="button"
                        @click="goToStep(index)"
                        :disabled="index >= currentStep || isSubmitting"
                        :class="[
                            index < currentStep
                                ? 'bg-green-500 text-white cursor-pointer hover:scale-110'
                                : index === currentStep
                                ? 'bg-admin-gradient text-white ring-4 ring-purple-200'
                                : 'bg-white border-2 border-gray-300 text-gray-400',
                        ]"
                        class="flex items-center justify-center w-10 h-10 rounded-full
                               text-sm font-semibold transition-all duration-300 z-10"
                    >
                        <CheckIcon v-if="index < currentStep" class="w-5 h-5" />
                        <span v-else>{{ index + 1 }}</span>
                    </button>
                </div>
            </div>

            <!-- Etiquetas de pasos -->
            <div class="flex justify-between text-xs">
                <div
                    v-for="(step, index) in steps"
                    :key="index"
                    class="flex-1 text-center px-1"
                    :class="index === currentStep ? 'text-gray-900 font-semibold' : 'text-gray-500'"
                >
                    <div class="truncate">{{ step.title }}</div>
                    <div v-if="step.description" class="text-[10px] text-gray-400 truncate mt-0.5">
                        {{ step.description }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido del paso actual con transición -->
        <Transition
            mode="out-in"
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-x-4"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 -translate-x-4"
        >
            <div :key="currentStep" class="min-h-[200px]">
                <slot
                    :name="`step-${currentStep}`"
                    :current-step="currentStep"
                    :total-steps="totalSteps"
                />
            </div>
        </Transition>

        <template #footer>
            <div class="flex justify-between items-center">
                <button
                    type="button"
                    @click="prev"
                    :disabled="isFirstStep || isSubmitting"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white
                           border border-gray-300 rounded-lg hover:bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-gray-300
                           transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                           flex items-center gap-1"
                >
                    <ChevronLeftIcon class="w-4 h-4" />
                    Anterior
                </button>

                <span class="text-sm text-gray-500">
                    Paso {{ currentStep + 1 }} de {{ totalSteps }}
                </span>

                <button
                    type="button"
                    @click="next"
                    :disabled="!canProceed || isSubmitting"
                    class="px-4 py-2 text-sm font-medium text-white bg-admin-gradient
                           rounded-lg hover:opacity-90 focus:outline-none focus:ring-2
                           focus:ring-purple-400 transition-all
                           disabled:opacity-50 disabled:cursor-not-allowed
                           flex items-center gap-1 min-w-[140px] justify-center"
                >
                    <!-- Spinner durante envío -->
                    <svg
                        v-if="isSubmitting"
                        class="animate-spin h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <template v-else>
                        {{ isLastStep ? finishText : 'Siguiente' }}
                        <ChevronRightIcon v-if="!isLastStep" class="w-4 h-4" />
                    </template>
                </button>
            </div>
        </template>
    </BaseModal>

    <!-- Modal de confirmación de cierre -->
    <ConfirmModal
        v-model:show="showCloseConfirm"
        title="¿Cerrar formulario?"
        :message="confirmCloseMessage"
        confirm-text="Sí, cerrar"
        cancel-text="Continuar editando"
        variant="warning"
        @confirm="closeModal"
        @cancel="cancelClose"
    />
</template>