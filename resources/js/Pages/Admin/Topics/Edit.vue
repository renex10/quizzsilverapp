<!--
=============================================================================
  Admin/Topics/Edit.vue
  Ubicación: resources/js/Pages/Admin/Topics/Edit.vue
=============================================================================

  ¿QUÉ HACE ESTA VISTA?
  ──────────────────────
  Idéntica a Create.vue en estructura y componentes.
  La diferencia es que viene prellenada con los datos del topic
  existente y envía PATCH en lugar de POST.

  DIFERENCIAS RESPECTO A Create.vue:
  ────────────────────────────────────
    1. El form se inicializa con los datos del topic (no vacío)
    2. submit() usa form.patch() en lugar de form.post()
    3. TopicVisibility recibe previewCount y esPrimero reales
       (en Create siempre son 0 y nextOrder===1 respectivamente)
    4. El breadcrumb y el título muestran "Editar Topic"
    5. El botón dice "Guardar cambios" y se deshabilita si no hay cambios
       (form.isDirty)

  PROPS QUE RECIBE (desde TopicController@edit):
  ────────────────────────────────────────────────
    topic          → { id, title, description, icon, color, order,
                       is_public, exam_category, cover_url }
    serie          → { id, title, published_at }
    examCategories → [{ category, count }]
    tieneExamen    → boolean
    previewCount   → number — lecciones con is_preview=true en este topic
    esPrimero      → boolean — si order === 1
    nextOrder      → number  — máximo order actual (para referencia)
=============================================================================
-->

<template>
  <AdminLayout :title="`Editar — ${topic.title}`">

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
          <h2 class="text-2xl font-bold text-gray-800 leading-tight">
            Editar Topic
          </h2>
        </div>
      </div>
    </template>

    <!-- ── Formulario ──────────────────────────────────────────────────── -->
    <form @submit.prevent="submit" class="max-w-5xl">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- Columna principal (2/3) -->
        <div class="lg:col-span-2 space-y-5">

          <TopicForm
            v-model="formData"
            :errors="form.errors"
            :next-order="nextOrder"
          />

          <TopicCategorySelect
            v-model="form.exam_category"
            :exam-categories="examCategories"
            :tiene-examen="tieneExamen"
            :error="form.errors.exam_category"
          />

        </div>

        <!-- Columna lateral (1/3) -->
        <div class="space-y-5">

          <TopicIconPicker
            v-model:icon="form.icon"
            v-model:color="form.color"
          />

          <TopicVisibility
            v-model="form.is_public"
            :serie-publica="!!serie.published_at"
            :preview-count="previewCount"
            :es-primero="esPrimero"
          />

        </div>

      </div>

      <!-- ── Barra de acciones ──────────────────────────────────────────── -->
      <div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-200">

        <!-- Indicador de cambios sin guardar -->
        <div class="flex items-center gap-2">
          <div
            class="w-2 h-2 rounded-full transition-colors duration-300"
            :class="form.isDirty ? 'bg-amber-400' : 'bg-gray-200'"
          ></div>
          <span class="text-xs text-gray-500">
            {{ form.isDirty ? 'Cambios sin guardar' : 'Sin cambios' }}
          </span>
        </div>

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
            :disabled="form.processing || !form.isDirty"
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
            {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>

      </div>
    </form>

  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout         from '@/Layouts/AdminLayout.vue';
import TopicForm           from '@/Components/Admin/Topics/TopicForm.vue';
import TopicIconPicker     from '@/Components/Admin/Topics/TopicIconPicker.vue';
import TopicCategorySelect from '@/Components/Admin/Topics/TopicCategorySelect.vue';
import TopicVisibility     from '@/Components/Admin/Topics/TopicVisibility.vue';

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  topic:          { type: Object,  required: true },
  serie:          { type: Object,  required: true },
  examCategories: { type: Array,   default: () => [] },
  tieneExamen:    { type: Boolean, default: false },
  previewCount:   { type: Number,  default: 0 },
  esPrimero:      { type: Boolean, default: false },
  nextOrder:      { type: Number,  default: 1 },
});

// ─── Formulario prellenado con los datos del topic ────────────────────────────

const form = useForm({
  title:         props.topic.title,
  description:   props.topic.description ?? '',
  order:         props.topic.order,
  icon:          props.topic.icon ?? null,
  color:         props.topic.color ?? '#6366f1',
  is_public:     props.topic.is_public ?? false,
  exam_category: props.topic.exam_category ?? null,
});

// Mismo patrón que Create.vue — proxy para TopicForm
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
  form.patch(route('admin.topics.update', { 
    series: props.serie.id, 
    topic: props.topic.id 
  }));
};
</script>