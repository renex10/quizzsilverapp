<script setup>
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ExamStatusBadge from '@/Components/Admin/Exams/ExamStatusBadge.vue';

const props = defineProps({
  exam: {
    type: Object,
    required: true,
    // { id, title, description, version, type, status, series, created_at }
  },
  questions: {
    type: Array,
    default: () => [],
    // preguntas sin correctAnswer ni explanation
  },
  examConfig: {
    type: Object,
    default: () => ({}),
    // { passingScore, timeLimitMinutes, shuffleQuestions, ... }
  },
});

const tipoLabel = (type) => {
  const map = {
    single_choice:   'Opción única',
    multiple_choice: 'Opción múltiple',
    true_false:      'Verdadero / Falso',
    ordering:        'Ordenamiento',
    matching:        'Relacionar columnas',
  };
  return map[type] ?? type;
};

const iniciarExamen = async () => {
  const result = await Swal.fire({
    icon:              'question',
    title:             '¿Iniciar evaluación?',
    html: `
      Vas a responder <strong>${props.exam.title}</strong>.<br>
      <span class="text-sm text-gray-500">
        ${props.examConfig.timeLimitMinutes
          ? `Tiempo límite: ${props.examConfig.timeLimitMinutes} minutos.`
          : 'Sin límite de tiempo.'}
        Puntaje mínimo de aprobación: ${props.examConfig.passingScore ?? 70}%.
      </span>
    `,
    showCancelButton:  true,
    confirmButtonText: 'Iniciar ahora',
    cancelButtonText:  'Cancelar',
    confirmButtonColor: '#6366f1',
    cancelButtonColor:  '#6b7280',
  });

  if (!result.isConfirmed) return;

  router.post(route('admin.exams.attempt.start', props.exam.id));
};
</script>

<template>
  <AdminLayout :title="exam.title">

    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-sm text-gray-500">{{ exam.series }}</span>
            <span class="text-gray-300">·</span>
            <ExamStatusBadge :status="exam.status" />
          </div>
          <h2 class="text-2xl font-bold text-gray-800">{{ exam.title }}</h2>
          <p v-if="exam.description" class="text-sm text-gray-500 mt-1">
            {{ exam.description }}
          </p>
        </div>

        <!-- Botón principal: Responder evaluación -->
        <button
          @click="iniciarExamen"
          class="shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r
                 from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700
                 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl
                 transition-all duration-200 transform hover:-translate-y-0.5"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832
                 l3.197-2.132a1 1 0 000-1.664z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Responder evaluación
        </button>
      </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Columna principal: preguntas -->
      <div class="lg:col-span-2 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">
          Preguntas
          <span class="text-sm font-normal text-gray-400 ml-1">({{ questions.length }})</span>
        </h3>

        <div
          v-if="questions.length === 0"
          class="text-center py-12 text-gray-400 border border-dashed rounded-lg"
        >
          No hay preguntas disponibles para mostrar.
        </div>

        <div
          v-for="(q, index) in questions"
          :key="q.id"
          class="bg-white rounded-lg border border-gray-200 p-4"
        >
          <div class="flex items-start gap-3">
            <span class="shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600
                         text-xs font-bold flex items-center justify-center">
              {{ index + 1 }}
            </span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 mb-3">{{ q.question }}</p>

              <!-- Opciones para single_choice y multiple_choice -->
              <ul v-if="q.options" class="space-y-1">
                <li
                  v-for="opt in q.options"
                  :key="opt.id"
                  class="flex items-center gap-2 text-sm text-gray-600"
                >
                  <span class="shrink-0 w-5 h-5 rounded border border-gray-300
                               text-xs flex items-center justify-center font-mono">
                    {{ opt.id }}
                  </span>
                  {{ opt.text }}
                </li>
              </ul>

              <!-- Opciones para true_false -->
              <div v-else-if="q.type === 'true_false'"
                class="flex gap-2 text-sm"
              >
                <span class="px-3 py-1 rounded border border-gray-200 text-gray-600">Verdadero</span>
                <span class="px-3 py-1 rounded border border-gray-200 text-gray-600">Falso</span>
              </div>

              <!-- Columnas para matching -->
              <div v-else-if="q.type === 'matching' && q.leftColumn"
                class="grid grid-cols-2 gap-2 text-sm"
              >
                <div>
                  <p class="text-xs text-gray-400 mb-1 font-medium">Columna A</p>
                  <ul class="space-y-1">
                    <li v-for="item in q.leftColumn" :key="item.id"
                      class="px-2 py-1 bg-gray-50 rounded text-gray-600"
                    >
                      {{ item.id }}. {{ item.text }}
                    </li>
                  </ul>
                </div>
                <div>
                  <p class="text-xs text-gray-400 mb-1 font-medium">Columna B</p>
                  <ul class="space-y-1">
                    <li v-for="item in q.rightColumn" :key="item.id"
                      class="px-2 py-1 bg-gray-50 rounded text-gray-600"
                    >
                      {{ item.id }}. {{ item.text }}
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Metadata de la pregunta -->
              <div class="flex items-center gap-3 mt-3 flex-wrap">
                <span class="text-xs text-gray-400">{{ q.category }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded"
                  :class="{
                    'bg-green-50 text-green-600':  q.difficulty === 'baja',
                    'bg-yellow-50 text-yellow-600': q.difficulty === 'media',
                    'bg-red-50 text-red-600':      q.difficulty === 'alta',
                  }"
                >
                  {{ q.difficulty }}
                </span>
                <span v-if="q.critical"
                  class="text-xs px-1.5 py-0.5 rounded bg-orange-50 text-orange-600"
                >
                  crítica
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna lateral: configuración del examen -->
      <div class="space-y-4">
        <div class="bg-white rounded-lg border border-gray-200 p-5 space-y-4">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
            Configuración
          </h3>

          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Tipo</dt>
              <dd class="font-medium text-gray-800">{{ tipoLabel(exam.type) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Versión</dt>
              <dd class="font-medium text-gray-800">{{ exam.version }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Preguntas</dt>
              <dd class="font-medium text-gray-800">{{ questions.length }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Aprobación</dt>
              <dd class="font-medium text-gray-800">{{ examConfig.passingScore ?? 70 }}%</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Tiempo límite</dt>
              <dd class="font-medium text-gray-800">
                {{ examConfig.timeLimitMinutes ? `${examConfig.timeLimitMinutes} min` : 'Sin límite' }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Orden aleatorio</dt>
              <dd class="font-medium text-gray-800">
                {{ examConfig.shuffleQuestions ? 'Sí' : 'No' }}
              </dd>
            </div>
            <div v-if="examConfig.allowPartialScore" class="flex justify-between">
              <dt class="text-gray-500">Puntaje parcial</dt>
              <dd class="font-medium text-gray-800">Habilitado</dd>
            </div>
          </dl>
        </div>

        <!-- Botón secundario volver -->
        <a
          :href="route('admin.exams.index')"
          class="block w-full text-center px-4 py-2 text-sm text-gray-600
                 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          ← Volver al listado
        </a>
      </div>

    </div>

  </AdminLayout>
</template>