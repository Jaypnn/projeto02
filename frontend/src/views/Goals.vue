<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Metas Financeiras</h1>
        <p class="mt-1 text-sm text-gray-500">Defina e acompanhe seus objetivos financeiros</p>
      </div>
      <button @click="openCreate()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
        <PlusIcon class="w-4 h-4 mr-2" />
        Nova Meta
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0"><TrophyIcon class="h-6 w-6 text-blue-400" /></div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Metas ativas</dt>
                <dd class="text-lg font-medium text-gray-900">{{ summary.active_goals }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0"><TrophyIcon class="h-6 w-6 text-green-400" /></div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Concluídas</dt>
                <dd class="text-lg font-medium text-gray-900">{{ summary.completed_goals }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0"><TrophyIcon class="h-6 w-6 text-rose-400" /></div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Em atraso</dt>
                <dd class="text-lg font-medium text-gray-900">{{ summary.overdue_goals }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="ml-0 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Progresso geral</dt>
                <dd class="text-lg font-medium text-gray-900">{{ Math.round(summary.overall_progress || 0) }}%</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input v-model="filters.search" type="text" placeholder="Buscar pelo nome" class="h-10 rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-blue-500" />
        <select v-model="filters.status" class="h-10 rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-blue-500">
          <option value="">Status: todos</option>
          <option value="active">Ativas</option>
          <option value="paused">Pausadas</option>
          <option value="completed">Concluídas</option>
          <option value="canceled">Canceladas</option>
        </select>
        <select v-model="filters.priority" class="h-10 rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-blue-500">
          <option value="">Prioridade: todas</option>
          <option value="low">Baixa</option>
          <option value="medium">Média</option>
          <option value="high">Alta</option>
        </select>
        <select v-model="filters.goal_type" class="h-10 rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-blue-500">
          <option value="">Tipo: todos</option>
          <option value="savings">Poupança</option>
          <option value="investment">Investimento</option>
          <option value="purchase">Compra</option>
          <option value="debt_payment">Pagamento de dívida</option>
          <option value="emergency_fund">Fundo de emergência</option>
        </select>
      </div>
      <div class="mt-3 flex justify-end gap-2">
        <button @click="resetFilters" class="px-3 py-2 text-sm rounded-md border border-gray-300">Limpar</button>
        <button @click="applyFilters" class="px-3 py-2 text-sm rounded-md bg-blue-600 text-white">Aplicar</button>
      </div>
    </div>

    <!-- Goals list -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Suas metas</h3>
        <span class="text-sm text-gray-500">{{ goals.length }} itens</span>
      </div>
      <div v-if="goals.length === 0" class="p-10 text-center text-gray-500">
        <TrophyIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
        Nenhuma meta cadastrada.
      </div>
      <ul v-else class="divide-y divide-gray-100">
        <li v-for="g in goals" :key="g.id" class="px-6 py-4">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-gray-900 truncate">{{ g.name }}</p>
                <span class="text-xs px-2 py-0.5 rounded-full" :class="priorityBadge(g.priority)">{{ priorityLabel(g.priority) }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full" :class="statusBadge(g.status)">{{ statusLabel(g.status) }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">Meta: {{ formatCurrency(g.target_amount) }} • Atual: {{ formatCurrency(g.current_amount) }} • Prazo: {{ formatDate(g.target_date) }}</p>
              <div class="mt-2">
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="h-2 rounded-full bg-blue-600" :style="{ width: Math.min(goalProgress(g.id), 100) + '%' }"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                  <span>{{ Math.round(goalProgress(g.id)) }}%</span>
                  <span>Faltam {{ formatCurrency(Math.max(0, (g.target_amount || 0) - (g.current_amount || 0))) }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button @click="openContrib(g)" class="px-3 py-2 text-xs rounded-md border border-gray-300">Contribuir</button>
              <button @click="openEdit(g)" class="px-3 py-2 text-xs rounded-md border border-gray-300">Editar</button>
              <button @click="toggle(g)" class="px-3 py-2 text-xs rounded-md border border-gray-300">
                {{ g.status === 'active' ? 'Pausar' : 'Ativar' }}
              </button>
              <button @click="remove(g)" class="px-3 py-2 text-xs rounded-md bg-rose-600 text-white">Excluir</button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Modals -->
    <GoalFormModal :open="modal.open" :goal="modal.goal" :category-options="categoryOptions" @close="modal.open=false" @saved="onSaved" />
    <ContributionModal v-if="contrib.open" :open="contrib.open" :goal-id="contrib.goalId" @close="contrib.open=false" @saved="onContribution" />
  </div>
  
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import { useStore } from 'vuex'
import { PlusIcon, TrophyIcon } from '@heroicons/vue/24/outline'
import GoalFormModal from '@/components/goals/GoalFormModal.vue'
import ContributionModal from '@/components/goals/ContributionModal.vue'
import apiService from '@/services'

const store = useStore()

// local ui state
const modal = ref({ open: false, goal: null })
const contrib = ref({ open: false, goalId: null })
const summary = ref({ active_goals: 0, completed_goals: 0, overdue_goals: 0, overall_progress: 0 })
const filters = reactive({ search: '', status: '', priority: '', goal_type: '' })

const goals = computed(() => store.getters['goals/allGoals'])
const goalProgress = (id) => store.getters['goals/goalProgress'](id)
const categoryOptions = computed(() => store.getters['categories/activeCategoriesOptions'])

function formatCurrency(value){
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
}
function formatDate(date){
  const d = new Date(date)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function priorityLabel(p){ return { low: 'Baixa', medium: 'Média', high: 'Alta' }[p] || '—' }
function statusLabel(s){ return { active: 'Ativa', paused: 'Pausada', completed: 'Concluída', canceled: 'Cancelada' }[s] || '—' }
function priorityBadge(p){ return p === 'high' ? 'bg-rose-100 text-rose-700' : p === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }
function statusBadge(s){
  return {
    active: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-blue-100 text-blue-700',
    canceled: 'bg-gray-100 text-gray-600'
  }[s] || 'bg-gray-100 text-gray-600'
}

function openCreate(){ modal.value = { open: true, goal: null } }
function openEdit(goal){ modal.value = { open: true, goal } }
function openContrib(goal){ contrib.value = { open: true, goalId: goal.id } }

async function remove(goal){
  if (!confirm(`Excluir a meta "${goal.name}"?`)) return
  await store.dispatch('goals/deleteGoal', goal.id)
  await refreshSummary()
}

async function toggle(goal){
  await store.dispatch('goals/toggleStatus', goal.id)
  await refreshSummary()
}
async function refreshSummary(){
  try {
    const res = await apiService.goals.getSummary()
    summary.value = res?.data?.data || {}
  } catch (_) {}
}

async function onSaved(){
  modal.value.open = false
  await Promise.all([store.dispatch('goals/fetchGoals'), refreshSummary()])
}
async function onContribution(){
  contrib.value.open = false
  await Promise.all([store.dispatch('goals/fetchGoals'), refreshSummary()])
}

function resetFilters(){ filters.search = filters.status = filters.priority = filters.goal_type = '' }
async function applyFilters(){
  const params = { ...filters }
  Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
  await store.dispatch('goals/fetchGoals', params)
  await refreshSummary()
}

onMounted(async () => {
  await Promise.all([
    store.dispatch('goals/fetchGoals'),
    store.dispatch('categories/fetchCategories')
  ])
  await refreshSummary()
})
</script>