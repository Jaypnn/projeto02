<template>
  <div class="w-full h-full">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { Chart } from 'chart.js/auto'

const props = defineProps({
  data: {
    type: Array,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  }
})

const chartCanvas = ref(null)
let chartInstance = null

const buildConfig = () => {
  const labels = props.data.map(d => d.label ?? d.date)
  const income = props.data.map(d => Number(d.income || 0))
  const expenses = props.data.map(d => Number(d.expenses || 0))

  return {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Receitas',
          data: income,
          tension: 0.3,
          fill: false,
          borderColor: '#22c55e',
          backgroundColor: '#22c55e',
          pointRadius: 2
        },
        {
          label: 'Despesas',
          data: expenses,
          tension: 0.3,
          fill: false,
          borderColor: '#ef4444',
          backgroundColor: '#ef4444',
          pointRadius: 2
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: true },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const v = ctx.parsed.y || 0
              return `${ctx.dataset.label}: ${v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`
            }
          }
        }
      },
      scales: {
        x: { ticks: { autoSkip: true, maxTicksLimit: 10 } },
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) =>
              Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
          }
        }
      },
      ...props.options
    }
  }
}

const createChart = () => {
  if (!chartCanvas.value) return
  const ctx = chartCanvas.value.getContext('2d')
  if (chartInstance) {
    chartInstance.destroy()
  }
  chartInstance = new Chart(ctx, buildConfig())
}

const updateChart = () => {
  if (!chartInstance) return createChart()
  const cfg = buildConfig()
  chartInstance.data = cfg.data
  chartInstance.options = cfg.options
  chartInstance.update()
}

watch(() => props.data, updateChart, { deep: true })
watch(() => props.options, updateChart, { deep: true })

onMounted(async () => {
  await nextTick()
  createChart()
})

onBeforeUnmount(() => {
  if (chartInstance) chartInstance.destroy()
})
</script>