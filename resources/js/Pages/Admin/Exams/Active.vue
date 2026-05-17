<template>
  <AdminLayout>
    <div class="py-6 max-w-5xl mx-auto px-4">

      <!-- Cabecera con título y timer -->
      <div class="mb-5 flex flex-wrap justify-between items-start gap-3">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-widest mb-0.5">
            {{ exam.series }}
          </p>
          <h1 class="text-xl font-bold text-gray-800">{{ exam.title }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ typeLabel(exam.type) }}</p>
        </div>

        <div
          v-if="timeLimitSeconds"
          class="flex items-center gap-2 px-4 py-2 rounded-xl font-mono text-lg font-bold
                 transition-colors duration-500"
          :class="{
            'bg-red-100 text-red-700 animate-pulse': isTimeCritical,
            'bg-yellow-100 text-yellow-700':         isTimeWarning && !isTimeCritical,
            'bg-gray-100 text-gray-700':             !isTimeWarning,
          }"
        >
          <span>⏱</span>
          <span>{{ formattedTime }}</span>
        </div>
      </div>

      <ProgressBar :answered="answeredCount" :total="totalQuestions" />

      <TimelineSteps
        :current="currentPage"
        :total="totalPages"
        :questions-per-page="QUESTIONS_PER_PAGE"
        :total-questions="totalQuestions"
      />

      <Transition :name="transitionName" mode="out-in">
        <div :key="currentPage">
          <QuestionCard
            v-for="question in currentPageQuestions"
            :key="question.id"
            :question="question"
            :number="getQuestionNumber(question.id)"
            :model-value="answers[question.id]"
            @update:model-value="(val) => onAnswerChange(question.id, val)"
          />
        </div>
      </Transition>

      <NavigationButtons
        :current-page="currentPage"
        :total-pages="totalPages"
        :is-submitting="isSubmitting"
        @prev="prevPage"
        @next="nextPage"
        @submit="confirmSubmit"
      />

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout       from '@/Layouts/AdminLayout.vue';
import ProgressBar       from '@/Components/Student/Attempt/ProgressBar.vue';
import TimelineSteps     from '@/Components/Student/Attempt/TimelineSteps.vue';
import NavigationButtons from '@/Components/Student/Attempt/NavigationButtons.vue';
import QuestionCard      from '@/Components/Student/Attempt/QuestionCard.vue';

const props = defineProps({
  attempt:          { type: Object, required: true },
  exam:             { type: Object, required: true },
  questions:        { type: Array,  required: true },
  existingAnswers:  { type: Object, default: () => ({}) },
  remainingSeconds: { type: Number, default: null },
});

const QUESTIONS_PER_PAGE = 5;

const answers        = ref({ ...props.existingAnswers });
const currentPage    = ref(0);
const isSubmitting   = ref(false);
const transitionName = ref('slide-left');
const remainingSeconds = ref(props.remainingSeconds);

let countdownInterval = null;
let heartbeatInterval = null;

const totalQuestions = computed(() => props.questions.length);
const totalPages     = computed(() => Math.ceil(totalQuestions.value / QUESTIONS_PER_PAGE));
const answeredCount  = computed(() => Object.keys(answers.value).length);

const currentPageQuestions = computed(() => {
  const start = currentPage.value * QUESTIONS_PER_PAGE;
  return props.questions.slice(start, start + QUESTIONS_PER_PAGE);
});

const getQuestionNumber = (id) =>
  props.questions.findIndex(q => q.id === id) + 1;

const timeLimitSeconds = computed(() => remainingSeconds.value !== null);

const formattedTime = computed(() => {
  if (remainingSeconds.value === null) return '';
  const m = Math.floor(remainingSeconds.value / 60);
  const s = remainingSeconds.value % 60;
  return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
});

const isTimeWarning  = computed(() => remainingSeconds.value !== null && remainingSeconds.value <= 300);
const isTimeCritical = computed(() => remainingSeconds.value !== null && remainingSeconds.value <= 60);

const typeLabel = (type) => ({
  single_choice:   'Opción única',
  multiple_choice: 'Opción múltiple',
  true_false:      'Verdadero / Falso',
  ordering:        'Ordenamiento',
  matching:        'Relacionar columnas',
}[type] ?? type);

const nextPage = () => {
  if (currentPage.value < totalPages.value - 1) {
    transitionName.value = 'slide-left';
    currentPage.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const prevPage = () => {
  if (currentPage.value > 0) {
    transitionName.value = 'slide-right';
    currentPage.value--;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const onAnswerChange = async (questionId, value) => {
  answers.value = { ...answers.value, [questionId]: value };
  try {
    await axios.post(route('attempts.answer', props.attempt.id), {
      question_id: questionId,
      user_answer: value,
    });
  } catch (e) {
    console.error('[Admin/Exams/Active.vue] Error al guardar respuesta:', e);
  }
};

const startHeartbeat = () => {
  heartbeatInterval = setInterval(async () => {
    try {
      const res = await axios.post(route('attempts.heartbeat', props.attempt.id));
      if (res.data.remaining_seconds !== undefined) {
        remainingSeconds.value = res.data.remaining_seconds;
      }
    } catch (e) {
      if (e.response?.status === 409) { stopHeartbeat(); stopCountdown(); }
    }
  }, 30_000);
};

const stopHeartbeat = () => {
  if (heartbeatInterval) { clearInterval(heartbeatInterval); heartbeatInterval = null; }
};

const startCountdown = () => {
  if (remainingSeconds.value === null) return;
  countdownInterval = setInterval(() => {
    if (remainingSeconds.value <= 0) { stopCountdown(); handleTimeOut(); return; }
    remainingSeconds.value -= 1;
  }, 1_000);
};

const stopCountdown = () => {
  if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
};

const handleTimeOut = async () => {
  stopHeartbeat();
  await Swal.fire({
    icon: 'warning', title: '¡Tiempo agotado!',
    text: 'El tiempo límite se cumplió. Enviando respuestas...',
    confirmButtonText: 'Aceptar', confirmButtonColor: '#ef4444',
    allowOutsideClick: false, timer: 3000, timerProgressBar: true,
  });
  await submitExam();
};

const confirmSubmit = async () => {
  const unanswered = totalQuestions.value - answeredCount.value;
  const result = await Swal.fire({
    icon: unanswered > 0 ? 'warning' : 'question',
    title: '¿Finalizar evaluación?',
    html: unanswered > 0
      ? `Tenés <strong>${unanswered}</strong> pregunta${unanswered !== 1 ? 's' : ''} sin responder.`
      : 'Respondiste todas las preguntas. ¿Confirmar envío?',
    showCancelButton: true,
    confirmButtonText: 'Sí, finalizar', cancelButtonText: 'Seguir respondiendo',
    confirmButtonColor: '#6366f1', cancelButtonColor: '#6b7280',
  });
  if (result.isConfirmed) await submitExam();
};

const submitExam = async () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  stopHeartbeat();
  stopCountdown();

  router.post(route('attempts.complete', props.attempt.id), {}, {
    onError: () => {
      isSubmitting.value = false;
      startHeartbeat();
      startCountdown();
      Swal.fire({
        icon: 'error', title: 'Error al enviar',
        text: 'No pudimos procesar el examen. Intentá nuevamente.',
        confirmButtonColor: '#ef4444',
      });
    },
  });
};

onMounted(() => {
  startCountdown();
  startHeartbeat();

  if (props.attempt.is_resumed && answeredCount.value > 0) {
    const idx = props.questions.findIndex(
      q => !Object.prototype.hasOwnProperty.call(answers.value, q.id)
    );
    if (idx >= 0) currentPage.value = Math.floor(idx / QUESTIONS_PER_PAGE);

    Swal.fire({
      icon: 'info', title: 'Intento retomado',
      html: `Continuás desde donde lo dejaste.<br>
             <strong>${answeredCount.value}</strong> de
             <strong>${totalQuestions.value}</strong> preguntas ya respondidas.`,
      confirmButtonText: 'Continuar', confirmButtonColor: '#6366f1',
      timer: 4000, timerProgressBar: true,
    });
  }
});

onUnmounted(() => {
  stopHeartbeat();
  stopCountdown();
});
</script>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-left-enter-from { opacity: 0; transform: translateX(40px); }
.slide-left-leave-to   { opacity: 0; transform: translateX(-40px); }

.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-right-enter-from { opacity: 0; transform: translateX(-40px); }
.slide-right-leave-to   { opacity: 0; transform: translateX(40px); }
</style>