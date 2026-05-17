<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    BookOpenIcon,
    DocumentTextIcon,
    UsersIcon,
    ChartBarIcon,
    ChevronLeftIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: true,
    },
    isMobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle', 'close']);

const page = usePage();

// Items de navegación con nombres de ruta COMPLETOS (incluyendo prefijo admin.)
const navigationItems = [
    {
        name: 'Dashboard',
        routeName: 'admin.dashboard',
        href: route('admin.dashboard'),
        icon: HomeIcon,
    },
    {
        name: 'Series',
        routeName: 'admin.series.index',
        href: route('admin.series.index'),
        icon: BookOpenIcon,
    },
    {
        name: 'Exámenes',
        routeName: 'admin.exams.index',
        href: route('admin.exams.index'),
        icon: DocumentTextIcon,
    },
    {
        name: 'Usuarios',
        routeName: 'admin.users.index',
        href: route('admin.users.index'),
        icon: UsersIcon,
    },
    {
        name: 'Estadísticas',
        routeName: 'admin.stats',
        href: route('admin.stats'),
        icon: ChartBarIcon,
    },
];

// Verificar si una ruta está activa (soporta comodines como 'admin.series.*' si se desea)
const isActive = (routeName) => {
    if (routeName.endsWith('.*')) {
        const base = routeName.slice(0, -2);
        return route().current(base + '*');
    }
    return route().current(routeName);
};

// Computed para las clases del sidebar
const sidebarClasses = computed(() => {
    if (props.isMobile) {
        return props.isOpen
            ? 'translate-x-0 w-64'
            : '-translate-x-full w-64';
    }
    return props.isOpen ? 'w-64' : 'w-[4.5rem]';
});
</script>

<template>
    <!-- Overlay para móvil -->
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isMobile && isOpen"
            @click="emit('close')"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
        ></div>
    </Transition>

    <!-- Sidebar -->
    <aside
        :class="[
            sidebarClasses,
            isMobile ? 'fixed' : 'sticky',
        ]"
        class="top-0 left-0 h-screen bg-admin-gradient shadow-sidebar
               transition-all duration-300 ease-in-out z-50
               flex flex-col overflow-hidden"
    >
        <!-- Header del Sidebar -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-white/10 flex-shrink-0">
            <Transition
                enter-active-class="transition-opacity duration-300 delay-100"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <!-- Un solo hijo: el Link contiene logo + texto -->
                <Link
                    v-if="isOpen || isMobile"
                    :href="route('admin.dashboard')"
                    class="flex items-center gap-2 overflow-hidden"
                >
                    <img
                        src="/images/logo_quizsilver.svg"
                        alt="QuizSilver Logo"
                        class="h-8 w-auto object-contain flex-shrink-0"
                    />
                    <span class="text-white font-semibold text-lg tracking-wide whitespace-nowrap">
                        QuizSilver
                    </span>
                </Link>
            </Transition>

            <!-- Botón cerrar (móvil) -->
            <button
                v-if="isMobile"
                @click="emit('close')"
                class="text-white hover:bg-white/10 p-1.5 rounded-lg transition-colors lg:hidden"
                aria-label="Cerrar menú"
            >
                <XMarkIcon class="w-5 h-5" />
            </button>

            <!-- Botón colapsar (desktop) -->
            <button
                v-else
                @click="emit('toggle')"
                :class="{ 'rotate-180': !isOpen }"
                class="text-white hover:bg-white/10 p-1.5 rounded-lg transition-all duration-300 hidden lg:block"
                aria-label="Colapsar menú"
            >
                <ChevronLeftIcon class="w-5 h-5" />
            </button>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 custom-scrollbar">
            <ul class="space-y-1.5">
                <li v-for="item in navigationItems" :key="item.name">
                    <Link
                        :href="item.href"
                        :class="[
                            isActive(item.routeName)
                                ? 'bg-white/20 text-white shadow-sidebar-item'
                                : 'text-white/80 hover:bg-white/10 hover:text-white',
                            !isOpen && !isMobile ? 'justify-center' : '',
                        ]"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                               transition-all duration-200 group relative"
                    >
                        <component
                            :is="item.icon"
                            class="w-5 h-5 flex-shrink-0 transition-transform duration-200 group-hover:scale-110"
                        />

                        <Transition
                            enter-active-class="transition-opacity duration-200 delay-100"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition-opacity duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <span
                                v-if="isOpen || isMobile"
                                class="font-medium whitespace-nowrap text-sm"
                            >
                                {{ item.name }}
                            </span>
                        </Transition>

                        <!-- Tooltip cuando está colapsado -->
                        <span
                            v-if="!isOpen && !isMobile"
                            class="absolute left-full ml-3 px-2 py-1 bg-gray-900 text-white text-xs
                                   rounded opacity-0 group-hover:opacity-100 pointer-events-none
                                   transition-opacity duration-200 whitespace-nowrap z-50 shadow-lg"
                        >
                            {{ item.name }}
                        </span>

                        <!-- Indicador activo -->
                        <span
                            v-if="isActive(item.routeName)"
                            class="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-l-full"
                        ></span>
                    </Link>
                </li>
            </ul>
        </nav>

        <!-- Footer del Sidebar -->
        <div class="border-t border-white/10 p-4 flex-shrink-0">
            <Transition
                enter-active-class="transition-opacity duration-300 delay-100"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="isOpen || isMobile" class="text-white/70 text-xs text-center">
                    <p class="font-medium">Quizz SilverApp</p>
                    <p class="mt-1">v1.0.0</p>
                </div>
            </Transition>
        </div>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>