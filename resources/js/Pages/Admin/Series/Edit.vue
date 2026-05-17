<!-- resources/js/Pages/Admin/Series/Edit.vue -->
<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  series: {
    type: Object,
    required: true,
    // { id, title, description, domain }
  },
});

const form = useForm({
  title:       props.series.title,
  description: props.series.description ?? '',
  domain:      props.series.domain,
});

const submit = () => {
  form.patch(route('admin.series.update', props.series.id));
};
</script>

<template>
  <AdminLayout title="Editar Serie">

    <template #header>
      <div class="flex items-center gap-3">
        <Link
          :href="route('admin.series.index')"
          class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Editar Serie</h2>
          <p class="text-sm text-gray-500 mt-0.5">{{ series.title }}</p>
        </div>
      </div>
    </template>

    <div class="max-w-xl">
      <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">

        <!-- Título -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Título <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors"
            :class="form.errors.title ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          />
          <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">
            {{ form.errors.title }}
          </p>
        </div>

        <!-- Dominio -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Dominio temático <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.domain"
            type="text"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors"
            :class="form.errors.domain ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          />
          <p v-if="form.errors.domain" class="mt-1 text-xs text-red-600">
            {{ form.errors.domain }}
          </p>
        </div>

        <!-- Descripción -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Descripción <span class="text-gray-400 font-normal">(opcional)</span>
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                   focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-colors
                   resize-none"
          ></textarea>
        </div>

        <!-- Indicador de cambios -->
        <p v-if="form.isDirty" class="text-xs text-amber-600">
          ⚠️ Tenés cambios sin guardar
        </p>

        <!-- Botones -->
        <div class="flex items-center gap-3 pt-2">
          <button
            type="submit"
            :disabled="form.processing || !form.isDirty"
            class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700
                   text-white text-sm font-semibold rounded-lg transition-colors
                   disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4"
              fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <Link
            :href="route('admin.series.index')"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg
                   hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </Link>
        </div>

      </form>
    </div>

  </AdminLayout>
</template>