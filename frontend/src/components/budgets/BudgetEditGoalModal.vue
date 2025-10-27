<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">Editar meta</h3>
            <p class="text-sm text-gray-600">Atualize o valor alocado para esta categoria.</p>
          </div>
          <form class="p-5 space-y-4" @submit.prevent="save" novalidate>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Valor alocado</label>
              <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">R$</span>
                <input
                  :value="displayAmount"
                  @input="handleAmountInput"
                  @keydown="onAmountKeydown"
                  @blur="formatAmountOnBlur"
                  inputmode="numeric"
                  autocomplete="off"
                  type="text"
                  class="w-full h-10 rounded-lg border border-gray-300 bg-white text-right pr-3 pl-10 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400"
                  placeholder="0,00"
                />
              </div>
              <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
              <button type="button" class="px-4 h-9 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="$emit('close')">Cancelar</button>
              <button type="submit" :disabled="saving" class="px-4 h-9 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60">
                {{ saving ? 'Salvando...' : 'Salvar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { budgetService } from '@/services'

const props = defineProps({
  open: { type: Boolean, default: false },
  budgetId: { type: [String, Number], required: true },
  budgetCategoryId: { type: [String, Number], required: true },
  currentAllocated: { type: [Number, String], default: 0 }
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const error = ref('')
const amountNum = ref(0)
const displayAmount = ref('')

function toBRLString(n){
  const num = Number.isFinite(Number(n)) ? Number(n) : 0
  return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function setFromNumber(n){
  amountNum.value = Number(n || 0)
  displayAmount.value = amountNum.value ? toBRLString(amountNum.value) : ''
}

function handleAmountInput(e){
  const digits = (e.target.value || '').replace(/\D/g, '')
  if (!digits) { setFromNumber(0); return }
  const cents = parseInt(digits, 10)
  const val = cents / 100
  setFromNumber(val)
}
function formatAmountOnBlur(){ if (displayAmount.value === '') return; displayAmount.value = toBRLString(amountNum.value || 0) }
function onAmountKeydown(e){
  const allowedControl = [8,9,13,27,46,35,36,37,38,39,40]
  const isCtrl = e.ctrlKey || e.metaKey
  if (isCtrl && ['a','c','v','x'].includes(e.key.toLowerCase())) return
  if (allowedControl.includes(e.keyCode)) return
  if (/^[0-9]$/.test(e.key)) return
  e.preventDefault()
}

watch(() => props.open, (val) => {
  if (val) setFromNumber(props.currentAllocated)
})

onMounted(() => { if (props.open) setFromNumber(props.currentAllocated) })

async function save(){
  error.value = ''
  if (amountNum.value < 0 || !Number.isFinite(amountNum.value)) {
    error.value = 'Informe um valor válido.'
    return
  }
  saving.value = true
  try {
    await budgetService.updateCategory(props.budgetId, props.budgetCategoryId, { allocated_amount: Number(amountNum.value || 0) })
    emit('saved')
  } catch (e) {
    console.error(e)
    error.value = 'Não foi possível salvar a meta.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
