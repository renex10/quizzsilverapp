<!-- resources/js/Components/Common/Stats/LineChart.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="text-base font-semibold text-gray-800 mb-4">{{ title }}</h3>

    <div v-if="hasData">
      <Line :data="chartData" :options="chartOptions" :height="height" />
    </div>

    <div v-else class="flex items-center justify-center text-gray-400 text-sm rounded-lg
                       bg-gray-50" :style="{ height: height + 'px' }">
      Sin datos suficientes para mostrar el gráfico
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
  title:         { type: String,  required: true },
  labels:        { type: Array,   required: true },
  datasets:      { type: Array,   required: true },
  height:        { type: Number,  default: 280 },
  yMin:          { type: Number,  default: 0 },
  yMax:          { type: Number,  default: 100 },
  yLabel:        { type: String,  default: 'Porcentaje (%)' },
  // Valor de línea de referencia horizontal (ej: 80 para puntaje de aprobación)
  referenceLine: { type: Number,  default: null },
});

const hasData = computed(() => props.labels.length > 0);

const chartData = computed(() => ({
  labels:   props.labels,
  datasets: props.datasets,
}));

const chartOptions = computed(() => ({
  responsive:          true,
  maintainAspectRatio: false,
  plugins: {
    legend:  { position: 'top', labels: { boxWidth: 12, font: { size: 12 } } },
    tooltip: { mode: 'index', intersect: false },
    // Línea de referencia con annotation plugin (opcional — no requiere plugin extra)
  },
  scales: {
    x: {
      grid:  { display: false },
      ticks: { font: { size: 11 }, maxRotation: 45 },
    },
    y: {
      min:   props.yMin,
      max:   props.yMax,
      title: { display: true, text: props.yLabel, font: { size: 12 } },
      ticks: { font: { size: 11 } },
      // Línea de referencia como gridLine destacada
      grid: {
        color: (ctx) => {
          if (props.referenceLine !== null && ctx.tick.value === props.referenceLine) {
            return 'rgba(239, 68, 68, 0.5)'; // rojo suave para la línea de aprobación
          }
          return 'rgba(0,0,0,0.06)';
        },
        lineWidth: (ctx) => {
          if (props.referenceLine !== null && ctx.tick.value === props.referenceLine) {
            return 2;
          }
          return 1;
        },
      },
    },
  },
  interaction: { mode: 'nearest', axis: 'x', intersect: false },
}));
</script>