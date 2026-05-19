<!--
=============================================================================
  TopicIconPicker.vue
  Ubicación: resources/js/Components/Admin/Topics/TopicIconPicker.vue
=============================================================================

  ¿QUÉ HACE ESTE COMPONENTE?
  ──────────────────────────
  Permite al administrador elegir dos elementos visuales del topic:

    1. ÍCONO → un emoji que representa el tema visualmente.
               Aparece en la card del catálogo, en la landing pública
               y en el sidebar del estudiante.

    2. COLOR → el color principal de la card del topic.
               Se usa como franja lateral en TopicCard, como fondo
               suave del ícono y como acento visual en la landing.

  El componente muestra una PREVIEW EN TIEMPO REAL de cómo se verá
  la card del topic con el ícono y color seleccionados, antes de guardar.
  Esto le da al admin confianza visual inmediata sin tener que imaginar
  el resultado final.

  CÓMO FUNCIONA EL SELECTOR DE ÍCONOS:
  ──────────────────────────────────────
  Muestra una grilla de emojis predefinidos agrupados por categoría.
  El admin puede:
    a) Hacer click en uno de los emojis de la grilla
    b) Escribir manualmente cualquier emoji en el input de texto

  Los emojis predefinidos cubren los dominios más comunes de la plataforma:
  seguridad vial, educación, ciencias, salud, tecnología, etc.
  No se limita a esos — el input libre permite cualquier emoji Unicode.

  CÓMO FUNCIONA EL SELECTOR DE COLOR:
  ─────────────────────────────────────
  Muestra una paleta de colores predefinidos armónicos con el diseño
  de la plataforma. También incluye un input de color nativo (type="color")
  para elegir cualquier color personalizado.

  PROPS QUE RECIBE:
  ─────────────────
    icon  → string | null — el emoji actualmente seleccionado
    color → string        — el color hex actualmente seleccionado

  EMITS:
  ──────
    update:icon  → cuando el admin elige o escribe un nuevo emoji
    update:color → cuando el admin elige un nuevo color

  DEPENDENCIAS:
  ─────────────
    • Vue 3 Composition API
    • Tailwind CSS
    • Sin librerías externas — emojis y colores son valores estáticos
=============================================================================
-->

<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

    <!-- Encabezado de la sección -->
    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60">
      <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
        <span
          class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center
                 justify-center text-xs font-bold"
        >2</span>
        Identidad visual
      </h3>
      <p class="text-xs text-gray-500 mt-0.5 ml-8">
        Ícono y color que representan el topic en la plataforma
      </p>
    </div>

    <div class="px-5 py-5 space-y-5">

      <!-- ── Preview de la card ─────────────────────────────────────────── -->
      <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
        <p class="text-xs text-gray-400 shrink-0">Vista previa:</p>
        <!-- Mini card preview -->
        <div
          class="flex items-center gap-2.5 px-3 py-2 bg-white rounded-xl border
                 shadow-sm flex-1 min-w-0 relative overflow-hidden"
        >
          <!-- Franja de color izquierda -->
          <div
            class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-xl"
            :style="{ backgroundColor: color }"
          ></div>
          <!-- Ícono con fondo suave -->
          <div
            class="ml-1 shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-base"
            :style="{ backgroundColor: color + '20' }"
          >
            {{ icon || '📄' }}
          </div>
          <span class="text-xs font-medium text-gray-700 truncate">
            Nombre del topic
          </span>
        </div>
      </div>

      <!-- ── Selector de ícono ──────────────────────────────────────────── -->
      <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700">
          Ícono
        </label>

        <!-- Input de emoji libre -->
        <div class="flex items-center gap-2">
          <div class="relative">
            <input
              type="text"
              :value="icon"
              @input="$emit('update:icon', $event.target.value)"
              placeholder="📄"
              maxlength="4"
              class="w-16 h-10 text-center text-xl border border-gray-300 rounded-xl
                     focus:outline-none focus:ring-2 focus:ring-indigo-200
                     focus:border-indigo-400 transition-all duration-150"
            />
          </div>
          <p class="text-xs text-gray-400 flex-1">
            Hacé click en uno de los íconos de abajo o escribí cualquier emoji
          </p>
        </div>

        <!-- Grilla de emojis predefinidos por categoría -->
        <div class="space-y-3">
          <div
            v-for="grupo in emojiGroups"
            :key="grupo.label"
            class="space-y-1.5"
          >
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">
              {{ grupo.label }}
            </p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="emoji in grupo.emojis"
                :key="emoji"
                type="button"
                @click="$emit('update:icon', emoji)"
                class="w-9 h-9 flex items-center justify-center text-lg rounded-lg
                       border transition-all duration-150 hover:scale-110"
                :class="icon === emoji
                  ? 'border-indigo-400 bg-indigo-50 scale-110 shadow-sm'
                  : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                :title="emoji"
              >
                {{ emoji }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Separador -->
      <div class="border-t border-gray-100"></div>

      <!-- ── Selector de color ──────────────────────────────────────────── -->
      <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700">
          Color de la card
        </label>

        <!-- Paleta de colores predefinidos -->
        <div class="flex flex-wrap gap-2">
          <button
            v-for="preset in colorPresets"
            :key="preset.value"
            type="button"
            @click="$emit('update:color', preset.value)"
            class="w-8 h-8 rounded-lg border-2 transition-all duration-150
                   hover:scale-110 relative"
            :style="{ backgroundColor: preset.value }"
            :class="color === preset.value
              ? 'border-gray-800 scale-110 shadow-md'
              : 'border-transparent'"
            :title="preset.label"
          >
            <!-- Checkmark para el color seleccionado -->
            <svg
              v-if="color === preset.value"
              class="w-4 h-4 absolute inset-0 m-auto text-white drop-shadow"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                d="M5 13l4 4L19 7"/>
            </svg>
          </button>

          <!-- Input de color personalizado -->
          <label
            class="w-8 h-8 rounded-lg border-2 border-dashed border-gray-300
                   flex items-center justify-center cursor-pointer hover:border-gray-400
                   transition-colors relative overflow-hidden"
            title="Elegir color personalizado"
          >
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v16m8-8H4"/>
            </svg>
            <input
              type="color"
              :value="color"
              @input="$emit('update:color', $event.target.value)"
              class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
            />
          </label>
        </div>

        <!-- Valor hex del color seleccionado -->
        <div class="flex items-center gap-2">
          <div
            class="w-5 h-5 rounded-md border border-gray-200 shrink-0"
            :style="{ backgroundColor: color }"
          ></div>
          <span class="text-xs font-mono text-gray-500">{{ color }}</span>
          <button
            v-if="color !== colorDefault"
            type="button"
            @click="$emit('update:color', colorDefault)"
            class="text-xs text-gray-400 hover:text-gray-600 underline
                   underline-offset-2 transition-colors"
          >
            Restablecer
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
// ─── Props ────────────────────────────────────────────────────────────────────

defineProps({
  /**
   * El emoji actualmente seleccionado.
   * Puede ser null (sin ícono) — en ese caso la preview muestra 📄
   */
  icon: {
    type: String,
    default: null,
  },

  /**
   * El color hex actualmente seleccionado.
   * Siempre debe tener un valor — el default es el indigo de la plataforma.
   */
  color: {
    type: String,
    default: '#6366f1',
  },
});

// ─── Emits ────────────────────────────────────────────────────────────────────

defineEmits(['update:icon', 'update:color']);

// ─── Constantes ───────────────────────────────────────────────────────────────

/** Color por defecto de la plataforma — se usa en el botón "Restablecer" */
const colorDefault = '#6366f1';

/**
 * Emojis predefinidos agrupados por dominio temático.
 * Cubren los casos de uso más comunes de la plataforma.
 * El admin siempre puede escribir manualmente cualquier otro emoji.
 */
const emojiGroups = [
  {
    label: 'Tránsito y vehículos',
    emojis: ['🚦', '🚗', '🚙', '🚕', '🏎️', '🚓', '🚑', '🚒', '🛻', '🚧',
             '⛽', '🗺️', '🛣️', '🪧', '⚠️', '🔧', '🔩', '⚙️'],
  },
  {
    label: 'Educación y conocimiento',
    emojis: ['📚', '📖', '📝', '✏️', '🎓', '🏫', '💡', '🔬', '🧪', '🧬',
             '📐', '📏', '🖊️', '📋', '📊', '📈', '🗂️'],
  },
  {
    label: 'Salud y ciencias',
    emojis: ['🏥', '💊', '🩺', '🩻', '🧠', '❤️', '🫀', '🫁', '🦷', '🦴',
             '🩹', '🩸', '💉', '🔭', '⚗️'],
  },
  {
    label: 'Tecnología y digital',
    emojis: ['💻', '🖥️', '📱', '⌨️', '🖱️', '💾', '📡', '🔐', '🛡️', '🤖',
             '⚡', '🔋', '📶', '🌐', '🔗'],
  },
  {
    label: 'Naturaleza y medio ambiente',
    emojis: ['🌿', '🌱', '🌍', '☀️', '🌊', '🌬️', '♻️', '🌳', '🦋', '🐾'],
  },
  {
    label: 'General',
    emojis: ['⭐', '🎯', '🏆', '🎖️', '✅', '❓', '💬', '📣', '🔔', '📌',
             '🏷️', '🧩', '🎲', '🗝️', '🔑'],
  },
];

/**
 * Paleta de colores predefinidos armónicos con el diseño de la plataforma.
 * Se eligieron 12 colores que funcionan bien como acentos visuales
 * y tienen suficiente contraste con texto blanco y gris.
 */
const colorPresets = [
  { value: '#6366f1', label: 'Índigo (default)' },
  { value: '#8b5cf6', label: 'Violeta' },
  { value: '#a855f7', label: 'Púrpura' },
  { value: '#ec4899', label: 'Rosa' },
  { value: '#ef4444', label: 'Rojo' },
  { value: '#f97316', label: 'Naranja' },
  { value: '#f59e0b', label: 'Ámbar' },
  { value: '#eab308', label: 'Amarillo' },
  { value: '#22c55e', label: 'Verde' },
  { value: '#10b981', label: 'Esmeralda' },
  { value: '#14b8a6', label: 'Teal' },
  { value: '#3b82f6', label: 'Azul' },
  { value: '#0ea5e9', label: 'Celeste' },
  { value: '#64748b', label: 'Gris pizarra' },
];
</script>