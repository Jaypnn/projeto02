<template>
  <div>
    <div v-if="loading" class="bg-white shadow rounded-lg p-6">
      <div class="animate-pulse space-y-4">
        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="h-24 bg-gray-100 rounded"></div>
        </div>
      </div>
    </div>

  <div v-else-if="!hasData" class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
      <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum orçamento para o período</h3>
      <p class="mb-4">Selecione outro mês ou crie um novo orçamento.</p>
      <button @click="$emit('create-budget')" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm">Criar orçamento</button>
    </div>

  <div v-else class="space-y-4">
      <!-- Pacing banner -->
      <div class="bg-white shadow rounded-lg p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <h3 class="text-base font-medium text-gray-900">
              Ritmo do mês
            </h3>
            <p class="text-sm text-gray-600">
              Você gastou <span class="font-medium">R$ {{ formatCurrency(totalSpent) }}</span> do orçamento do mês, com {{ (elapsed*100).toFixed(0) }}% do período concluído.
            </p>
          </div>
          <div class="w-full md:w-1/2">
            <div class="h-3 bg-gray-100 rounded">
              <div
                class="h-3 rounded transition-all"
                :class="progressColor"
                :style="{ width: totalProgress + '%' }"
              />
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
              <span>0%</span>
              <span>{{ totalProgress.toFixed(0) }}%</span>
              <span>100%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white shadow rounded-lg p-4">
          <div class="text-sm text-gray-500">Planejado Total</div>
          <div class="text-xl font-semibold text-gray-900">R$ {{ formatCurrency(totalPlanned) }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
          <div class="text-sm text-gray-500">Gasto até agora</div>
          <div class="text-xl font-semibold text-gray-900">R$ {{ formatCurrency(totalSpent) }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
          <div class="text-sm text-gray-500">Saldo disponível (orçamentos)</div>
          <div class="text-xl font-semibold" :class="budgetRemainingColor">R$ {{ formatCurrency(budgetRemaining) }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
          <div class="text-sm text-gray-500">Saldo total em contas</div>
          <div class="text-xl font-semibold" :class="remainingColor">R$ {{ formatCurrency(remaining) }}</div>
        </div>
      </div>
    </div>
  </div>
  
  
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  budget: { type: Object, default: null },
  stats: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  dateRange: { type: Object, default: () => ({ start: new Date(), end: new Date() }) },
  // Saldo total do usuário (todas as contas), exibido no último card
  totalBalance: { type: Number, default: 0 },
  // Conjunto de categorias do orçamento do mês (fonte para cálculos do mês)
  categories: { type: Array, default: () => [] }
})

defineEmits(['create-budget'])

// Mostrar conteúdo quando existir um orçamento OU quando houver categorias do mês
const hasData = computed(() => {
  const hasBudget = !!(props.budget && Object.keys(props.budget).length)
  const hasCats = Array.isArray(props.categories) && props.categories.length > 0
  return hasBudget || hasCats
})

// Calcular com base APENAS nas categorias do orçamento do mês
const plannedFromCats = computed(() => (props.categories || []).reduce((s, c) => s + toNumber(c.allocated_amount), 0))
const spentFromCats = computed(() => (props.categories || []).reduce((s, c) => s + toNumber(c.spent_amount), 0))

// Fallback para os campos agregados do orçamento se categorias não vierem
const totalPlanned = computed(() => plannedFromCats.value || toNumber(props.budget?.total_amount))
const totalSpent = computed(() => spentFromCats.value || toNumber(props.budget?.spent_amount))
// Saldo disponível agora: saldo total das contas do usuário
const remaining = computed(() => Number(props.totalBalance || 0))

const elapsed = computed(() => getElapsedRatio(props.dateRange.start, props.dateRange.end))
const projectedEOM = computed(() => elapsed.value > 0 ? (totalSpent.value / elapsed.value) : totalSpent.value)
const totalProgress = computed(() => totalPlanned.value > 0 ? Math.min(100, (totalSpent.value / totalPlanned.value) * 100) : 0)

const progressColor = computed(() => totalProgress.value < 75 ? 'bg-green-500' : (totalProgress.value < 95 ? 'bg-yellow-500' : 'bg-red-500'))
// Saldo disponível considerando os orçamentos do mês
const budgetRemaining = computed(() => totalPlanned.value - totalSpent.value)
const budgetRemainingColor = computed(() => budgetRemaining.value >= 0 ? 'text-green-600' : 'text-red-600')
const remainingColor = computed(() => remaining.value >= 0 ? 'text-green-600' : 'text-red-600')

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