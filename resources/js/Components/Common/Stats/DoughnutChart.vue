<!-- resources/js/Components/Common/Stats/DoughnutChart.vue -->
<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="text-base font-semibold text-gray-800 mb-4">{{ title }}</h3>

    <div v-if="hasData" class="flex items-center justify-center">
      <div style="max-width: 280px; width: 100%;">
        <Doughnut :data="chartData" :options="chartOptions" :height="height" />
      </div>
    </div>

    <div v-else class="flex items-center justify-center text-gray-400 text-sm
                       rounded-lg bg-gray-50" :style="{ height: height + 'px' }">
      Sin datos suficientes
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
  title:    { type: String, required: true },
  labels:   { type: Array,  required: true },
  datasets: { type: Array,  required: true },
  height:   { type: Number, default: 240 },
});

const hasData = computed(() =>
  props.datasets?.[0]?.data?.some(v => v > 0) ?? false
);

const chartData = computed(() => ({
  labels:   props.labels,
  datasets: props.datasets,
}));

const chartOptions = computed(() => ({
  responsive:          true,
  maintainAspectRatio: true,
  cutout:              '65%',
  plugins: {
    legend: {
      position: 'bottom',
      labels:   { boxWidth: 12, padding: 16, font: { size: 12 } },
    },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.label}: ${ctx.parsed} (${
          Math.round((ctx.parsed / ctx.dataset.data.reduce((a, b) => a + b, 0)) * 100)
        }%)`,
      },
    },
  },
}));
</script>