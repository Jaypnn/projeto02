<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop with transition -->
    <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div class="absolute inset-0 bg-black/40" @click="onClose" />
    </Transition>

    <!-- Modal with transition -->
    <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95">
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4 ring-1 ring-black/5">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-xl font-semibold text-gray-900">Nova Transação</h3>
          <button @click="onClose" class="text-gray-500 hover:text-gray-700 transition-colors" aria-label="Fechar">✕</button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <select v-model="form.type" :class="inputClasses">
              <option value="income">Receita</option>
              <option value="expense">Despesa</option>
              <option value="transfer">Transferência</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
            <input v-model="form.transaction_date" type="date" :class="inputClasses" required />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
            <select v-model.number="form.account_id" :class="inputClasses" required>
              <option :value="undefined" disabled>Selecione...</option>
              <option v-for="acc in accounts" :key="acc.value" :value="acc.value">{{ acc.label }}</option>
            </select>
          </div>

          <div v-if="form.type === 'transfer'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Conta destino</label>
            <select v-model.number="form.destination_account_id" :class="inputClasses">
              <option :value="undefined" disabled>Selecione...</option>
              <option v-for="acc in accounts" :key="'dst-' + acc.value" :value="acc.value">{{ acc.label }}</option>
            </select>
          </div>

          <div v-else>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
            <select v-model.number="form.category_id" :class="inputClasses" required>
              <option :value="undefined" disabled>Selecione...</option>
              <option v-for="cat in filteredCategories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
              <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">R$</span>
                <input
                  :value="displayAmount"
                  @input="handleAmountInput"
                  @blur="formatAmountOnBlur"
                  inputmode="decimal"
                  type="text"
                  :class="[inputClasses, 'text-right pl-10']"
                  placeholder="0,00"
                  aria-label="Valor"
                  required
                />
              </div>
              <p v-if="amountError" class="mt-1 text-xs text-rose-600">Informe um valor maior que 0.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="form.status" :class="inputClasses">
              <option value="completed">Completada</option>
              <option value="pending">Pendente</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
          <input v-model="form.description" type="text" :class="inputClasses" placeholder="Ex.: Mercado, aluguel, salário..." required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
          <textarea v-model="form.notes" :class="inputClasses" rows="3" />
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" @click="onClose" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</button>
          <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 shadow-sm">
            {{ submitting ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
        </form>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, reactive, watch, ref, onMounted, onBeforeUnmount } from 'vue'
import { useStore } from 'vuex'

const props = defineProps({
  open: { type: Boolean, default: false }
})
const emit = defineEmits(['close', 'saved'])

const store = useStore()

const accounts = computed(() => store.getters['accounts/accountsOptions'])
const categories = computed(() => store.getters['categories/activeCategoriesOptions'])

const form = reactive({
  type: 'expense',
  transaction_date: new Date().toISOString().slice(0, 10),
  account_id: undefined,
  destination_account_id: undefined,
  category_id: undefined,
  amount: undefined,
  status: 'completed',
  description: '',
  notes: ''
})

const submitting = ref(false)
const displayAmount = ref('')
const amountError = ref(false)

// Consistent, smooth and visible style for inputs/selects/textarea
const inputClasses = 'w-full h-10 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:ring-offset-0 transition-[box-shadow,border-color]'

function toBRLString(n) {
  const num = Number.isFinite(Number(n)) ? Number(n) : 0
  return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function handleAmountInput(e) {
  // Keep only digits; build cents mask for pt-BR
  const digits = (e.target.value || '').replace(/\D/g, '')
  const cents = digits ? parseInt(digits, 10) : 0
  const val = cents / 100
  displayAmount.value = toBRLString(val)
  form.amount = val
  amountError.value = val <= 0
}

function formatAmountOnBlur() {
  // Ensure two decimals and set error state
  displayAmount.value = toBRLString(form.amount || 0)
  amountError.value = (form.amount || 0) <= 0
}

// Keep display in sync when modal opens or type changes
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    displayAmount.value = form.amount ? toBRLString(form.amount) : ''
    amountError.value = false
  }
})

const filteredCategories = computed(() => {
  const type = form.type === 'transfer' ? 'expense' : form.type
  return (categories.value || []).filter(c => c.type === type)
})

watch(() => form.type, (val) => {
  if (val === 'transfer') {
    form.category_id = undefined
  } else {
    form.destination_account_id = undefined
  }
})

function onClose() {
  emit('close')
}

async function handleSubmit() {
  submitting.value = true
  try {
    amountError.value = (form.amount || 0) <= 0
    if (amountError.value) {
      submitting.value = false
      return
    }
    const payload = { ...form }
    const created = await store.dispatch('transactions/createTransaction', payload)
    emit('saved', created)
    emit('close')
    // reload list
    await store.dispatch('transactions/fetchTransactions')
  } catch (e) {
    console.error('Erro ao salvar transação', e)
  } finally {
    submitting.value = false
  }
}

// ESC to close for comfort
function onKeydown(e) {
  if (e.key === 'Escape') onClose()
}
onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
/* Hide spinner arrows for numeric inputs (avoid sequential steppers) */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  appearance: textfield;
  -moz-appearance: textfield; /* Firefox */
}
</style>
