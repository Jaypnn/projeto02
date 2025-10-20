<template>
  <div class="w-full h-full">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'

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

// Placeholder para Chart.js - será implementado quando a biblioteca for instalada
const createChart = async () => {
  if (!chartCanvas.value) return
  
  // TODO: Implementar Chart.js quando instalado
  console.log('DoughnutChart data:', props.data)
  console.log('DoughnutChart options:', props.options)
  
  // Simulação visual básica por enquanto
  const ctx = chartCanvas.value.getContext('2d')
  ctx.fillStyle = '#e5e7eb'
  ctx.fillRect(0, 0, chartCanvas.value.width, chartCanvas.value.height)
  
  // Desenha um círculo simples
  const centerX = chartCanvas.value.width / 2
  const centerY = chartCanvas.value.height / 2
  const radius = Math.min(centerX, centerY) * 0.6
  
  ctx.beginPath()
  ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI)
  ctx.fillStyle = '#9ca3af'
  ctx.fill()
  
  ctx.beginPath()
  ctx.arc(centerX, centerY, radius * 0.5, 0, 2 * Math.PI)
  ctx.fillStyle = '#e5e7eb'
  ctx.fill()
  
  ctx.fillStyle = '#374151'
  ctx.font = '14px Arial'
  ctx.textAlign = 'center'
  ctx.fillText('Gráfico de Rosca', centerX, centerY - 5)
  ctx.fillText('(Chart.js será integrado)', centerX, centerY + 15)
}

const updateChart = () => {
  if (chartInstance) {
    // chartInstance.destroy()
  }
  createChart()
}

watch(() => props.data, updateChart, { deep: true })
watch(() => props.options, updateChart, { deep: true })

onMounted(async () => {
  await nextTick()
  createChart()
})
</script>