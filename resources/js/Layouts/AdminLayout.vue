<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Admin/Sidebar.vue';
import Navbar from '@/Components/Admin/Navbar.vue';

defineProps({
    title: {
        type: String,
        default: 'Admin',
    },
});

// Estado del sidebar
const sidebarOpen = ref(true);
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Detección de móvil/tablet
const isMobile = computed(() => windowWidth.value < 1024);

// Handlers
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    if (isMobile.value) {
        sidebarOpen.value = false;
    }
};

const handleResize = () => {
    windowWidth.value = window.innerWidth;
    // Auto-cerrar en móvil, auto-abrir en desktop
    if (windowWidth.value < 1024) {
        sidebarOpen.value = false;
    } else {
        sidebarOpen.value = true;
    }
};

onMounted(() => {
    handleResize();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <Sidebar
            :is-open="sidebarOpen"
            :is-mobile="isMobile"
            @toggle="toggleSidebar"
            @close="closeSidebar"
        />

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col min-w-0 min-h-screen">
            <!-- Navbar -->
            <Navbar
                :is-sidebar-open="sidebarOpen"
                @toggle-sidebar="toggleSidebar"
            />

            <!-- Header de la página (opcional) -->
            <header v-if="$slots.header" class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <slot name="header" />
                </div>
            </header>

            <!-- Contenido -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>