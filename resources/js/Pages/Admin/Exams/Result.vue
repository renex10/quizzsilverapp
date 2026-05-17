<!-- resources/js/Pages/Admin/Exams/Result.vue -->
<template>
  <AdminLayout>
    <div class="py-10">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- ─── Cabecera ─────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">
            {{ exam.series }}
          </p>
          <h1 class="text-2xl font-bold text-gray-900">{{ exam.title }}</h1>
          <p class="text-sm text-gray-400 mt-0.5">
            Completado el {{ attempt.completed_at }}
          </p>
        </div>

        <!-- ─── Resultado global ─────────────────────────────────────────── -->
        <div
          class="rounded-2xl shadow-sm border p-8 text-center"
          :class="result.passed
            ? 'bg-green-50 border-green-200'
            : 'bg-red-50 border-red-200'"
        >
          <div class="text-5xl mb-3">{{ result.passed ? '🎉' : '😓' }}</div>
          <div
            class="inline-flex items-center gap-2 px-6 py-2 rounded-full
                   text-xl font-bold mb-4"
            :class="result.passed
              ? 'bg-green-100 text-green-800'
              : 'bg-red-100 text-red-800'"
          >
            {{ result.passed ? '¡Aprobado!' : 'Reprobado' }}
          </div>
          <p class="text-4xl font-black text-gray-800 mb-1">
            {{ result.percentage }}%
          </p>
          <p class="text-sm text-gray-500">
            {{ result.total_correct }} correctas de {{ summary.totalQuestions }} preguntas
          </p>
          <div
            v-if="summary.criticalFailed"
            class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg
                   bg-orange-100 text-orange-800 text-sm font-medium"
          >
            ⚠️ Reprobado por pregunta crítica fallada
          </div>
          <p class="mt-4 text-sm text-gray-600 max-w-md mx-auto">
            {{ recommendation }}
          </p>
        </div>

        <!-- ─── Tarjetas de resumen ──────────────────────────────────────── -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-3xl font-black text-gray-800">{{ summary.totalQuestions }}</p>
            <p class="text-xs text-gray-500 mt-1">Total</p>
          </div>
          <div class="bg-green-50 rounded-xl border border-green-100 shadow-sm p-4 text-center">
            <p class="text-3xl font-black text-green-700">{{ result.total_correct }}</p>
            <p class="text-xs text-green-600 mt-1">Correctas</p>
          </div>
          <div class="bg-red-50 rounded-xl border border-red-100 shadow-sm p-4 text-center">
            <p class="text-3xl font-black text-red-700">{{ result.total_wrong }}</p>
            <p class="text-xs text-red-600 mt-1">Incorrectas</p>
          </div>
          <div class="bg-gray-50 rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-3xl font-black text-gray-600">{{ summary.unanswered }}</p>
            <p class="text-xs text-gray-500 mt-1">Sin responder</p>
          </div>
        </div>

        <!-- ─── Tiempo empleado ──────────────────────────────────────────── -->
        <div
          v-if="result.time_used_seconds"
          class="bg-white rounded-xl border border-gray-100 shadow-sm p-4
                 flex items-center justify-center gap-3"
        >
          <span class="text-2xl">⏱</span>
          <div class="text-center">
            <p class="font-mono text-2xl font-bold text-gray-800">
              {{ formatTime(result.time_used_seconds) }}
            </p>
            <p class="text-xs text-gray-400">Tiempo empleado</p>
          </div>
        </div>

        <!-- ─── Rendimiento por categoría ────────────────────────────────── -->
        <div
          v-if="hasCategoryData"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6"
        >
          <h2 class="text-lg font-bold text-gray-800 mb-4">📊 Rendimiento por categoría</h2>
          <div class="space-y-3">
            <div
              v-for="(stats, cat) in result.detail.byCategory"
              :key="cat"
              class="flex items-center gap-4"
            >
              <span class="w-36 text-sm text-gray-700 font-medium capitalize truncate shrink-0">
                {{ cat }}
              </span>
              <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="h-full bg-indigo-500 rounded-full transition-all duration-700"
                  :style="{ width: `${categoryPercent(stats)}%` }"
                ></div>
              </div>
              <div class="shrink-0 flex gap-2 text-xs">
                <span class="text-green-600 font-medium">✓ {{ stats.correct }}</span>
                <span class="text-red-500 font-medium">✗ {{ stats.incorrect }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ─── Rendimiento por dificultad ───────────────────────────────── -->
        <div
          v-if="hasDifficultyData"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6"
        >
          <h2 class="text-lg font-bold text-gray-800 mb-4">📈 Rendimiento por dificultad</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
              v-for="(stats, diff) in result.detail.byDifficulty"
              :key="diff"
              class="rounded-xl p-4 text-center border"
              :class="{
                'bg-green-50 border-green-100':   diff === 'baja',
                'bg-yellow-50 border-yellow-100': diff === 'media',
                'bg-red-50 border-red-100':       diff === 'alta',
              }"
            >
              <p class="font-bold capitalize text-gray-700 mb-2">{{ diff }}</p>
              <p class="text-2xl font-black text-gray-800">
                {{ (stats.correct ?? 0) + (stats.incorrect ?? 0) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">preguntas</p>
              <div class="flex justify-center gap-3 mt-2 text-xs">
                <span class="text-green-600 font-medium">✓ {{ stats.correct }}</span>
                <span class="text-red-500 font-medium">✗ {{ stats.incorrect }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ─── Preguntas incorrectas ────────────────────────────────────── -->
        <div
          v-if="incorrectQuestions.length > 0"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6"
        >
          <h2 class="text-lg font-bold text-gray-800 mb-4">
            🔍 Preguntas falladas
            <span class="text-sm font-normal text-gray-400 ml-1">
              ({{ incorrectQuestions.length }})
            </span>
          </h2>
          <div class="space-y-4">
            <div
              v-for="(q, idx) in incorrectQuestions"
              :key="q.questionId"
              class="border border-gray-200 rounded-xl p-4"
            >
              <div class="flex items-start justify-between gap-2 mb-3">
                <p class="font-medium text-gray-800 text-sm leading-relaxed">
                  <span class="text-gray-400 mr-1">{{ idx + 1 }}.</span>
                  {{ q.questionText }}
                </p>
                <div class="flex gap-1 shrink-0">
                  <span
                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="{
                      'bg-green-100 text-green-700':   q.difficulty === 'baja',
                      'bg-yellow-100 text-yellow-700': q.difficulty === 'media',
                      'bg-red-100 text-red-700':       q.difficulty === 'alta',
                    }"
                  >{{ q.difficulty }}</span>
                  <span
                    v-if="q.critical"
                    class="text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 font-medium"
                  >⚠ crítica</span>
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                <div class="bg-red-50 rounded-lg p-3 text-sm">
                  <p class="text-xs text-red-500 font-medium mb-1">Tu respuesta</p>
                  <p class="text-gray-800">{{ q.userAnswer ?? 'Sin responder' }}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-3 text-sm">
                  <p class="text-xs text-green-600 font-medium mb-1">Respuesta correcta</p>
                  <p class="text-gray-800">{{ q.correctAnswer }}</p>
                </div>
              </div>
              <div
                v-if="q.explanation"
                class="bg-blue-50 rounded-lg p-3 text-sm text-gray-700"
              >
                <span class="text-blue-600 font-medium">📖 Explicación: </span>
                {{ q.explanation }}
              </div>
            </div>
          </div>
        </div>

        <!-- ─── Acciones ─────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
            ¿Qué querés hacer ahora?
          </p>
          <div class="flex flex-wrap gap-3">

            <!-- Reintentar -->
            <button
              @click="retryExam(false)"
              class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white
                     rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors"
            >
              🔄 Reintentar
            </button>

            <!-- Reintentar con orden aleatorio -->
            <button
              @click="retryExam(true)"
              class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white
                     rounded-lg hover:bg-purple-700 text-sm font-medium transition-colors"
            >
              🔀 Reintentar (orden aleatorio)
            </button>

            <!-- Volver al detalle del examen — exclusivo del admin -->
            <Link
              :href="route('admin.exams.show', attempt.exam_id)"
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300
                     text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium
                     transition-colors"
            >
              📋 Ver detalle del examen
            </Link>

            <!-- Volver al listado de exámenes -->
            <Link
              :href="route('admin.exams.index')"
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300
                     text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium
                     transition-colors"
            >
              📚 Listado de exámenes
            </Link>

            <!-- Estadísticas globales -->
            <Link
              :href="route('admin.stats')"
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300
                     text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium
                     transition-colors"
            >
              📊 Estadísticas globales
            </Link>

          </div>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// Props — idénticas a Student/Results/Show.vue
// Llegan desde AttemptController@result
const props = defineProps({
  attempt: { type: Object, required: true },
  // { id, exam_id, completed_at }
  exam:    { type: Object, required: true },
  // { id, title, series }
  result:  { type: Object, required: true },
  // { score, percentage, passed, time_used_seconds,
  //   total_correct, total_wrong,
  //   detail: { summary, byCategory, byDifficulty, incorrectQuestions } }
});

// Accesos seguros al detail
const summary = computed(() => props.result.detail?.summary ?? {
  totalQuestions: 0,
  answered:       0,
  unanswered:     0,
  correct:        0,
  incorrect:      0,
  criticalFailed: false,
});

const incorrectQuestions = computed(() =>
  props.result.detail?.incorrectQuestions ?? []
);

const hasCategoryData = computed(() =>
  props.result.detail?.byCategory &&
  Object.keys(props.result.detail.byCategory).length > 0
);

const hasDifficultyData = computed(() =>
  props.result.detail?.byDifficulty &&
  Object.keys(props.result.detail.byDifficulty).length > 0
);

// Recomendación calculada en frontend
const recommendation = computed(() => {
  if (summary.value.criticalFailed) {
    return 'Fallaste una pregunta crítica. Revisá esa área antes de reintentar.';
  }
  const pct = props.result.percentage;
  if (pct >= 90) return '¡Excelente! Dominio sólido del tema.';
  if (pct >= 80) return 'Buen resultado. Revisá las preguntas incorrectas.';
  if (pct >= 60) return 'Estás cerca. Repasá las categorías con más errores.';
  return 'Necesitás reforzar el estudio antes de reintentar.';
});

const formatTime = (seconds) => {
  if (!seconds && seconds !== 0) return '--:--';
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
};

const categoryPercent = (stats) => {
  const total = (stats.correct ?? 0) + (stats.incorrect ?? 0);
  return total > 0 ? Math.round((stats.correct / total) * 100) : 0;
};

const retryExam = (shuffle) => {
  router.post(route('admin.exams.attempt.start', props.attempt.exam_id), { shuffle });
};
</script>