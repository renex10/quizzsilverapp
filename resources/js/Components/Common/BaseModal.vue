<script setup>
import { watch, onMounted, onUnmounted, computed } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(val),
    },
    // Cerrar al hacer click fuera
    closeOnBackdrop: {
        type: Boolean,
        default: true,
    },
    // Cerrar con tecla ESC
    closeOnEscape: {
        type: Boolean,
        default: true,
    },
    // Mostrar el botón X
    showCloseButton: {
        type: Boolean,
        default: true,
    },
    // Mostrar el header completo
    showHeader: {
        type: Boolean,
        default: true,
    },
    // Padding personalizado del contenido
    contentPadding: {
        type: String,
        default: 'p-6',
    },
    // Permitir scroll interno
    scrollable: {
        type: Boolean,
        default: true,
    },
    // Variante de color del header (para alertas, etc)
    variant: {
        type: String,
        default: 'default',
        validator: (val) => ['default', 'success', 'warning', 'danger', 'info', 'admin'].includes(val),
    },
    // Persistente: no se cierra con backdrop ni ESC (útil para formularios)
    persistent: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:show', 'close', 'opened', 'closed']);

// Mapa de tamaños
const sizeClasses = computed(() => {
    const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        '2xl': 'max-w-6xl',
        full: 'max-w-[95vw] h-[95vh]',
    };
    return sizes[props.size];
});

// Clases de variante para el header
const variantClasses = computed(() => {
    const variants = {
        default: 'bg-gray-50 border-gray-200',
        success: 'bg-green-50 border-green-200',
        warning: 'bg-yellow-50 border-yellow-200',
        danger: 'bg-red-50 border-red-200',
        info: 'bg-blue-50 border-blue-200',
        admin: 'bg-admin-gradient text-white border-transparent',
    };
    return variants[props.variant];
});

const titleColorClass = computed(() => {
    const colors = {
        default: 'text-gray-900',
        success: 'text-green-800',
        warning: 'text-yellow-800',
        danger: 'text-red-800',
        info: 'text-blue-800',
        admin: 'text-white',
    };
    return colors[props.variant];
});

const closeButtonColorClass = computed(() => {
    return props.variant === 'admin'
        ? 'text-white/80 hover:text-white hover:bg-white/10'
        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200';
});

// Cerrar el modal
const close = () => {
    if (props.persistent) return;
    emit('update:show', false);
    emit('close');
};

// Forzar cierre (ignora persistent) — útil para botones internos
const forceClose = () => {
    emit('update:show', false);
    emit('close');
};

// Cerrar al hacer click en el backdrop
const handleBackdropClick = () => {
    if (props.closeOnBackdrop && !props.persistent) {
        close();
    }
};

// Manejar tecla ESC
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show && props.closeOnEscape && !props.persistent) {
        close();
    }
};

// Bloquear scroll del body cuando el modal está abierto
watch(
    () => props.show,
    (newVal) => {
        if (typeof document === 'undefined') return;

        if (newVal) {
            document.body.style.overflow = 'hidden';
            emit('opened');
        } else {
            document.body.style.overflow = '';
            emit('closed');
        }
    }
);

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});

// Exponer métodos para uso desde componentes padre con refs
defineExpose({ close, forceClose });
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop con transición -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                @click.self="handleBackdropClick"
                class="fixed inset-0 z-[1000] flex items-center justify-center
                       bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <!-- Contenedor del modal con transición -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                    appear
                >
                    <div
                        v-if="show"
                        :class="[sizeClasses]"
                        class="relative w-full bg-white rounded-xl shadow-2xl
                               flex flex-col max-h-[90vh] my-auto"
                        @click.stop
                    >
                        <!-- Header -->
                        <div
                            v-if="showHeader && (title || $slots.header)"
                            :class="variantClasses"
                            class="flex items-center justify-between px-6 py-4
                                   border-b rounded-t-xl flex-shrink-0"
                        >
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <!-- Slot para icono del header (ej: en alertas) -->
                                <slot name="header-icon" />

                                <!-- Título o slot personalizado -->
                                <slot name="header">
                                    <h2
                                        :class="titleColorClass"
                                        class="text-lg font-semibold truncate"
                                    >
                                        {{ title }}
                                    </h2>
                                </slot>
                            </div>

                            <!-- Botón cerrar -->
                            <button
                                v-if="showCloseButton && !persistent"
                                @click="close"
                                :class="closeButtonColorClass"
                                class="flex items-center justify-center w-9 h-9 rounded-full
                                       transition-all duration-200 hover:rotate-90 flex-shrink-0 ml-2"
                                aria-label="Cerrar"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Contenido -->
                        <div
                            :class="[
                                contentPadding,
                                scrollable ? 'overflow-y-auto' : '',
                            ]"
                            class="flex-1"
                        >
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div
                            v-if="$slots.footer"
                            class="px-6 py-4 border-t border-gray-200 bg-gray-50
                                   rounded-b-xl flex-shrink-0"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>