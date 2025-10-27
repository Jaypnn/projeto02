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
  const labels = props.data.map(d => d.name)
  const values = props.data.map(d => Number(d.value || 0))
  const colors = props.data.map(d => d.color || '#3b82f6')

  return {
    type: 'doughnut',
    data: {
      labels,
      datasets: [
        {
          label: 'Gastos por categoria',
          data: values,
          backgroundColor: colors,
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const label = ctx.label || ''
              const v = ctx.parsed || 0
              return `${label}: ${v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`
            }
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
  if (chartInstance) chartInstance.destroy()
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