<template>
  <div>
    <!-- Bloque de código con la plantilla -->
    <div class="bg-gray-900 text-green-300 p-4 rounded-lg overflow-auto max-h-96 relative">
      <pre class="text-xs leading-relaxed">{{ plantilla }}</pre>

      <!-- Badge del tipo activo -->
      <span class="absolute top-2 right-2 text-[10px] bg-gray-700 text-gray-300 px-2 py-0.5 rounded font-mono">
        {{ tipo }}
      </span>
    </div>

    <!-- Acciones -->
    <div class="mt-3 flex items-center justify-between">
      <p class="text-sm text-gray-500 max-w-sm">
        Copiá esta plantilla, pedile a una IA que genere el JSON respetando la estructura exacta, y pegalo en el siguiente paso.
      </p>
      <button
        @click="copiar"
        :class="copiado ? 'bg-green-100 text-green-700 border-green-300' : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200'"
        class="shrink-0 ml-4 text-sm border rounded-lg px-4 py-2 transition-colors flex items-center gap-1"
      >
        {{ copiado ? '✅ Copiado' : '📋 Copiar plantilla' }}
      </button>
    </div>

    <!-- Instrucción adicional -->
    <div class="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-lg text-xs text-blue-700">
      <strong>Instrucción para la IA:</strong> "Genera un JSON con <em>[N] preguntas</em> sobre <em>[TEMA]</em>
      respetando exactamente esta estructura. Responde solo con el JSON, sin texto adicional ni bloques markdown."
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tipo: String,
});

// Estado visual del botón copiar
const copiado = ref(false);

const plantilla = computed(() => {
    const templates = {
        single_choice: `{
  "exam": {
    "id": "string_unico",
    "title": "Título de la evaluación",
    "version": "2024",
    "passingScore": 80,
    "timeLimitMinutes": 45,
    "shuffleQuestions": true
  },
  "questions": [
    {
      "id": "Q1",
      "type": "single_choice",
      "question": "Texto de la pregunta",
      "options": [
        { "id": "A", "text": "Opción A" },
        { "id": "B", "text": "Opción B" },
        { "id": "C", "text": "Opción C" }
      ],
      "correctAnswer": "B",
      "category": "categoria_tematica",
      "difficulty": "baja",
      "critical": false,
      "explanation": "Explicación de por qué B es correcta"
    }
  ]
}`,
        multiple_choice: `{
  "exam": {
    "id": "string_unico",
    "title": "Título de la evaluación",
    "version": "2024",
    "passingScore": 75,
    "timeLimitMinutes": 60,
    "shuffleQuestions": true,
    "allowPartialScore": true
  },
  "questions": [
    {
      "id": "Q1",
      "type": "multiple_choice",
      "question": "¿Cuáles de los siguientes son correctos?",
      "options": [
        { "id": "A", "text": "Opción A" },
        { "id": "B", "text": "Opción B" },
        { "id": "C", "text": "Opción C" },
        { "id": "D", "text": "Opción D" }
      ],
      "correctAnswer": ["A", "C"],
      "category": "categoria_tematica",
      "difficulty": "media",
      "critical": false,
      "explanation": "A y C son correctas porque..."
    }
  ]
}`,
        true_false: `{
  "exam": {
    "id": "string_unico",
    "title": "Título de la evaluación",
    "version": "2024",
    "passingScore": 70,
    "timeLimitMinutes": 30,
    "shuffleQuestions": true
  },
  "questions": [
    {
      "id": "Q1",
      "type": "true_false",
      "question": "Afirmación que debe evaluarse como verdadera o falsa",
      "correctAnswer": true,
      "category": "categoria_tematica",
      "difficulty": "baja",
      "critical": false,
      "explanation": "Es verdadera porque..."
    }
  ]
}`,
        ordering: `{
  "exam": {
    "id": "string_unico",
    "title": "Título de la evaluación",
    "version": "2024",
    "passingScore": 80,
    "timeLimitMinutes": 40,
    "shuffleQuestions": true,
    "allowPartialScore": true
  },
  "questions": [
    {
      "id": "Q1",
      "type": "ordering",
      "question": "Ordena los siguientes pasos correctamente",
      "options": [
        { "id": "A", "text": "Paso A" },
        { "id": "B", "text": "Paso B" },
        { "id": "C", "text": "Paso C" },
        { "id": "D", "text": "Paso D" }
      ],
      "correctAnswer": ["C", "A", "D", "B"],
      "category": "categoria_tematica",
      "difficulty": "alta",
      "critical": false,
      "explanation": "El orden correcto es C → A → D → B porque..."
    }
  ]
}`,
        matching: `{
  "exam": {
    "id": "string_unico",
    "title": "Título de la evaluación",
    "version": "2024",
    "passingScore": 80,
    "timeLimitMinutes": 40,
    "shuffleQuestions": false
  },
  "questions": [
    {
      "id": "Q1",
      "type": "matching",
      "question": "Relaciona cada elemento con su definición correcta",
      "leftColumn": [
        { "id": "L1", "text": "Concepto 1" },
        { "id": "L2", "text": "Concepto 2" },
        { "id": "L3", "text": "Concepto 3" }
      ],
      "rightColumn": [
        { "id": "R1", "text": "Definición 1" },
        { "id": "R2", "text": "Definición 2" },
        { "id": "R3", "text": "Definición 3" }
      ],
      "correctAnswer": {
        "L1": "R3",
        "L2": "R1",
        "L3": "R2"
      },
      "category": "categoria_tematica",
      "difficulty": "media",
      "critical": false,
      "explanation": "La relación correcta es porque..."
    }
  ]
}`,
    };

    return (
        templates[props.tipo] ||
        'Seleccioná un tipo de evaluación en el paso anterior para ver la plantilla correspondiente.'
    );
});

// CORRECCIÓN: reemplaza alert() nativo por SweetAlert toast no bloqueante
const copiar = async () => {
    try {
        await navigator.clipboard.writeText(plantilla.value);

        // Feedback visual en el botón
        copiado.value = true;
        setTimeout(() => { copiado.value = false; }, 2500);

        // Toast SweetAlert no bloqueante
        Swal.fire({
            icon:              'success',
            title:             '¡Copiado!',
            text:              'Plantilla copiada al portapapeles. Pegala en tu IA favorita.',
            toast:             true,
            position:          'top-end',
            showConfirmButton: false,
            timer:             2500,
            timerProgressBar:  true,
        });
    } catch {
        Swal.fire({
            icon:  'error',
            title: 'No se pudo copiar',
            text:  'Tu navegador no permite copiar automáticamente. Seleccioná el texto manualmente.',
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            confirmButtonText: 'OK',
        });
    }
};
</script>