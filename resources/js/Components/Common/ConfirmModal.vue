<script setup>
import { computed } from 'vue';
import BaseModal from './BaseModal.vue';
import {
    ExclamationTriangleIcon,
    QuestionMarkCircleIcon,
    TrashIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: '¿Estás seguro?',
    },
    message: {
        type: String,
        default: 'Esta acción no se puede deshacer.',
    },
    confirmText: {
        type: String,
        default: 'Confirmar',
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
    variant: {
        type: String,
        default: 'warning', // warning, danger, info, success
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:show', 'confirm', 'cancel']);

const iconComponent = computed(() => {
    const icons = {
        warning: ExclamationTriangleIcon,
        danger: TrashIcon,
        info: QuestionMarkCircleIcon,
        success: CheckCircleIcon,
    };
    return icons[props.variant] || ExclamationTriangleIcon;
});

const iconColorClass = computed(() => {
    const colors = {
        warning: 'text-yellow-500 bg-yellow-100',
        danger: 'text-red-500 bg-red-100',
        info: 'text-blue-500 bg-blue-100',
        success: 'text-green-500 bg-green-100',
    };
    return colors[props.variant];
});

const confirmButtonClass = computed(() => {
    const classes = {
        warning: 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-400',
        danger: 'bg-red-500 hover:bg-red-600 focus:ring-red-400',
        info: 'bg-blue-500 hover:bg-blue-600 focus:ring-blue-400',
        success: 'bg-green-500 hover:bg-green-600 focus:ring-green-400',
    };
    return classes[props.variant];
});

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
    emit('cancel');
    emit('update:show', false);
};
</script>

<template>
    <BaseModal
        :show="show"
        :title="''"
        size="sm"
        :show-header="false"
        @update:show="(val) => emit('update:show', val)"
    >
        <div class="text-center py-2">
            <!-- Icono -->
            <div
                :class="iconColorClass"
                class="mx-auto flex items-center justify-center w-14 h-14 rounded-full mb-4"
            >
                <component :is="iconComponent" class="w-7 h-7" />
            </div>

            <!-- Título -->
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                {{ title }}
            </h3>

            <!-- Mensaje -->
            <p class="text-sm text-gray-600">
                <slot>{{ message }}</slot>
            </p>
        </div>

        <template #footer>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button
                    type="button"
                    @click="handleCancel"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white
                           border border-gray-300 rounded-lg hover:bg-gray-50
                           focus:outline-none focus:ring-2 focus:ring-gray-300
                           transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    :disabled="loading"
                    :class="confirmButtonClass"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg
                           focus:outline-none focus:ring-2 transition-colors
                           disabled:opacity-50 disabled:cursor-not-allowed
                           flex items-center justify-center gap-2 min-w-[100px]"
                >
                    <svg
                        v-if="loading"
                        class="animate-spin h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    {{ loading ? 'Procesando...' : confirmText }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>
