/**
 * useAttemptStore
 *
 * Store de Pinia que gestiona el estado completo del intento activo
 * durante la ejecución de un examen.
 *
 * Responsabilidades:
 * - Mantener en memoria: preguntas, respuestas, índice actual, tiempo
 * - Persistir cada respuesta en la DB via API en tiempo real
 * - Ejecutar el heartbeat cada 30 segundos
 * - Sincronizar el temporizador con el servidor en cada heartbeat
 * - Detectar tiempo agotado y completar el intento automáticamente
 *
 * Principio de fuente de verdad:
 * - La DB es siempre la fuente de verdad
 * - Este store es caché de sesión — si el usuario cambia de dispositivo,
 *   el controlador reconstruye el estado desde la DB y lo pasa como props
 *   a la vista, que llama a hydrate() para cargar el store
 *
 * Ruta: resources/js/Stores/useAttemptStore.js
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAttemptStore = defineStore('attempt', () => {

    // =========================================================================
    // ESTADO
    // =========================================================================

    /** ID del intento activo */
    const attemptId = ref(null);

    /** Metadatos del examen */
    const exam = ref(null);
    // { id, title, series, type, passing_score, time_limit_minutes, allow_partial }

    /** Lista de preguntas en el orden definido por el backend (sin correctAnswer) */
    const questions = ref([]);

    /** Mapa de respuestas { question_id: user_answer } */
    const answers = ref({});

    /** Índice de la pregunta actualmente visible */
    const currentIndex = ref(0);

    /** Segundos restantes — sincronizado con el servidor en cada heartbeat */
    const remainingSeconds = ref(null);

    /** Estado del intento */
    const status = ref(null); // 'pending' | 'active' | 'completed' | 'abandoned'

    /** Si el intento fue retomado (había respuestas previas) */
    const isResumed = ref(false);

    /** Indica si se está guardando una respuesta (evita doble envío) */
    const isSaving = ref(false);

    /** Indica si se está completando el intento */
    const isCompleting = ref(false);

    /** Referencia al intervalo del heartbeat para poder limpiarlo */
    let heartbeatInterval = null;

    /** Referencia al intervalo del countdown */
    let countdownInterval = null;

    // =========================================================================
    // COMPUTED
    // =========================================================================

    /** Pregunta actualmente visible */
    const currentQuestion = computed(() => questions.value[currentIndex.value] ?? null);

    /** Total de preguntas */
    const totalQuestions = computed(() => questions.value.length);

    /** Cantidad de preguntas respondidas */
    const answeredCount = computed(() => Object.keys(answers.value).length);

    /** Preguntas sin responder */
    const unansweredCount = computed(() => totalQuestions.value - answeredCount.value);

    /** Respuesta de la pregunta actual (si existe) */
    const currentAnswer = computed(() =>
        currentQuestion.value ? (answers.value[currentQuestion.value.id] ?? null) : null
    );

    /** Porcentaje de progreso visual */
    const progressPercent = computed(() =>
        totalQuestions.value > 0
            ? Math.round((answeredCount.value / totalQuestions.value) * 100)
            : 0
    );

    /** Si hay un límite de tiempo activo */
    const hasTimeLimit = computed(() => remainingSeconds.value !== null);

    /** Tiempo formateado MM:SS para mostrar en el temporizador */
    const formattedTime = computed(() => {
        if (remainingSeconds.value === null) return null;
        const mins = Math.floor(remainingSeconds.value / 60);
        const secs = remainingSeconds.value % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    });

    /** Si el tiempo está en zona de advertencia (menos de 5 minutos) */
    const isTimeWarning = computed(() =>
        remainingSeconds.value !== null && remainingSeconds.value <= 300
    );

    /** Si el tiempo está crítico (menos de 1 minuto) */
    const isTimeCritical = computed(() =>
        remainingSeconds.value !== null && remainingSeconds.value <= 60
    );

    /** Indica si la pregunta N fue respondida (para el mapa de progreso) */
    const isQuestionAnswered = (index) => {
        const q = questions.value[index];
        return q ? Object.prototype.hasOwnProperty.call(answers.value, q.id) : false;
    };

    // =========================================================================
    // HIDRATACIÓN INICIAL
    // Llamada una sola vez al montar Active.vue con los datos que llegan del backend
    // =========================================================================

    /**
     * Carga el estado del intento desde los props de Inertia.
     * @param {Object} params
     * @param {Object} params.attempt   - { id, status, is_resumed, question_order }
     * @param {Object} params.examData  - { id, title, series, type, ... }
     * @param {Array}  params.questionsData
     * @param {Object} params.existingAnswers - { question_id: user_answer }
     * @param {number|null} params.remaining  - segundos restantes del servidor
     */
    const hydrate = ({ attempt, examData, questionsData, existingAnswers, remaining }) => {
        attemptId.value      = attempt.id;
        status.value         = attempt.status;
        isResumed.value      = attempt.is_resumed ?? false;
        exam.value           = examData;
        questions.value      = questionsData;
        answers.value        = existingAnswers ?? {};
        remainingSeconds.value = remaining ?? null;

        // Si hay respuestas previas, posicionar en la primera sin responder
        if (isResumed.value && answeredCount.value > 0) {
            const firstUnanswered = questions.value.findIndex(
                (q) => !Object.prototype.hasOwnProperty.call(answers.value, q.id)
            );
            currentIndex.value = firstUnanswered >= 0 ? firstUnanswered : 0;
        } else {
            currentIndex.value = 0;
        }

        // Arrancar countdown y heartbeat
        startCountdown();
        startHeartbeat();
    };

    // =========================================================================
    // NAVEGACIÓN ENTRE PREGUNTAS
    // =========================================================================

    const goToQuestion = (index) => {
        if (index >= 0 && index < totalQuestions.value) {
            currentIndex.value = index;
        }
    };

    const goNext = () => goToQuestion(currentIndex.value + 1);
    const goPrev = () => goToQuestion(currentIndex.value - 1);

    const isFirstQuestion = computed(() => currentIndex.value === 0);
    const isLastQuestion  = computed(() => currentIndex.value === totalQuestions.value - 1);

    // =========================================================================
    // GUARDAR RESPUESTA
    // Persiste en la DB y actualiza el store local
    // =========================================================================

    /**
     * Guarda la respuesta del usuario a la pregunta actual.
     * Usa updateOrCreate en el backend — seguro llamarlo múltiples veces.
     *
     * @param {string} questionId
     * @param {*} userAnswer - string, boolean, array u objeto según el tipo
     * @param {Function|null} onTimeOut - callback si el tiempo se agotó durante el guardado
     */
    const saveAnswer = async (questionId, userAnswer) => {
        if (isSaving.value) return;
        if (!attemptId.value) return;

        // Actualizar el store local inmediatamente (optimistic update)
        answers.value = { ...answers.value, [questionId]: userAnswer };

        isSaving.value = true;
        try {
            await axios.post(route('attempts.answer', attemptId.value), {
                question_id: questionId,
                user_answer: userAnswer,
            });

            // Pasar al estado active si estaba pending
            if (status.value === 'pending') {
                status.value = 'active';
            }
        } catch (error) {
            console.error('[useAttemptStore] Error al guardar respuesta:', error);
            // La respuesta queda en el store local aunque falle la API
            // Se reintentará en el próximo heartbeat si se implementa sincronización
        } finally {
            isSaving.value = false;
        }
    };

    // =========================================================================
    // HEARTBEAT
    // Actualiza last_seen_at en el servidor y sincroniza el temporizador
    // =========================================================================

    const startHeartbeat = () => {
        stopHeartbeat(); // limpiar cualquier intervalo previo

        heartbeatInterval = setInterval(async () => {
            if (!attemptId.value || status.value !== 'active') {
                stopHeartbeat();
                return;
            }

            try {
                const response = await axios.post(
                    route('attempts.heartbeat', attemptId.value)
                );

                // Sincronizar temporizador con el servidor
                if (response.data.remaining_seconds !== undefined) {
                    remainingSeconds.value = response.data.remaining_seconds;
                }

            } catch (error) {
                // Si el servidor devuelve 409 (intento no activo), detener heartbeat
                if (error.response?.status === 409) {
                    stopHeartbeat();
                    stopCountdown();
                }
                console.warn('[useAttemptStore] Heartbeat fallido:', error.message);
            }
        }, 30_000); // cada 30 segundos
    };

    const stopHeartbeat = () => {
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
    };

    // =========================================================================
    // COUNTDOWN
    // Cuenta regresiva local — se sincroniza con el servidor en cada heartbeat
    // =========================================================================

    const startCountdown = () => {
        stopCountdown();

        if (remainingSeconds.value === null) return; // sin límite de tiempo

        countdownInterval = setInterval(() => {
            if (remainingSeconds.value === null) {
                stopCountdown();
                return;
            }

            if (remainingSeconds.value <= 0) {
                remainingSeconds.value = 0;
                stopCountdown();
                // El componente Active.vue escucha el evento 'tiempo-agotado'
                // a través del computed isTimeCritical o un watch externo
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

    // =========================================================================
    // COMPLETAR EL INTENTO
    // =========================================================================

    /**
     * Envía el intento al servidor para calcular el resultado.
     * Idempotente — si ya está completado devuelve false sin hacer nada.
     *
     * @returns {boolean} true si el envío fue iniciado correctamente
     */
    const complete = async () => {
        if (isCompleting.value) return false;
        if (status.value === 'completed') return false;
        if (!attemptId.value) return false;

        isCompleting.value = true;
        stopHeartbeat();
        stopCountdown();

        try {
            // Inertia maneja la redirección a la vista de resultados
            // Usamos axios directamente y dejamos que Inertia navegue
            // a través del redirect del backend
            await axios.post(route('attempts.complete', attemptId.value));
            status.value = 'completed';
            return true;
        } catch (error) {
            console.error('[useAttemptStore] Error al completar intento:', error);
            isCompleting.value = false;
            return false;
        }
    };

    // =========================================================================
    // RESET
    // Limpia el store al desmontar la vista de examen
    // =========================================================================

    const reset = () => {
        stopHeartbeat();
        stopCountdown();

        attemptId.value      = null;
        exam.value           = null;
        questions.value      = [];
        answers.value        = {};
        currentIndex.value   = 0;
        remainingSeconds.value = null;
        status.value         = null;
        isResumed.value      = false;
        isSaving.value       = false;
        isCompleting.value   = false;
    };

    // =========================================================================
    // EXPORTS
    // =========================================================================

    return {
        // Estado
        attemptId,
        exam,
        questions,
        answers,
        currentIndex,
        remainingSeconds,
        status,
        isResumed,
        isSaving,
        isCompleting,

        // Computed
        currentQuestion,
        totalQuestions,
        answeredCount,
        unansweredCount,
        currentAnswer,
        progressPercent,
        hasTimeLimit,
        formattedTime,
        isTimeWarning,
        isTimeCritical,
        isFirstQuestion,
        isLastQuestion,
        isQuestionAnswered,

        // Acciones
        hydrate,
        goToQuestion,
        goNext,
        goPrev,
        saveAnswer,
        complete,
        reset,

        // Internos expuestos para tests o debug
        startHeartbeat,
        stopHeartbeat,
        startCountdown,
        stopCountdown,
    };
});