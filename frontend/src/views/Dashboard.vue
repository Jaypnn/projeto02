<template>
  <div class="space-y-6">
    <!-- Header do Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">
          Visão geral das suas finanças
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex space-x-3">
        <select 
          v-model="selectedPeriod" 
          class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="7">Últimos 7 dias</option>
          <option value="30">Últimos 30 dias</option>
          <option value="90">Últimos 90 dias</option>
          <option value="365">Último ano</option>
        </select>
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
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <span 
              class="font-medium"
              :class="incomeChange >= 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ incomeChange >= 0 ? '+' : '' }}{{ incomeChange.toFixed(1) }}%
            </span>
            <span class="text-gray-600 ml-1">vs período anterior</span>
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
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <span 
              class="font-medium"
              :class="expenseChange <= 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ expenseChange >= 0 ? '+' : '' }}{{ expenseChange.toFixed(1) }}%
            </span>
            <span class="text-gray-600 ml-1">vs período anterior</span>
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
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <span class="font-medium text-gray-600">
              {{ netIncome >= 0 ? 'Sobrou' : 'Faltou' }} no período
            </span>
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
const selectedPeriod = ref(30)
const loading = ref(false)

// Computed
const totalBalance = computed(() => store.getters['accounts/totalBalance'])
// Totais independentes da tela de Transações (buscados do backend)
const totalIncome = ref(0)
const totalExpenses = ref(0)
const netIncome = computed(() => totalIncome.value - totalExpenses.value)

// Transações recentes locais para não poluir o store
const recentTransactions = ref([])

const activeGoals = computed(() => 
  store.getters['goals/activeGoals'].slice(0, 3)
)

const goalProgress = computed(() => 
  store.getters['goals/goalProgress']
)

// Mock data para demonstração
const incomeChange = ref(12.5)
const expenseChange = ref(-5.2)

const chartData = ref([
  { date: '2025-01-01', income: 3500, expenses: 2800 },
  { date: '2025-01-02', income: 0, expenses: 150 },
  { date: '2025-01-03', income: 500, expenses: 300 },
  // ... mais dados
])

const categoryData = ref([
  { name: 'Alimentação', value: 800, color: '#ef4444' },
  { name: 'Transporte', value: 400, color: '#f97316' },
  { name: 'Moradia', value: 1200, color: '#eab308' },
  { name: 'Lazer', value: 300, color: '#22c55e' },
  { name: 'Outros', value: 200, color: '#3b82f6' }
])

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

import apiService from '@/services'

const refreshData = async () => {
  loading.value = true
  
  try {
    const end = new Date()
    const start = new Date(end)
    start.setDate(end.getDate() - Number(selectedPeriod.value))

    // yyyy-mm-dd
    const toDate = (d) => d.toISOString().slice(0,10)
    const params = { start_date: toDate(start), end_date: toDate(end) }

    const [summaryRes] = await Promise.all([
      apiService.transactions.getSummary(params),
      store.dispatch('accounts/fetchAccounts'),
      store.dispatch('goals/fetchGoals')
    ])

    const sum = summaryRes?.data?.data || summaryRes?.data || {}
    totalIncome.value = Number(sum.total_income || 0)
    totalExpenses.value = Number(sum.total_expense || 0)

    // Carrega 5 transações recentes sem mexer no store global
    try {
      const listRes = await apiService.transactions.getAll({ per_page: 5, order_by: 'transaction_date', order_direction: 'desc' })
      const resData = listRes?.data
      recentTransactions.value = Array.isArray(resData) ? resData.slice(0,5) : (resData?.data || []).slice(0,5)
    } catch {}
  } catch (error) {
    console.error('Erro ao atualizar dados:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  refreshData()
})

// Atualiza quando mudar o período
import { watch } from 'vue'
watch(selectedPeriod, refreshData)
</script>