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
  console.log('LineChart data:', props.data)
  console.log('LineChart options:', props.options)
  
  // Simulação visual básica por enquanto
  const ctx = chartCanvas.value.getContext('2d')
  ctx.fillStyle = '#e5e7eb'
  ctx.fillRect(0, 0, chartCanvas.value.width, chartCanvas.value.height)
  
  ctx.fillStyle = '#374151'
  ctx.font = '16px Arial'
  ctx.textAlign = 'center'
  ctx.fillText('Gráfico de Linha', chartCanvas.value.width / 2, chartCanvas.value.height / 2)
  ctx.fillText('(Chart.js será integrado)', chartCanvas.value.width / 2, chartCanvas.value.height / 2 + 25)
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