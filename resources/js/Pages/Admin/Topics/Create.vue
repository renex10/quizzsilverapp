<!--
=============================================================================
  Admin/Topics/Create.vue
  Ubicación: resources/js/Pages/Admin/Topics/Create.vue
=============================================================================

  ¿QUÉ HACE ESTA VISTA?
  ──────────────────────
  Orquesta los componentes del formulario de creación de un topic.
  No tiene lógica de negocio propia — eso vive en cada componente.

  Su única responsabilidad es:
    1. Instanciar el form (useForm de Inertia)
    2. Conectar cada componente con su campo del form
    3. Manejar el submit y los errores

  PROPS QUE RECIBE (desde TopicController@create):
  ──────────────────────────────────────────────────
    serie          → { id, title, published_at }
    examCategories → [{ category, count }] — categorías del examen final
    tieneExamen    → boolean — si la serie tiene examen final publicado
    nextOrder      → number  — el siguiente número de orden disponible
=============================================================================
-->

<template>
  <AdminLayout title="Nuevo Topic">

    <!-- ── Encabezado ──────────────────────────────────────────────────── -->
    <template #header>
      <div class="flex items-center gap-3">
        <Link
          :href="route('admin.topics.index', { series: serie.id })"
          class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600
                 transition-all duration-150"
          title="Volver a topics"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-0.5">
            <span>{{ serie.title }}</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-600 font-medium">Topics</span>
          </div>
          <h2 class="text-2xl font-bold text-gray-800 leading-tight">Nuevo Topic</h2>
        </div>
      </div>
    </template>

    <!-- ── Formulario ──────────────────────────────────────────────────── -->
    <form @submit.prevent="submit" class="max-w-5xl">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- Columna principal (2/3) -->
        <div class="lg:col-span-2 space-y-5">

          <!-- Campos base: título, descripción, orden -->
          <TopicForm
            v-model="formData"
            :errors="form.errors"
            :next-order="nextOrder"
          />

          <!-- Selector de categoría del examen -->
          <TopicCategorySelect
            v-model="form.exam_category"
            :exam-categories="examCategories"
            :tiene-examen="tieneExamen"
            :error="form.errors.exam_category"
          />

        </div>

        <!-- Columna lateral (1/3) -->
        <div class="space-y-5">

          <!-- Selector de ícono y color -->
          <TopicIconPicker
            v-model:icon="form.icon"
            v-model:color="form.color"
          />

          <!-- Toggle de visibilidad pública -->
          <TopicVisibility
            v-model="form.is_public"
            :serie-publica="!!serie.published_at"
            :preview-count="0"
            :es-primero="nextOrder === 1"
          />

        </div>

      </div>

      <!-- ── Barra de acciones ──────────────────────────────────────────── -->
      <div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-200">

        <!-- Indicador de campos requeridos -->
        <p class="text-xs text-gray-400">
          Los campos marcados con <span class="text-red-500">*</span> son obligatorios
        </p>

        <div class="flex items-center gap-3">
          <Link
            :href="route('admin.topics.index', { series: serie.id })"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-xl
                   hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing || !form.title"
            class="flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600
                   to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white
                   text-sm font-semibold rounded-xl shadow-sm hover:shadow-md
                   transition-all duration-200 disabled:opacity-50
                   disabled:cursor-not-allowed disabled:shadow-none"
          >
            <svg
              v-if="form.processing"
              class="animate-spin w-4 h-4"
              fill="none" viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <svg
              v-else
              class="w-4 h-4"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 13l4 4L19 7"/>
            </svg>
            {{ form.processing ? 'Creando...' : 'Crear topic' }}
          </button>
        </div>

      </div>
    </form>

  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout          from '@/Layouts/AdminLayout.vue';
import TopicForm            from '@/Components/Admin/Topics/TopicForm.vue';
import TopicIconPicker      from '@/Components/Admin/Topics/TopicIconPicker.vue';
import TopicCategorySelect  from '@/Components/Admin/Topics/TopicCategorySelect.vue';
import TopicVisibility      from '@/Components/Admin/Topics/TopicVisibility.vue';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  serie:          { type: Object,  required: true },
  examCategories: { type: Array,   default: () => [] },
  tieneExamen:    { type: Boolean, default: false },
  nextOrder:      { type: Number,  default: 1 },
});

// ─── Formulario ───────────────────────────────────────────────────────────────

const form = useForm({
  title:         '',
  description:   '',
  order:         props.nextOrder,
  icon:          null,
  color:         '#6366f1',
  is_public:     false,
  exam_category: null,
});

/**
 * formData actúa como proxy bidireccional para TopicForm.
 *
 * TopicForm recibe un objeto con v-model y emite update:modelValue.
 * Como useForm no acepta v-model directo en un objeto parcial,
 * usamos una computada con getter y setter para sincronizar los
 * campos title, description y order con el form de Inertia.
 */
const formData = computed({
  get: () => ({
    title:       form.title,
    description: form.description,
    order:       form.order,
  }),
  set: (val) => {
    form.title       = val.title;
    form.description = val.description;
    form.order       = val.order;
  },
});

// ─── Submit ───────────────────────────────────────────────────────────────────

const submit = () => {
  form.post(route('admin.topics.store', { series: props.serie.id }));
};
</script>