<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Transações</h1>
        <p class="mt-1 text-sm text-gray-500">Gerencie suas receitas e despesas</p>
      </div>
      <button @click="openModal = true" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
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
          <select v-model="order.order_by" @change="changeOrder" :class="inputClasses">
            <option value="transaction_date">Data</option>
            <option value="amount">Valor</option>
            <option value="description">Descrição</option>
          </select>
          <select v-model="order.order_direction" @change="changeOrder" :class="inputClasses">
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
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="t in transactions" :key="t.id" class="odd:bg-white even:bg-gray-50/50 hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatDate(t.transaction_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ t.description }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ t.category?.name || (t.type==='transfer' ? 'Transferência' : '-') }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ t.account?.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right" :class="t.type==='income' ? 'text-emerald-600' : (t.type==='expense' ? 'text-rose-600' : 'text-slate-700')">
                {{ formatCurrency(t.amount) }}
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
      <div v-if="pagination.total > pagination.perPage" class="px-6 py-3 border-t flex items-center justify-between">
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50" :disabled="pagination.currentPage <= 1" @click="goToPage(pagination.currentPage - 1)">Anterior</button>
        <span class="text-sm text-gray-600">Página {{ pagination.currentPage }}</span>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50" @click="goToPage(pagination.currentPage + 1)">Próxima</button>
      </div>
    </div>

    <TransactionFormModal :open="openModal" @close="openModal = false" @saved="onSaved" />
  </div>
  
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
import { PlusIcon, CreditCardIcon } from '@heroicons/vue/24/outline'
import TransactionFormModal from '@/components/transactions/TransactionFormModal.vue'

const store = useStore()

const transactions = computed(() => store.getters['transactions/allTransactions'])
const loading = computed(() => store.getters['transactions/transactionsLoading'])
const pagination = computed(() => store.state.transactions.pagination)
const order = computed({
  get: () => store.state.transactions.order,
  set: (val) => store.dispatch('transactions/setOrder', val)
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

const openModal = ref(false)

// Smooth and consistent input style across the page
const inputClasses = 'w-full h-10 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:ring-offset-0 transition-[box-shadow,border-color]'

function applyFilters() {
  store.dispatch('transactions/setFilters', { ...localFilters })
  store.dispatch('transactions/setPage', 1)
  store.dispatch('transactions/fetchTransactions')
}

function resetFilters() {
  Object.assign(localFilters, { type: null, category_id: null, account_id: null, start_date: null, end_date: null, search: '' })
  store.dispatch('transactions/clearFilters')
  store.dispatch('transactions/fetchTransactions')
}

function changeOrder() {
  store.dispatch('transactions/setOrder', order.value)
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