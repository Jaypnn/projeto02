<template>
  <div class="space-y-6">
    <!-- Header do Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">
          Visão geral das suas finanças - {{ getPeriodLabel() }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row sm:items-center sm:space-x-3 space-y-2 sm:space-y-0">
        <select 
          v-model="selectedPeriod" 
          class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="current_month">Mês atual</option>
          <option value="last_month">Mês passado</option>
          <option value="custom">Selecionar período</option>
        </select>
        <div v-if="selectedPeriod === 'custom'" class="flex items-center space-x-2">
          <input type="date" v-model="customStart" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
          <span class="text-gray-500 text-sm">até</span>
          <input type="date" v-model="customEnd" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
          <button @click="applyCustomPeriod" :disabled="loading || !isCustomValid" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">Aplicar</button>
        </div>
        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
        >
          <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
          Atualizar
        </button>
      </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Saldo Total -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <BuildingLibraryIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Saldo Total
                </dt>
                <dd class="text-lg font-medium text-gray-900">
                  {{ formatCurrency(totalBalance) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <span class="font-medium text-gray-600">Todas as contas</span>
          </div>
        </div>
      </div>

      <!-- Receitas do Período -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ArrowTrendingUpIcon class="h-6 w-6 text-green-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Receitas
                </dt>
                <dd class="text-lg font-medium text-green-600">
                  {{ formatCurrency(totalIncome) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Despesas do Período -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ArrowTrendingDownIcon class="h-6 w-6 text-red-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Despesas
                </dt>
                <dd class="text-lg font-medium text-red-600">
                  {{ formatCurrency(totalExpenses) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Economia/Resultado -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <TrophyIcon class="h-6 w-6 text-blue-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Economia
                </dt>
                <dd 
                  class="text-lg font-medium"
                  :class="netIncome >= 0 ? 'text-green-600' : 'text-red-600'"
                >
                  {{ formatCurrency(netIncome) }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos e Conteúdo Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Gráfico de Receitas vs Despesas -->
      <div class="lg:col-span-2 bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            Fluxo Financeiro
          </h3>
          <div class="flex items-center space-x-2">
            <div class="flex items-center">
              <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
              <span class="text-sm text-gray-600">Receitas</span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
              <span class="text-sm text-gray-600">Despesas</span>
            </div>
          </div>
        </div>
        <div class="h-80">
          <LineChart 
            v-if="chartData.length > 0"
            :data="chartData"
            :options="chartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            <div class="text-center">
              <ChartBarIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
              <p>Nenhum dado disponível</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Distribuição por Categorias -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Gastos por Categoria
        </h3>
        <div class="h-80">
          <DoughnutChart 
            v-if="categoryData.length > 0"
            :data="categoryData"
            :options="doughnutOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            <div class="text-center">
              <TagIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
              <p>Nenhuma categoria</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Transações Recentes e Metas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Transações Recentes -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              Transações Recentes
            </h3>
            <router-link 
              to="/transactions" 
              class="text-sm text-blue-600 hover:text-blue-500"
            >
              Ver todas
            </router-link>
          </div>
        </div>
        <div class="divide-y divide-gray-200">
          <div 
            v-for="transaction in recentTransactions" 
            :key="transaction.id"
            class="px-6 py-4 hover:bg-gray-50"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div 
                  class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
                  :class="transaction.type === 'income' ? 'bg-green-100' : 'bg-red-100'"
                >
                  <ArrowTrendingUpIcon 
                    v-if="transaction.type === 'income'"
                    class="w-5 h-5 text-green-600"
                  />
                  <ArrowTrendingDownIcon 
                    v-else
                    class="w-5 h-5 text-red-600"
                  />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ transaction.description }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ transaction.category?.name || 'Sem categoria' }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p 
                  class="text-sm font-medium"
                  :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'"
                >
                  {{ transaction.type === 'income' ? '+' : '-' }}{{ formatCurrency(Math.abs(transaction.amount)) }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ formatDate(transaction.transaction_date) }}
                </p>
              </div>
            </div>
          </div>
          <div v-if="recentTransactions.length === 0" class="px-6 py-8 text-center">
            <CreditCardIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p class="text-gray-500">Nenhuma transação recente</p>
          </div>
        </div>
      </div>

      <!-- Metas Financeiras -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              Metas Financeiras
            </h3>
            <router-link 
              to="/goals" 
              class="text-sm text-blue-600 hover:text-blue-500"
            >
              Ver todas
            </router-link>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <div 
            v-for="goal in activeGoals" 
            :key="goal.id"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-medium text-gray-900">
                {{ goal.name }}
              </h4>
              <span class="text-sm text-gray-500">
                {{ Math.round(goalProgress(goal.id)) }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
              <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: Math.min(goalProgress(goal.id), 100) + '%' }"
              ></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
              <span>{{ formatCurrency(goal.current_amount) }}</span>
              <span>{{ formatCurrency(goal.target_amount) }}</span>
            </div>
          </div>
          <div v-if="activeGoals.length === 0" class="text-center py-8">
            <TrophyIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p class="text-gray-500">Nenhuma meta ativa</p>
            <router-link 
              to="/goals" 
              class="text-sm text-blue-600 hover:text-blue-500 mt-2 inline-block"
            >
              Criar primeira meta
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import {
  ArrowPathIcon,
  BuildingLibraryIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  TrophyIcon,
  ChartBarIcon,
  TagIcon,
  CreditCardIcon
} from '@heroicons/vue/24/outline'

// Importar componentes de gráfico (placeholder - serão criados)
import LineChart from '../components/charts/LineChart.vue'
import DoughnutChart from '../components/charts/DoughnutChart.vue'

const store = useStore()

// State
const selectedPeriod = ref('current_month')
const loading = ref(false)
// Para evitar condição de corrida entre múltiplas atualizações do período
const requestId = ref(0)
// Período customizado
const customStart = ref('')
const customEnd = ref('')
const isCustomValid = computed(() => {
  if (!customStart.value || !customEnd.value) return false
  return new Date(customStart.value) <= new Date(customEnd.value)
})

// Computed
const totalBalance = computed(() => store.getters['accounts/totalBalance'])
// Totais independentes da tela de Transações (buscados do backend)
const totalIncome = ref(0)
const totalExpenses = ref(0)
const netIncome = computed(() => totalIncome.value - totalExpenses.value)

// Transações recentes locais para não poluir o store
const recentTransactions = ref([])

// Removido: comparações com período anterior

const activeGoals = computed(() => 
  store.getters['goals/activeGoals'].slice(0, 3)
)

const goalProgress = computed(() => 
  store.getters['goals/goalProgress']
)

const chartData = ref([])

const categoryData = ref([])

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function(value) {
          return 'R$ ' + value.toLocaleString('pt-BR')
        }
      }
    }
  }
})

const doughnutOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
})

// Methods
const formatCurrency = (value) => {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(value || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short'
  })
}

const formatDateLabel = (date) => {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const getPeriodLabel = () => {
  if (selectedPeriod.value === 'custom') {
    return isCustomValid.value
      ? `${formatDateLabel(customStart.value)} a ${formatDateLabel(customEnd.value)}`
      : 'Período personalizado'
  }
  const periodLabels = {
    current_month: 'Mês atual',
    last_month: 'Mês passado'
  }
  return periodLabels[selectedPeriod.value] || 'Período personalizado'
}

const getPeriodDates = (period) => {
  const today = new Date()
  const localToday = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  
  let startDate, endDate

  switch (period) {
    case 'current_month':
      startDate = new Date(localToday.getFullYear(), localToday.getMonth(), 1)
      endDate = new Date(localToday)
      break
      
    case 'last_month':
      startDate = new Date(localToday.getFullYear(), localToday.getMonth() - 1, 1)
      endDate = new Date(localToday.getFullYear(), localToday.getMonth(), 0)
      break
      
    case 'custom':
      if (isCustomValid.value) {
        startDate = new Date(customStart.value)
        endDate = new Date(customEnd.value)
      } else {
        startDate = new Date(localToday.getFullYear(), localToday.getMonth(), 1)
        endDate = new Date(localToday)
      }
      break
      
    default:
      startDate = new Date(localToday.getFullYear(), localToday.getMonth(), 1)
      endDate = new Date(localToday)
  }

  return {
    startDate: `${startDate.getFullYear()}-${String(startDate.getMonth() + 1).padStart(2, '0')}-${String(startDate.getDate()).padStart(2, '0')}`,
    endDate: `${endDate.getFullYear()}-${String(endDate.getMonth() + 1).padStart(2, '0')}-${String(endDate.getDate()).padStart(2, '0')}`
  }
}

import apiService from '@/services'

// Utilidades de agregação para gráficos
// Garante parsing LOCAL de 'YYYY-MM-DD' para evitar deslocamento por timezone
const parseLocal = (str) => {
  const [y, m, d] = String(str).split('-').map(Number)
  return new Date(y, (m || 1) - 1, d || 1)
}

const eachDay = (startStr, endStr) => {
  const out = []
  const start = parseLocal(startStr)
  const end = parseLocal(endStr)
  for (let d = new Date(start.getFullYear(), start.getMonth(), start.getDate()); d <= end; d.setDate(d.getDate() + 1)) {
    const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    out.push(iso)
  }
  return out
}

const PALETTE = ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7','#06b6d4','#84cc16','#f59e0b','#10b981']

const buildDailySeries = (transactions, startDate, endDate) => {
  const days = eachDay(startDate, endDate)
  const map = Object.fromEntries(days.map(d => [d, { income: 0, expenses: 0 }]))
  for (const t of transactions) {
    const d = String(t.transaction_date).slice(0,10)
    if (!map[d]) continue
    if (t.type === 'income') map[d].income += Number(t.amount || 0)
    else if (t.type === 'expense') map[d].expenses += Number(t.amount || 0)
  }
  // Labels apenas com o dia (01..31), sem depender do objeto Date (evita TZ)
  return days.map(d => ({
    date: d,
    label: d.slice(8, 10),
    income: map[d].income,
    expenses: map[d].expenses
  }))
}

const buildCategoryBreakdown = (transactions) => {
  const totals = new Map()
  for (const t of transactions) {
    if (t.type !== 'expense') continue
    const name = t.category?.name || 'Sem categoria'
    totals.set(name, (totals.get(name) || 0) + Number(t.amount || 0))
  }
  const items = Array.from(totals.entries()).sort((a,b) => b[1]-a[1])
  return items.map(([name, value], i) => ({ name, value, color: PALETTE[i % PALETTE.length] }))
}

const refreshData = async () => {
  const myReq = ++requestId.value
  loading.value = true
  // Evita exibir dados do período anterior enquanto recarrega
  recentTransactions.value = []
  
  try {
    const { startDate, endDate } = getPeriodDates(selectedPeriod.value)
    
    const params = { 
      start_date: startDate, 
      end_date: endDate
    }

    const [summaryRes] = await Promise.all([
      apiService.transactions.getSummary(params),
      store.dispatch('accounts/fetchAccounts'),
      store.dispatch('goals/fetchGoals')
    ])

    // Se outra requisição mais recente já começou, ignora esta resposta
    if (myReq !== requestId.value) return

    const sum = summaryRes?.data?.data || summaryRes?.data || {}
    if (import.meta.env.DEV) {
      console.log('Dashboard summary period ->', sum?.period)
      console.log('Dashboard summary totals ->', {
        total_income: sum.total_income,
        total_expense: sum.total_expense,
        net_income: (sum.total_income ?? 0) - (sum.total_expense ?? 0)
      })
    }
    totalIncome.value = Number(sum.total_income || 0)
    totalExpenses.value = Number(sum.total_expense || 0)

    // Removido: atualização de comparações com período anterior

    // Carrega transações recentes do período atual (escopo do usuário) SEM fallback para fora do período
    try {
      const listRes = await apiService.transactions.getMine({ 
        start_date: startDate,
        end_date: endDate,
        per_page: 5, 
        order_by: 'transaction_date', 
        order_direction: 'desc' 
      })
      // Se outra requisição mais recente já começou, ignora esta resposta
      if (myReq !== requestId.value) return
      const resData = listRes?.data
      recentTransactions.value = Array.isArray(resData) ? resData.slice(0,5) : (resData?.data || []).slice(0,5)
    } catch (e) {
      if (import.meta.env.DEV) console.warn('Falha ao carregar transações recentes do período selecionado:', e?.message)
      recentTransactions.value = []
    }

    // Carrega todas as transações do período para gráficos (limite alto para mês)
    try {
      const seriesRes = await apiService.transactions.getMine({
        start_date: startDate,
        end_date: endDate,
        per_page: 1000,
        order_by: 'transaction_date',
        order_direction: 'asc'
      })

      if (myReq !== requestId.value) return

      const list = Array.isArray(seriesRes?.data) ? seriesRes.data : (seriesRes?.data?.data || [])
      chartData.value = buildDailySeries(list, startDate, endDate)
      categoryData.value = buildCategoryBreakdown(list)
    } catch (e) {
      if (import.meta.env.DEV) console.warn('Falha ao carregar dados para gráficos:', e?.message)
      chartData.value = []
      categoryData.value = []
    }
  } catch (error) {
    console.error('Erro ao atualizar dados:', error)
  } finally {
    if (myReq === requestId.value) {
      loading.value = false
    }
  }
}

// Lifecycle
onMounted(() => {
  refreshData()
})

// Atualiza quando mudar o período
import { watch } from 'vue'
watch(selectedPeriod, (val) => {
  if (val !== 'custom') {
    refreshData()
  }
})

const applyCustomPeriod = () => {
  if (!isCustomValid.value) return
  refreshData()
}
</script>