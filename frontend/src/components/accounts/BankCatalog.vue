<template>
  <div class="space-y-8">
    <!-- Barra de busca e ordenação -->
    <section class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Buscar banco</label>
          <input v-model="search" type="text" :class="inputClasses" placeholder="Digite o nome do banco..." />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
          <select v-model="orderBy" :class="inputClasses">
            <option value="name">Nome</option>
            <option value="code">Código</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Direção</label>
          <select v-model="orderDir" :class="inputClasses">
            <option value="asc">Asc</option>
            <option value="desc">Desc</option>
          </select>
        </div>
      </div>
    </section>
    <!-- Catálogo de Bancos -->
    <section class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Catálogo de Bancos</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="bank in filteredBanks" :key="bank.id" class="rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-md flex items-center justify-center" :style="{ backgroundColor: bank.color }">
                <span class="w-5 h-5 text-white" v-html="icons[bank.id]?.svg"></span>
              </div>
              <div>
                <div class="font-medium text-gray-900">{{ bank.name }}</div>
                <div class="text-xs text-gray-500">{{ bank.code }}</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button @click="openSetup(bank)" class="px-3 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700">Adicionar</button>
            </div>
          </div>
          <!-- Nenhum campo inline; configuração ocorre no modal ao ativar -->
        </div>
      </div>
    </section>

    <!-- Contas Ativas -->
    <section class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Minhas Contas Ativas</h2>
        <span class="text-sm text-gray-500" v-if="loading">Carregando...</span>
      </div>
      <div v-if="activeAccounts.length" class="divide-y divide-gray-200">
        <div v-for="acc in activeAccounts" :key="acc.id" class="py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md" :style="{ backgroundColor: acc.color || '#64748b' }"></div>
            <div>
              <div class="text-sm font-medium text-gray-900">{{ acc.name }}</div>
              <div class="text-xs text-gray-500">{{ typeLabel(acc.type) }}</div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium" :class="getBalance(acc) >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatCurrency(getBalance(acc)) }}</span>
              <button @click="openEdit(acc)" class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Editar</button>
              <button @click="confirmDelete(acc)" class="px-3 py-1.5 rounded border border-rose-300 text-rose-700 hover:bg-rose-50">Excluir</button>
              <button @click="confirmArchive(acc)" class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Arquivar</button>
          </div>
        </div>
      </div>
      <div v-else class="text-center text-gray-500">
        Nenhuma conta ativa ainda. Ative bancos acima para criar suas contas.
      </div>
    </section>

    <!-- Contas Arquivadas -->
    <section class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Arquivadas</h2>
      <div v-if="archivedAccounts.length" class="divide-y divide-gray-200">
        <div v-for="acc in archivedAccounts" :key="acc.id" class="py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md" :style="{ backgroundColor: acc.color || '#94a3b8' }"></div>
            <div>
              <div class="text-sm font-medium text-gray-900">{{ acc.name }}</div>
              <div class="text-xs text-gray-500">{{ typeLabel(acc.type) }}</div>
            </div>
          </div>
          <div>
            <button @click="reactivate(acc)" class="px-3 py-1.5 rounded bg-emerald-600 text-white hover:bg-emerald-700">Reativar</button>
          </div>
        </div>
      </div>
      <div v-else class="text-gray-500">Nenhuma conta arquivada.</div>
    </section>

    <!-- Modal de confirmação -->
    <ConfirmModal
      v-model="confirm.open"
      :title="confirm.title"
      :message="confirm.message"
      :confirm-text="confirm.confirmText || 'Confirmar'"
      cancel-text="Cancelar"
      @confirm="confirm.onConfirm && confirm.onConfirm()"
      @cancel="confirm.open = false"
    />

    <!-- Modal de configuração inicial -->
    <AccountSetupModal
      v-model="setup.open"
      :bank="setup.bank"
      :form="setup.form"
      title="Ativar conta"
      confirm-text="Ativar"
      @confirm="createFromSetup"
      @cancel="setup.open = false"
    />

    <!-- Modal de edição -->
    <AccountSetupModal
      v-model="edit.open"
      :form="edit.form"
      title="Editar conta"
      confirm-text="Salvar"
      @confirm="saveEdit"
      @cancel="edit.open = false"
    />

    <!-- Toast simples -->
    <transition name="fade">
      <div v-if="toast.show" class="fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-2 rounded shadow-lg">{{ toast.message }}</div>
    </transition>
  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted, onBeforeUnmount } from 'vue'
import { useStore } from 'vuex'
import { BANK_ICONS } from '@/assets/banks/icons'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import AccountSetupModal from '@/components/accounts/AccountSetupModal.vue'

const store = useStore()

// Catálogo fixo simples (poderia vir de API)
const icons = BANK_ICONS
const banks = ref([
  { id: 'nubank', name: 'Nubank', code: '260', color: icons.nubank.color },
  { id: 'itau', name: 'Itaú', code: '341', color: icons.itau.color },
  { id: 'bradesco', name: 'Bradesco', code: '237', color: icons.bradesco.color },
  { id: 'santander', name: 'Santander', code: '033', color: icons.santander.color },
  { id: 'caixa', name: 'Caixa', code: '104', color: icons.caixa.color },
  { id: 'bb', name: 'Banco do Brasil', code: '001', color: icons.bb.color },
])

const loading = computed(() => store.getters['accounts/accountsLoading'])
const allAccounts = computed(() => store.getters['accounts/allAccounts'])
const activeAccounts = computed(() => store.getters['accounts/activeAccounts'])
const archivedAccounts = computed(() => allAccounts.value.filter(a => !a.is_active))

const inputClasses = 'w-full h-10 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:ring-offset-0 transition-[box-shadow,border-color]'

// Estado do modal de configuração
const setup = reactive({ open: false, bank: null, form: { name: '', type: 'checking', initial_balance: 0, color: '#64748b' } })
function openSetup(bank) {
  setup.bank = bank
  setup.form.name = bank.name
  setup.form.type = 'checking'
  setup.form.initial_balance = 0
  setup.form.color = bank.color
  setup.open = true
}

// Para permitir múltiplas contas por banco, o card sempre exibe "Adicionar".

function typeLabel(type) {
  return type === 'checking' ? 'Conta corrente' : type === 'savings' ? 'Poupança' : 'Carteira'
}

// Helpers de saldo e formatação
function getBalance(acc) {
  const val = acc?.current_balance ?? acc?.balance ?? acc?.initial_balance ?? 0
  return Number(val)
}

function formatCurrency(value) {
  try {
    const n = Number(value)
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
  } catch {
    return value
  }
}

// Helpers de máscara usados no modal (mantidos aqui se precisarmos no futuro)
function toBRLString(n) {
  const num = Number.isFinite(Number(n)) ? Number(n) : 0
  return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Toggle removido: ação agora é feita por botões Adicionar/Reativar/Ativo

async function createFromSetup() {
  if (!setup.bank) return
  try {
    await store.dispatch('accounts/createAccount', {
      bank_id: setup.bank.id,
      name: setup.form.name || setup.bank.name,
      type: setup.form.type,
      initial_balance: setup.form.initial_balance,
      color: setup.form.color,
      is_active: true,
      include_in_total: true,
    })
    await store.dispatch('accounts/fetchAccounts')
    toastMsg('Conta criada')
  } catch (e) {
    console.error('Erro ao criar conta', e)
  } finally {
    setup.open = false
  }
}

// Removido: salvamento inline não é mais necessário; usamos o modal de setup

async function archive(acc) {
  try {
    await store.dispatch('accounts/updateAccount', { id: acc.id, data: { is_active: false } })
    await store.dispatch('accounts/fetchAccounts')
    toastMsg('Conta arquivada')
  } catch (e) {
    console.error('Erro ao arquivar conta', e)
  }
}

onMounted(async () => {
  try { await store.dispatch('accounts/fetchAccounts') } catch {}
})

// Busca e ordenação
const search = ref('')
const orderBy = ref('name')
const orderDir = ref('asc')
const filteredBanks = computed(() => {
  const term = search.value.trim().toLowerCase()
  const list = banks.value.filter(b => !term || b.name.toLowerCase().includes(term) || b.code.includes(term))
  return list.sort((a, b) => {
    const va = (a[orderBy.value] || '').toString().toLowerCase()
    const vb = (b[orderBy.value] || '').toString().toLowerCase()
    if (va < vb) return orderDir.value === 'asc' ? -1 : 1
    if (va > vb) return orderDir.value === 'asc' ? 1 : -1
    return 0
  })
})

// Confirmações e toasts
const confirm = reactive({ open: false, title: 'Arquivar conta', message: '', onConfirm: null })
function confirmArchive(acc) {
  confirm.title = 'Arquivar conta'
  confirm.message = `Arquivar a conta "${acc.name}"?`
  confirm.confirmText = 'Arquivar'
  confirm.onConfirm = async () => {
    confirm.open = false
    await archive(acc)
  }
  confirm.open = true
}

function confirmDelete(acc) {
  confirm.title = 'Excluir conta'
  confirm.message = `Excluir a conta "${acc.name}" e todas as suas transações? Esta ação não pode ser desfeita.`
  confirm.confirmText = 'Excluir'
  confirm.onConfirm = async () => {
    confirm.open = false
    await remove(acc)
  }
  confirm.open = true
}
function toastMsg(msg) {
  toast.message = msg
  toast.show = true
  clearTimeout(toast.timer)
  toast.timer = setTimeout(() => (toast.show = false), 2200)
}
const toast = reactive({ show: false, message: '', timer: null })
onBeforeUnmount(() => {
  if (toast.timer) clearTimeout(toast.timer)
})

async function reactivate(acc) {
  try {
    await store.dispatch('accounts/reactivateAccount', acc.id)
    await store.dispatch('accounts/fetchAccounts')
    toastMsg('Conta reativada')
  } catch (e) {
    console.error('Erro ao reativar conta', e)
  }
}

// Edição de conta
const edit = reactive({ open: false, account: null, form: { name: '', type: 'checking', initial_balance: 0, color: '#64748b' } })
function openEdit(acc) {
  edit.account = acc
  edit.form.name = acc.name
  edit.form.type = acc.type
  // Para edição, usamos initial_balance atual para permitir correção; backend ajustará current_balance
  edit.form.initial_balance = Number(acc.initial_balance ?? 0)
  edit.form.color = acc.color || '#64748b'
  edit.open = true
}
async function saveEdit() {
  if (!edit.account) return
  try {
    await store.dispatch('accounts/updateAccount', {
      id: edit.account.id,
      data: {
        name: edit.form.name,
        type: edit.form.type,
        initial_balance: edit.form.initial_balance,
        color: edit.form.color,
      }
    })
    await store.dispatch('accounts/fetchAccounts')
    toastMsg('Conta atualizada')
  } catch (e) {
    console.error('Erro ao atualizar conta', e)
  } finally {
    edit.open = false
  }
}

async function remove(acc) {
  try {
    await store.dispatch('accounts/deleteAccount', acc.id)
    await store.dispatch('accounts/fetchAccounts')
    toastMsg('Conta excluída')
  } catch (e) {
    console.error('Erro ao excluir conta', e)
  }
}
</script>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
