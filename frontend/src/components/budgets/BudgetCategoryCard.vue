<template>
  <div class="bg-white shadow rounded-lg p-4">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-sm text-gray-500">{{ item.category?.name || 'Categoria' }}</div>
        <div v-if="budgetName" class="text-xs text-gray-400">{{ budgetName }}</div>
        <div class="text-lg font-semibold text-gray-900">Meta: R$ {{ formatCurrency(allocated) }}</div>
      </div>
      <span
        class="text-xs px-2 py-1 rounded-full"
        :class="pacingPill.class"
        :title="pacingPill.title"
      >{{ pacingPill.label }}</span>
    </div>

    <div class="mt-3">
      <div class="flex justify-between text-xs text-gray-600 mb-1">
        <span>Gasto: R$ {{ formatCurrency(spent) }}</span>
        <span>{{ progress.toFixed(0) }}%</span>
      </div>
      <div class="h-2 bg-gray-100 rounded overflow-hidden">
        <div class="h-2" :class="progressColor" :style="{ width: Math.min(100, progress) + '%' }"></div>
      </div>
      <div class="flex items-center justify-between text-xs text-gray-600 mt-2">
        <span>Previsto EOM: <span :class="forecastColor">R$ {{ formatCurrency(forecast) }}</span></span>
        <span>Diferença: <span :class="diffColor">R$ {{ formatCurrency(diff) }}</span></span>
      </div>
    </div>

    <div class="mt-4 flex gap-2">
      <button
        @click="$emit('edit-goal', { budgetCategoryId: item.id, currentAllocated: allocated })"
        class="text-xs inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50"
      >Editar meta</button>
      <button
        @click="$emit('view-transactions', item.category?.id)"
        class="text-xs inline-flex items-center px-2.5 py-1.5 border border-gray-300 rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50"
      >Ver transações</button>
      <button
        @click="$emit('delete-budget')"
        class="text-xs inline-flex items-center px-2.5 py-1.5 border border-rose-200 rounded-md shadow-sm text-rose-700 bg-white hover:bg-rose-50"
      >Excluir orçamento</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  budgetId: { type: [String, Number], required: true },
  item: { type: Object, required: true },
  dateRange: { type: Object, required: true },
  budgetName: { type: String, default: '' }
})

const allocated = computed(() => toNumber(props.item?.allocated_amount))
const spent = computed(() => toNumber(props.item?.spent_amount))
const progress = computed(() => allocated.value > 0 ? (spent.value / allocated.value) * 100 : 0)

const elapsed = computed(() => getElapsedRatio(props.dateRange.start, props.dateRange.end))
const expected = computed(() => allocated.value * elapsed.value)
const forecast = computed(() => elapsed.value > 0 ? (spent.value / elapsed.value) : spent.value)
const diff = computed(() => allocated.value - forecast.value)

const progressColor = computed(() => progress.value < 75 ? 'bg-green-500' : (progress.value < 95 ? 'bg-yellow-500' : 'bg-red-500'))
const forecastColor = computed(() => forecast.value <= allocated.value ? 'text-green-600' : 'text-red-600')
const diffColor = computed(() => diff.value >= 0 ? 'text-green-600' : 'text-red-600')

const pacingPill = computed(() => {
  const tol = Math.max(10, allocated.value * 0.05)
  const delta = spent.value - expected.value
  if (delta <= tol * -1) return { label: 'Abaixo do ritmo', class: 'bg-green-100 text-green-700', title: 'Gastos abaixo do ritmo esperado' }
  if (Math.abs(delta) <= tol) return { label: 'No ritmo', class: 'bg-yellow-100 text-yellow-700', title: 'Gastos dentro da faixa esperada' }
  return { label: 'Acima do ritmo', class: 'bg-red-100 text-red-700', title: 'Gastos acima do ritmo esperado' }
})

function toNumber(n){ return typeof n === 'number' ? n : Number(n || 0) }
function formatCurrency(n){ return (Number(n)||0).toFixed(2).replace('.', ',') }
function daysBetween(a,b){ const ms=b-a; return Math.floor(ms/86400000)+1 }
function getElapsedRatio(start, end){
  const today = new Date();
  const total = daysBetween(new Date(start.getFullYear(), start.getMonth(), start.getDate()), new Date(end.getFullYear(), end.getMonth(), end.getDate()))
  if (today < start) return 0
  const capped = today > end ? end : today
  const elapsed = daysBetween(new Date(start.getFullYear(), start.getMonth(), start.getDate()), new Date(capped.getFullYear(), capped.getMonth(), capped.getDate()))
  return Math.min(1, Math.max(0, elapsed / total))
}
</script>

<style scoped>
</style>