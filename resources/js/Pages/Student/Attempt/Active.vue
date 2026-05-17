<template>
  <StudentLayout>
    <div class="py-6 max-w-5xl mx-auto px-4">

      <!-- ─── Cabecera: título + temporizador ─────────────────────────────── -->
      <div class="mb-5 flex flex-wrap justify-between items-start gap-3">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-widest mb-0.5">
            {{ exam.series }}
          </p>
          <h1 class="text-xl font-bold text-gray-800">{{ exam.title }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ typeLabel(exam.type) }}</p>
        </div>

        <!-- Temporizador -->
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

      <!-- ─── Barra de progreso de respuestas ────────────────────────────── -->
      <ProgressBar :answered="answeredCount" :total="totalQuestions" />

      <!-- ─── Timeline de páginas ────────────────────────────────────────── -->
      <TimelineSteps
        :current="currentPage"
        :total="totalPages"
        :questions-per-page="QUESTIONS_PER_PAGE"
        :total-questions="totalQuestions"
      />

      <!-- ─── Preguntas de la página actual con transición ──────────────── -->
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

      <!-- ─── Navegación ──────────────────────────────────────────────────── -->
      <NavigationButtons
        :current-page="currentPage"
        :total-pages="totalPages"
        :is-submitting="isSubmitting"
        @prev="prevPage"
        @next="nextPage"
        @submit="confirmSubmit"
      />

    </div>
  </StudentLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import ProgressBar    from '@/Components/Student/Attempt/ProgressBar.vue';
import TimelineSteps  from '@/Components/Student/Attempt/TimelineSteps.vue';
import NavigationButtons from '@/Components/Student/Attempt/NavigationButtons.vue';
import QuestionCard   from '@/Components/Student/Attempt/QuestionCard.vue';

// ─────────────────────────────────────────────────────────────────────────────
// Props — vienen de AttemptController@start via Inertia
// ─────────────────────────────────────────────────────────────────────────────
const props = defineProps({
  attempt:          { type: Object, required: true },
  exam:             { type: Object, required: true },
  questions:        { type: Array,  required: true },
  existingAnswers:  { type: Object, default: () => ({}) },
  remainingSeconds: { type: Number, default: null },
});

// ─────────────────────────────────────────────────────────────────────────────
// Configuración de paginación
// ─────────────────────────────────────────────────────────────────────────────
// Cuántas preguntas se muestran por página/paso del formulario multipaso
const QUESTIONS_PER_PAGE = 5;

// ─────────────────────────────────────────────────────────────────────────────
// Estado local
// ─────────────────────────────────────────────────────────────────────────────
const answers       = ref({ ...props.existingAnswers });
const currentPage   = ref(0);
const isSubmitting  = ref(false);
const isSaving      = ref(false);

// Dirección de la transición — 'slide-left' cuando avanzamos, 'slide-right' cuando retrocedemos
const transitionName = ref('slide-left');

// Temporizador
const remainingSeconds = ref(props.remainingSeconds);
let countdownInterval = null;
let heartbeatInterval = null;

// ─────────────────────────────────────────────────────────────────────────────
// Computed — paginación
// ─────────────────────────────────────────────────────────────────────────────
const totalQuestions = computed(() => props.questions.length);

const totalPages = computed(() =>
  Math.ceil(totalQuestions.value / QUESTIONS_PER_PAGE)
);

// Preguntas de la página actual
const currentPageQuestions = computed(() => {
  const start = currentPage.value * QUESTIONS_PER_PAGE;
  return props.questions.slice(start, start + QUESTIONS_PER_PAGE);
});

// Número global de la pregunta (para mostrar "7." en lugar de "2.")
const getQuestionNumber = (questionId) => {
  return props.questions.findIndex(q => q.id === questionId) + 1;
};

// Cantidad de preguntas respondidas
const answeredCount = computed(() =>
  Object.keys(answers.value).length
);

// ─────────────────────────────────────────────────────────────────────────────
// Computed — temporizador
// ─────────────────────────────────────────────────────────────────────────────
const timeLimitSeconds = computed(() => remainingSeconds.value !== null);

const formattedTime = computed(() => {
  if (remainingSeconds.value === null) return '';
  const mins = Math.floor(remainingSeconds.value / 60);
  const secs = remainingSeconds.value % 60;
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const isTimeWarning  = computed(() =>
  remainingSeconds.value !== null && remainingSeconds.value <= 300
);
const isTimeCritical = computed(() =>
  remainingSeconds.value !== null && remainingSeconds.value <= 60
);

// ─────────────────────────────────────────────────────────────────────────────
// Tipo de evaluación — label legible
// ─────────────────────────────────────────────────────────────────────────────
const typeLabel = (type) => {
  const map = {
    single_choice:   'Opción única',
    multiple_choice: 'Opción múltiple',
    true_false:      'Verdadero / Falso',
    ordering:        'Ordenamiento',
    matching:        'Relacionar columnas',
  };
  return map[type] ?? type;
};

// ─────────────────────────────────────────────────────────────────────────────
// Navegación entre páginas
// ─────────────────────────────────────────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────────────────────
// Guardar respuesta en tiempo real
// ─────────────────────────────────────────────────────────────────────────────
const onAnswerChange = async (questionId, value) => {
  // Optimistic update — actualiza la UI inmediatamente
  answers.value = { ...answers.value, [questionId]: value };

  // Persistir en el servidor sin bloquear la UI
  try {
    await axios.post(route('attempts.answer', props.attempt.id), {
      question_id: questionId,
      user_answer: value,
    });
  } catch (error) {
    console.error('[Active.vue] Error al guardar respuesta:', error);
    // La respuesta queda en el estado local aunque falle la API
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// Heartbeat — cada 30s actualiza last_seen_at y sincroniza el timer
// ─────────────────────────────────────────────────────────────────────────────
const startHeartbeat = () => {
  heartbeatInterval = setInterval(async () => {
    try {
      const res = await axios.post(route('attempts.heartbeat', props.attempt.id));
      // Sincronizar tiempo restante con el servidor
      if (res.data.remaining_seconds !== undefined) {
        remainingSeconds.value = res.data.remaining_seconds;
      }
    } catch (error) {
      if (error.response?.status === 409) {
        stopHeartbeat();
        stopCountdown();
      }
    }
  }, 30_000);
};

const stopHeartbeat = () => {
  if (heartbeatInterval) {
    clearInterval(heartbeatInterval);
    heartbeatInterval = null;
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// Countdown — descuenta 1 segundo local, sincronizado por heartbeat
// ─────────────────────────────────────────────────────────────────────────────
const startCountdown = () => {
  if (remainingSeconds.value === null) return;

  countdownInterval = setInterval(() => {
    if (remainingSeconds.value <= 0) {
      stopCountdown();
      handleTimeOut();
      return;
    }
    remainingSeconds.value -= 1;
  }, 1_000);
};

const stopCountdown = () => {
  if (countdownInterval) {
    clearInterval(countdownInterval);
    countdownInterval = null;
  }
};

// ─────────────────────────────────────────────────────────────────────────────
// Tiempo agotado
// ─────────────────────────────────────────────────────────────────────────────
const handleTimeOut = async () => {
  stopHeartbeat();
  await Swal.fire({
    icon:              'warning',
    title:             '¡Tiempo agotado!',
    text:              'El tiempo límite se cumplió. Enviando tus respuestas...',
    confirmButtonText: 'Aceptar',
    confirmButtonColor: '#ef4444',
    allowOutsideClick: false,
    timer:             3000,
    timerProgressBar:  true,
  });
  await submitExam();
};

// ─────────────────────────────────────────────────────────────────────────────
// Confirmación y envío final
// ─────────────────────────────────────────────────────────────────────────────
const confirmSubmit = async () => {
  const unanswered = totalQuestions.value - answeredCount.value;

  const result = await Swal.fire({
    icon:  unanswered > 0 ? 'warning' : 'question',
    title: '¿Finalizar evaluación?',
    html: unanswered > 0
      ? `Tenés <strong>${unanswered}</strong> pregunta${unanswered !== 1 ? 's' : ''} sin responder.<br>
         Una vez enviado no podrás modificar tus respuestas.`
      : 'Respondiste todas las preguntas.<br>Una vez enviado no podrás modificar tus respuestas.',
    showCancelButton:  true,
    confirmButtonText: 'Sí, finalizar',
    cancelButtonText:  'Seguir respondiendo',
    confirmButtonColor: '#6366f1',
    cancelButtonColor:  '#6b7280',
  });

  if (result.isConfirmed) {
    await submitExam();
  }
};

const submitExam = async () => {
  if (isSubmitting.value) return;

  isSubmitting.value = true;
  stopHeartbeat();
  stopCountdown();

  router.post(
    route('attempts.complete', props.attempt.id),
    {},
    {
      onError: () => {
        isSubmitting.value = false;
        startHeartbeat();
        startCountdown();
        Swal.fire({
          icon:  'error',
          title: 'Error al enviar',
          text:  'No pudimos procesar tu examen. Intentá nuevamente.',
          confirmButtonColor: '#ef4444',
        });
      },
    }
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// Ciclo de vida
// ─────────────────────────────────────────────────────────────────────────────
onMounted(() => {
  startCountdown();
  startHeartbeat();

  // Si el intento fue retomado con respuestas previas, navegar a la página
  // donde está la primera pregunta sin responder
  if (props.attempt.is_resumed && answeredCount.value > 0) {
    const firstUnansweredIndex = props.questions.findIndex(
      q => !Object.prototype.hasOwnProperty.call(answers.value, q.id)
    );
    if (firstUnansweredIndex >= 0) {
      currentPage.value = Math.floor(firstUnansweredIndex / QUESTIONS_PER_PAGE);
    }

    Swal.fire({
      icon:              'info',
      title:             'Intento retomado',
      html:              `Continuás desde donde lo dejaste.<br>
                          <strong>${answeredCount.value}</strong> de
                          <strong>${totalQuestions.value}</strong>
                          preguntas ya respondidas.`,
      confirmButtonText: 'Continuar',
      confirmButtonColor: '#6366f1',
      timer:             4000,
      timerProgressBar:  true,
    });
  }
});

onUnmounted(() => {
  stopHeartbeat();
  stopCountdown();
});
</script>

<style scoped>
/* Transición al avanzar → entra desde la derecha */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-left-enter-from {
  opacity: 0;
  transform: translateX(40px);
}
.slide-left-leave-to {
  opacity: 0;
  transform: translateX(-40px);
}

/* Transición al retroceder → entra desde la izquierda */
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-right-enter-from {
  opacity: 0;
  transform: translateX(-40px);
}
.slide-right-leave-to {
  opacity: 0;
  transform: translateX(40px);
}
</style>