<script setup>
import { computed } from 'vue';
import BaseModal from './BaseModal.vue';
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/solid';

const props = defineProps({
    show: Boolean,
    title: String,
    message: String,
    type: {
        type: String,
        default: 'info', // success, error, warning, info
    },
    buttonText: {
        type: String,
        default: 'Aceptar',
    },
});

const emit = defineEmits(['update:show', 'close']);

const config = computed(() => {
    const configs = {
        success: {
            icon: CheckCircleIcon,
            iconColor: 'text-green-500',
            iconBg: 'bg-green-100',
            buttonClass: 'bg-green-500 hover:bg-green-600 focus:ring-green-400',
            defaultTitle: 'Éxito',
        },
        error: {
            icon: XCircleIcon,
            iconColor: 'text-red-500',
            iconBg: 'bg-red-100',
            buttonClass: 'bg-red-500 hover:bg-red-600 focus:ring-red-400',
            defaultTitle: 'Error',
        },
        warning: {
            icon: ExclamationTriangleIcon,
            iconColor: 'text-yellow-500',
            iconBg: 'bg-yellow-100',
            buttonClass: 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-400',
            defaultTitle: 'Advertencia',
        },
        info: {
            icon: InformationCircleIcon,
            iconColor: 'text-blue-500',
            iconBg: 'bg-blue-100',
            buttonClass: 'bg-blue-500 hover:bg-blue-600 focus:ring-blue-400',
            defaultTitle: 'Información',
        },
    };
    return configs[props.type] || configs.info;
});

const handleClose = () => {
    emit('update:show', false);
    emit('close');
};
</script>

<template>
    <BaseModal
        :show="show"
        size="sm"
        :show-header="false"
        @update:show="(val) => emit('update:show', val)"
    >
        <div class="text-center py-2">
            <div
                :class="config.iconBg"
                class="mx-auto flex items-center justify-center w-16 h-16 rounded-full mb-4"
            >
                <component :is="config.icon" :class="config.iconColor" class="w-9 h-9" />
            </div>

            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                {{ title || config.defaultTitle }}
            </h3>

            <p class="text-sm text-gray-600">
                <slot>{{ message }}</slot>
            </p>
        </div>

        <template #footer>
            <div class="flex justify-center">
                <button
                    type="button"
                    @click="handleClose"
                    :class="config.buttonClass"
                    class="px-6 py-2 text-sm font-medium text-white rounded-lg
                           focus:outline-none focus:ring-2 transition-colors min-w-[120px]"
                >
                    {{ buttonText }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>