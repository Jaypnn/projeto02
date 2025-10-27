<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Orçamentos</h1>
        <p class="mt-1 text-sm text-gray-500">Planeje e acompanhe seus gastos mensais</p>
      </div>
      <div class="flex items-center gap-3">
        <input
          type="month"
          v-model="selectedMonth"
          class="block w-44 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        />
        
        <button
          @click="onCreateBudget"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Novo Orçamento
        </button>
      </div>
    </div>

    <!-- Summary mensal agregado (todas as categorias dos orçamentos do mês) -->
    <BudgetSummary
      :budget="null"
      :stats="null"
      :loading="loadingBudgets || loadingStatsAny"
      :date-range="{ start: periodStart, end: periodEnd }"
      :total-balance="totalBalance"
      :categories="allCategories"
      @create-budget="onCreateBudget"
    />

    <!-- Filtros da lista de orçamentos (por categoria) -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="w-full sm:w-80">
          <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por categoria</label>
          <input v-model.trim="search" type="text" placeholder="Ex: Educação, Transporte..." class="w-full h-10 px-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
        </div>
      </div>
    </div>

    <!-- Lista de cards de categorias (todos os orçamentos do mês) -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <BudgetCategoryCard
        v-for="item in displayCategories"
        :key="item.id"
        :budget-id="item.__budgetId"
        :budget-name="item.__budgetName"
        :item="item"
        :date-range="{ start: periodStart, end: periodEnd }"
        @view-transactions="viewCategoryTransactions"
        @edit-goal="(payload) => openEditGoal(item.__budgetId, payload)"
        @delete-budget="() => askDelete(item.__budgetId)"
      />
    </div>

    <!-- Insights -->
    <BudgetInsights :items="insights" />

    <!-- Details table -->
    <BudgetDetailsTable
      :rows="tableRows"
      :date-range="{ start: periodStart, end: periodEnd }"
    />
  </div>
  
  <!-- Create budget modal -->
  <BudgetCreateModal
    v-if="showCreateModal"
    :month="selectedMonth"
    @close="showCreateModal = false"
    @created="onBudgetCreated"
  />

  <!-- Edit goal modal -->
  <BudgetEditGoalModal
    v-if="goalModal.open"
    :open="goalModal.open"
    :budget-id="goalModal.budgetId"
    :budget-category-id="goalModal.bcId"
    :current-allocated="goalModal.currentAllocated"
    @close="goalModal.open = false"
    @saved="onGoalSaved"
  />

  <!-- Delete confirm -->
  <ConfirmModal
    v-model="showDeleteConfirm"
    title="Excluir orçamento"
    :message="'Tem certeza que deseja excluir o orçamento deste período? Esta ação não pode ser desfeita.'"
    confirm-text="Excluir"
    cancel-text="Cancelar"
    @confirm="onDeleteBudget"
    @cancel="showDeleteConfirm = false"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { PlusIcon } from '@heroicons/vue/24/outline'
import BudgetSummary from '@/components/budgets/BudgetSummary.vue'
import BudgetCategoryCard from '@/components/budgets/BudgetCategoryCard.vue'
import BudgetInsights from '@/components/budgets/BudgetInsights.vue'
import BudgetDetailsTable from '@/components/budgets/BudgetDetailsTable.vue'
import BudgetCreateModal from '@/components/budgets/BudgetCreateModal.vue'
import BudgetEditGoalModal from '@/components/budgets/BudgetEditGoalModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import { transactionService, budgetService, accountService } from '@/services'

const store = useStore()
const router = useRouter()

// Month selector (YYYY-MM)
const now = new Date()
const pad = (n) => String(n).padStart(2, '0')
const selectedMonth = ref(`${now.getFullYear()}-${pad(now.getMonth() + 1)}`)

const periodStart = computed(() => new Date(`${selectedMonth.value}-01T00:00:00`))
const periodEnd = computed(() => {
  const d = new Date(periodStart.value)
  d.setMonth(d.getMonth() + 1)
  d.setDate(0) // last day prev month
  d.setHours(23, 59, 59, 999)
  return d
})

// Local state
const loadingBudgets = computed(() => store.getters['budgets/budgetsLoading'])
const budgets = computed(() => store.getters['budgets/allBudgets'])
const error = computed(() => store.getters['budgets/budgetsError'])

// Util: parser de data "YYYY-MM-DD" em horário local para evitar deslocamento de fuso
function parseLocalDate(dateStr) {
  if (!dateStr) return new Date('Invalid')
  const [y, m, d] = String(dateStr).split('-').map(n => Number(n))
  return new Date(y, (m || 1) - 1, d || 1, 0, 0, 0, 0)
}
function parseLocalDateEnd(dateStr) {
  if (!dateStr) return new Date('Invalid')
  const [y, m, d] = String(dateStr).split('-').map(n => Number(n))
  return new Date(y, (m || 1) - 1, d || 1, 23, 59, 59, 999)
}

// Lista de orçamentos EXATAMENTE do mês selecionado (sem vazamento de mês)
const budgetsForMonth = computed(() => {
  const start = periodStart.value
  const end = periodEnd.value
  return (budgets.value || []).filter(b => {
    // Os campos do backend vêm como YYYY-MM-DD; se usados com new Date("2025-10-01") viram UTC e podem cair no dia anterior no Brasil.
    // Usamos parser local para garantir comparação correta mês-a-mês.
    const bs = parseLocalDate(b.start_date)
    const be = parseLocalDateEnd(b.end_date)
    return bs <= end && be >= start && bs.getMonth() === start.getMonth() && bs.getFullYear() === start.getFullYear()
  })
})

// Mapas com orçamento detalhado e stats por orçamento
const detailedBudgets = ref({}) // { [id]: budget }
const budgetStatsMap = ref({})   // { [id]: stats }
function categoriesByBudget(id){
  const det = detailedBudgets.value[id]
  const fallback = (budgetsForMonth.value.find(b => b.id === id) || {}).budget_categories || []
  return det?.budget_categories || fallback
}
// categorias enriquecidas com info do orçamento (para deletar/editar e exibir nome)
const allCategories = computed(() => budgetsForMonth.value.flatMap(b => (categoriesByBudget(b.id) || []).map(c => ({ ...c, __budgetId: b.id, __budgetName: b.name }))))

// busca por categoria
const search = ref('')
const displayCategories = computed(() => {
  const term = (search.value || '').toLowerCase().trim()
  if (!term) return allCategories.value
  return allCategories.value.filter(c => String(c.category?.name || '').toLowerCase().includes(term))
})

// Stats loading
const loadingStats = ref({}) // { [id]: bool }
const loadingStatsAny = computed(() => Object.values(loadingStats.value).some(Boolean))
const totalBalance = ref(0)

async function fetchStatsFor(id) {
  loadingStats.value = { ...loadingStats.value, [id]: true }
  try {
    const resBudget = await budgetService.getById(id)
    detailedBudgets.value = { ...detailedBudgets.value, [id]: resBudget?.data?.data || (budgetsForMonth.value.find(b => b.id === id) || null) }
    budgetStatsMap.value = { ...budgetStatsMap.value, [id]: resBudget?.data?.stats || null }
    try {
      const perf = await budgetService.getPerformance(id)
      if (perf?.data?.data?.budget_stats) {
        budgetStatsMap.value = { ...budgetStatsMap.value, [id]: perf.data.data.budget_stats }
      }
    } catch {}
  } catch (e) {
    // keep fallback
  } finally {
    loadingStats.value = { ...loadingStats.value, [id]: false }
  }
}
async function fetchAllStats(){
  const ids = budgetsForMonth.value.map(b => b.id)
  await Promise.all(ids.map(fetchStatsFor))
}

// Accounts summary for total balance
async function fetchAccountsSummary(){
  try {
    const res = await accountService.getSummary()
    totalBalance.value = Number(res?.data?.data?.total_balance ?? 0)
  } catch {
    totalBalance.value = 0
  }
}

// Insights (computed locally)
const insights = computed(() => {
  if (!budgetsForMonth.value.length) return []
  const start = periodStart.value
  const end = periodEnd.value
  const elapsed = getElapsedRatio(start, end)

  const rows = allCategories.value.map(bc => {
    const allocated = toNumber(bc.allocated_amount)
    const spent = toNumber(bc.spent_amount)
    const expected = allocated * elapsed
    const delta = spent - expected
    const forecast = elapsed > 0 ? (spent / elapsed) : spent
    return { bc, allocated, spent, expected, delta, forecast }
  })

  const overPacing = rows.filter(r => r.delta > allocatedTolerance(r.allocated)).sort((a,b)=>b.delta-a.delta).slice(0,3)
  const underUtilized = rows.filter(r => r.spent < r.expected * 0.6).sort((a,b)=> (a.spent/a.allocated) - (b.spent/b.allocated)).slice(0,3)

  const out = []
  if (overPacing.length) {
    out.push({
      type: 'alert',
      title: `Acima do ritmo em ${overPacing.length} categoria(s)`,
      details: overPacing.map(r => `${r.bc.category?.name || 'Categoria'}: +R$ ${formatCurrency(r.delta)}`)
    })
  }
  if (underUtilized.length) {
    out.push({
      type: 'info',
      title: `Categorias com baixa utilização`,
      details: underUtilized.map(r => `${r.bc.category?.name || 'Categoria'}: R$ ${formatCurrency(r.spent)} de R$ ${formatCurrency(r.allocated)}`)
    })
  }
  return out
})

// Table rows
const tableRows = computed(() => {
  const start = periodStart.value
  const end = periodEnd.value
  const elapsed = getElapsedRatio(start, end)
  return (allCategories.value || []).map(bc => {
    const allocated = toNumber(bc.allocated_amount)
    const spent = toNumber(bc.spent_amount)
    const forecast = elapsed > 0 ? (spent / elapsed) : spent
    const diff = allocated - forecast
    const pacing = elapsed > 0 ? (spent / Math.max(allocated * elapsed, 0.01)) : 0
    return {
      id: bc.id,
      category: bc.category?.name || 'Categoria',
      allocated,
      spent,
      pacing,
      forecast,
      diff
    }
  })
})

// Actions
function onCreateBudget() { showCreateModal.value = true }

function viewCategoryTransactions(categoryId) {
  router.push({ name: 'Transactions', query: {
    category_id: categoryId,
    start_date: formatISODate(periodStart.value),
    end_date: formatISODate(periodEnd.value)
  }})
}

async function openEditGoal(budgetId, { budgetCategoryId, currentAllocated }) {
  goalModal.value = { open: true, budgetId, bcId: budgetCategoryId, currentAllocated }
}

// Create modal state & handler
const showCreateModal = ref(false)
async function onBudgetCreated() {
  showCreateModal.value = false
  await store.dispatch('budgets/fetchBudgets')
  await fetchAllStats()
}

// Edit goal modal state
const goalModal = ref({ open: false, budgetId: null, bcId: null, currentAllocated: 0 })
async function onGoalSaved(){
  goalModal.value.open = false
  await store.dispatch('budgets/fetchBudgets')
  await fetchAllStats()
}

// Delete budget state/handlers
const showDeleteConfirm = ref(false)
const deleteTargetId = ref(null)
function askDelete(id){ deleteTargetId.value = id; showDeleteConfirm.value = true }
async function onDeleteBudget(){
  try {
    if (!deleteTargetId.value) return
    await budgetService.delete(deleteTargetId.value)
    showDeleteConfirm.value = false
    deleteTargetId.value = null
    // refresh list; might remove active budget
    await store.dispatch('budgets/fetchBudgets')
    detailedBudgets.value = {}
    budgetStatsMap.value = {}
    await fetchAllStats()
  } catch (e) {
    console.error(e)
    alert('Não foi possível excluir o orçamento.')
  }
}

// Helpers
function toNumber(n) { return typeof n === 'number' ? n : Number(n || 0) }
// Formata YYYY-MM-DD em horário local (sem toISOString para evitar mudança de dia)
function formatISODate(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
function daysBetween(a,b){ const ms = b - a; return Math.floor(ms / 86400000) + 1 }
function getElapsedRatio(start, end){
  const today = new Date();
  const total = daysBetween(new Date(start.getFullYear(), start.getMonth(), start.getDate()), new Date(end.getFullYear(), end.getMonth(), end.getDate()))
  if (today < start) return 0
  const capped = today > end ? end : today
  const elapsed = daysBetween(new Date(start.getFullYear(), start.getMonth(), start.getDate()), new Date(capped.getFullYear(), capped.getMonth(), capped.getDate()))
  return Math.min(1, Math.max(0, elapsed / total))
}
function allocatedTolerance(a){ return Math.max(10, a * 0.05) }
function formatCurrency(n){ return (Number(n)||0).toFixed(2).replace('.', ',') }

// Effects
async function loadAll(){
  if (!budgets.value?.length) {
    try { await store.dispatch('budgets/fetchBudgets') } catch {}
  }
  await fetchAllStats()
  await fetchAccountsSummary()
}

onMounted(loadAll)
watch(selectedMonth, loadAll)
// Removido watcher extra para evitar chamadas duplicadas de stats

</script>

<style scoped>
</style>