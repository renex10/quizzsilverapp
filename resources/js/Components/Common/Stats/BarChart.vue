<!-- resources/js/Components/Common/Stats/BarChart.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="text-base font-semibold text-gray-800 mb-4">{{ title }}</h3>

    <div v-if="hasData">
      <Bar :data="chartData" :options="chartOptions" :height="height" />
    </div>

    <div v-else class="flex items-center justify-center text-gray-400 text-sm
                       rounded-lg bg-gray-50" :style="{ height: height + 'px' }">
      Sin datos suficientes
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  title:      { type: String,  required: true },
  labels:     { type: Array,   required: true },
  datasets:   { type: Array,   required: true },
  height:     { type: Number,  default: 280 },
  yMin:       { type: Number,  default: 0 },
  yMax:       { type: Number,  default: 100 },
  yLabel:     { type: String,  default: 'Porcentaje (%)' },
  horizontal: { type: Boolean, default: false },
});

const hasData = computed(() => props.labels.length > 0);

const chartData = computed(() => ({
  labels:   props.labels,
  datasets: props.datasets,
}));

const chartOptions = computed(() => ({
  responsive:          true,
  maintainAspectRatio: false,
  indexAxis:           props.horizontal ? 'y' : 'x',
  plugins: {
    legend:  { position: 'top', labels: { boxWidth: 12, font: { size: 12 } } },
    tooltip: { mode: 'index', intersect: false },
  },
  scales: {
    x: {
      grid:  { display: props.horizontal },
      ticks: { font: { size: 11 } },
      ...(props.horizontal ? { min: props.yMin, max: props.yMax } : {}),
    },
    y: {
      grid:  { display: !props.horizontal, color: 'rgba(0,0,0,0.06)' },
      ticks: { font: { size: 11 } },
      ...(!props.horizontal ? { min: props.yMin, max: props.yMax,
        title: { display: true, text: props.yLabel } } : {}),
    },
  },
}));
</script>