<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Transações</h1>
        <p class="mt-1 text-sm text-gray-500">Gerencie suas receitas e despesas</p>
      </div>
      <button @click="openCreate = true" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
        <PlusIcon class="w-4 h-4 mr-2" />
        Nova Transação
      </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 p-6">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
          <select v-model="localFilters.type" :class="inputClasses">
            <option :value="null">Todos</option>
            <option value="income">Receitas</option>
            <option value="expense">Despesas</option>
            <option value="transfer">Transferências</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
          <select v-model.number="localFilters.category_id" :class="inputClasses">
            <option :value="null">Todas</option>
            <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Conta</label>
          <select v-model.number="localFilters.account_id" :class="inputClasses">
            <option :value="null">Todas</option>
            <option v-for="a in accounts" :key="a.value" :value="a.value">{{ a.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Início</label>
          <input v-model="localFilters.start_date" type="date" :class="inputClasses" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Fim</label>
          <input v-model="localFilters.end_date" type="date" :class="inputClasses" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
          <input v-model="localFilters.search" type="text" placeholder="Descrição..." :class="inputClasses" />
        </div>
      </div>

      <div class="mt-4 flex items-center gap-2">
        <button @click="applyFilters" class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm">Aplicar</button>
        <button @click="resetFilters" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Limpar</button>
        <div class="ml-auto flex items-center gap-2">
          <label class="text-sm text-gray-600">Ordenar por:</label>
          <select v-model="orderBy" :class="inputClasses">
            <option value="transaction_date">Data</option>
            <option value="amount">Valor</option>
            <option value="description">Descrição</option>
          </select>
          <select v-model="orderDirection" :class="inputClasses">
            <option value="desc">Desc</option>
            <option value="asc">Asc</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Lista de Transações -->
    <div class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Lista de Transações</h3>
        <span v-if="loading" class="text-sm text-gray-500">Carregando...</span>
      </div>

      <div v-if="transactions.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="t in transactions" :key="t.id" class="odd:bg-white even:bg-gray-50/50 hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatDate(t.transaction_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ t.description }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ t.category?.name || (t.type==='transfer' ? 'Transferência' : '-') }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ accountLabel(t.account) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right" :class="t.type==='income' ? 'text-emerald-600' : (t.type==='expense' ? 'text-rose-600' : 'text-slate-700')">
                {{ formatCurrency(t.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                <div class="flex items-center justify-end gap-2">
                  <button class="px-2 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50 disabled:opacity-50" @click="onEdit(t)">Editar</button>
                  <button class="px-2 py-1 text-xs rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50" @click="askDelete(t)">Excluir</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-6 text-center text-gray-500">
        <CreditCardIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">Sem transações</h3>
        <p>Use o botão acima para adicionar sua primeira transação.</p>
      </div>

      <!-- Paginação -->
      <div v-if="pagination.total > pagination.perPage" class="px-6 py-3 border-t flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50" :disabled="pagination.currentPage <= 1" @click="goToPage(pagination.currentPage - 1)">Anterior</button>
          <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50" :disabled="pagination.currentPage >= totalPages" @click="goToPage(pagination.currentPage + 1)">Próxima</button>
        </div>
        <span class="text-sm text-gray-600">Página {{ pagination.currentPage }} de {{ totalPages }}</span>
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600">Itens por página:</label>
          <select :value="pagination.perPage" @change="changePerPage($event.target.value)" class="h-9 px-2 rounded-md border border-gray-300 text-sm">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <TransactionFormModal :open="openCreate" @close="openCreate = false" @saved="onSaved" />
    <TransactionFormModal :open="Boolean(editTarget)" :transaction="editTarget" @close="editTarget = null" @saved="onSaved" />
    <ConfirmModal v-model="showConfirm" title="Excluir transação" :message="confirmMessage" confirm-text="Excluir" cancel-text="Cancelar" @confirm="onDelete" @cancel="showConfirm=false" />
  </div>
  
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
import { PlusIcon, CreditCardIcon } from '@heroicons/vue/24/outline'
import TransactionFormModal from '@/components/transactions/TransactionFormModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const store = useStore()

const transactions = computed(() => {
  const list = store.getters['transactions/allTransactions'] || []
  const f = store.state.transactions?.filters || {}
  const ord = store.state.transactions?.order || { order_by: 'transaction_date', order_direction: 'desc' }
  const normDate = (d) => d ? new Date(d).toISOString().slice(0,10) : null
  const start = normDate(f.start_date)
  const end = normDate(f.end_date)
  const search = (f.search || '').toLowerCase()

  const filtered = list.filter(item => {
    if (f.type && item.type !== f.type) return false
    if (f.category_id && item.category_id !== f.category_id) return false
    if (f.account_id && item.account_id !== f.account_id) return false
    if (start && String(item.transaction_date).slice(0,10) < start) return false
    if (end && String(item.transaction_date).slice(0,10) > end) return false
    if (search && !(item.description || '').toLowerCase().includes(search)) return false
    return true
  })

  const arr = filtered.slice()
  const dir = String(ord.order_direction).toLowerCase() === 'asc' ? 1 : -1
  switch (ord.order_by) {
    case 'amount':
      arr.sort((a, b) => (Number(a.amount) - Number(b.amount)) * dir)
      break
    case 'description':
      arr.sort((a, b) => (String(a.description || '').localeCompare(String(b.description || ''), 'pt-BR')) * dir)
      break
    case 'created_at':
      arr.sort((a, b) => (new Date(a.created_at) - new Date(b.created_at)) * dir)
      break
    case 'transaction_date':
    default:
      arr.sort((a, b) => (new Date(a.transaction_date) - new Date(b.transaction_date)) * dir)
      break
  }
  return arr
})
const loading = computed(() => store.getters['transactions/transactionsLoading'])
const pagination = computed(() => store.state.transactions.pagination)
// Evita mutação direta do estado Vuex via v-model em objetos aninhados
const orderBy = computed({
  get: () => store.state.transactions.order.order_by,
  set: (val) => store.dispatch('transactions/setOrder', { order_by: val })
})
const orderDirection = computed({
  get: () => store.state.transactions.order.order_direction,
  set: (val) => store.dispatch('transactions/setOrder', { order_direction: val })
})

const accounts = computed(() => store.getters['accounts/accountsOptions'])
const categories = computed(() => store.getters['categories/activeCategoriesOptions'])

const localFilters = reactive({
  type: null,
  category_id: null,
  account_id: null,
  start_date: null,
  end_date: null,
  search: ''
})

const openCreate = ref(false)
const editTarget = ref(null)
const showConfirm = ref(false)
const confirmMessage = ref('Tem certeza que deseja excluir esta transação? Esta ação não pode ser desfeita.')

// Smooth and consistent input style across the page
const inputClasses = 'w-full h-10 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:ring-offset-0 transition-[box-shadow,border-color]'

async function applyFilters() {
  // setFilters agora já reseta a página e busca imediatamente
  await store.dispatch('transactions/setFilters', { ...localFilters })
}

async function resetFilters() {
  Object.assign(localFilters, { type: null, category_id: null, account_id: null, start_date: null, end_date: null, search: '' })
  await store.dispatch('transactions/clearFilters')
  await store.dispatch('transactions/fetchTransactions')
}

// changeOrder não é mais necessário pois v-model já dispara set individualmente

function changePerPage(val) {
  store.dispatch('transactions/setPerPage', Number(val))
}

function goToPage(p) {
  store.dispatch('transactions/setPage', p)
}

function formatCurrency(value) {
  try {
    const n = Number(value)
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
  } catch { return value }
}

function formatDate(value) {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleDateString('pt-BR')
}

function onSaved() {
  // no-op, list already refreshed in modal
}

function accountTypeLabel(type) {
  switch (type) {
    case 'checking': return 'corrente'
    case 'savings': return 'poupança'
    case 'wallet': return 'carteira'
    case 'credit_card': return 'cartão'
    default: return type || '-'
  }
}

function accountLabel(acc) {
  if (!acc) return '-'
  const type = accountTypeLabel(acc.type)
  return `${acc.name}${type ? ` (${type})` : ''}`
}

const totalPages = computed(() => {
  const per = Number(pagination.value.perPage) || 1
  const total = Number(pagination.value.total) || 0
  return Math.max(1, Math.ceil(total / per))
})

function onEdit(t) { editTarget.value = t }
function askDelete(t) { editTarget.value = t; showConfirm.value = true }
async function onDelete() {
  try {
    await store.dispatch('transactions/deleteTransaction', editTarget.value.id)
    showConfirm.value = false
    editTarget.value = null
    // refresh
    await store.dispatch('transactions/fetchTransactions')
  } catch (e) { console.error(e) }
}

onMounted(async () => {
  // ensure accounts and categories are loaded
  if (!store.getters['accounts/allAccounts']?.length) {
    try { await store.dispatch('accounts/fetchAccounts') } catch {}
  }
  if (!store.getters['categories/allCategories']?.length) {
    try { await store.dispatch('categories/fetchCategories') } catch {}
  }
  await store.dispatch('transactions/fetchTransactions')
})
</script>