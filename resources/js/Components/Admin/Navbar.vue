
<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bars3Icon,
    BellIcon,
    UserCircleIcon,
    ArrowRightOnRectangleIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    isSidebarOpen: Boolean,
});

const emit = defineEmits(['toggle-sidebar']);

const page = usePage();
const showUserMenu = ref(false);

const user = page.props.auth?.user || { name: 'Usuario', email: '' };

const closeUserMenu = () => {
    setTimeout(() => {
        showUserMenu.value = false;
    }, 150);
};
</script>

<template>
    <header class="sticky top-0 z-30 bg-white shadow-sm border-b border-gray-200">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6">
            <!-- Botón toggle sidebar -->
            <button
                @click="emit('toggle-sidebar')"
                class="text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors"
                aria-label="Abrir menú"
            >
                <Bars3Icon class="w-6 h-6" />
            </button>

            <!-- Acciones del usuario -->
            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Notificaciones -->
                <button
                    class="relative text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors"
                    aria-label="Notificaciones"
                >
                    <BellIcon class="w-6 h-6" />
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Menú de usuario -->
                <div class="relative">
                    <button
                        @click="showUserMenu = !showUserMenu"
                        @blur="closeUserMenu"
                        class="flex items-center gap-2 text-gray-700 hover:bg-gray-100 p-1.5 pr-3 rounded-lg transition-colors"
                    >
                        <div class="w-8 h-8 rounded-full bg-admin-gradient flex items-center justify-center text-white font-semibold text-sm">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <span class="hidden sm:block text-sm font-medium">{{ user.name }}</span>
                    </button>

                    <!-- Dropdown -->
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="showUserMenu"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 origin-top-right"
                        >
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-900">{{ user.name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
                            </div>

                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                <Cog6ToothIcon class="w-4 h-4" />
                                Configuración
                            </Link>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                Cerrar Sesión
                            </Link>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>