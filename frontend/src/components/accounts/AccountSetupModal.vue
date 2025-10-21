<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="onCancel"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-9 h-9 rounded-md" :style="{ backgroundColor: form.color }"></div>
              <div>
                <h3 class="text-base font-semibold text-gray-900">{{ headerTitle }}</h3>
                <p v-if="bank?.code" class="text-xs text-gray-500">Código {{ bank.code }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Apelido</label>
                <input v-model="form.name" :class="inputClasses" placeholder="Ex.: Conta salário" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select v-model="form.type" :class="inputClasses">
                  <option value="checking">Conta corrente</option>
                  <option value="savings">Poupança</option>
                  <option value="wallet">Carteira</option>
                  <option value="cash">Dinheiro</option>
                  <option value="credit_card">Cartão de crédito</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Saldo inicial</label>
                <div class="relative">
                  <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">R$</span>
                  <input :value="initialStr" @input="onInitialInput" @blur="onInitialBlur" type="text" inputmode="decimal" :class="[inputClasses, 'text-right pl-10']" placeholder="0,00" />
                </div>
                <p class="mt-1 text-xs text-gray-500">Digite números e iremos formatar (ex.: 1 → 0,01; 1234 → 12,34). Use "-" para negativo.</p>
              </div>

              <div class="flex items-center gap-3">
                <input type="color" v-model="form.color" class="h-10 w-12 rounded border border-gray-300" />
                <span class="text-xs text-gray-500">Cor</span>
              </div>
            </div>
          </div>

          <div class="px-5 pb-5 pt-2 flex justify-end gap-2">
            <button type="button" class="px-4 h-9 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="onCancel">Cancelar</button>
            <button type="button" class="px-4 h-9 rounded-md bg-indigo-600 text-white hover:bg-indigo-700" @click="onConfirm">{{ confirmText }}</button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, defineEmits, defineProps, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  bank: { type: Object, default: null },
  form: { type: Object, default: () => ({ name: '', type: 'checking', initial_balance: 0, color: '#64748b' }) },
  title: { type: String, default: '' },
  confirmText: { type: String, default: 'Ativar' },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const inputClasses = 'w-full h-10 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 focus:ring-offset-0 transition-[box-shadow,border-color]'

const initialStr = ref('')
function formatBRL(n) {
  const v = Number(n || 0)
  return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Atualiza a string do campo quando o modal abre
watch(() => props.modelValue, (open) => {
  if (open) {
    const n = Number(props.form.initial_balance || 0)
    initialStr.value = n === 0 ? '' : formatBRL(n)
  }
})

const headerTitle = computed(() => props.title || (props.bank?.name ? `Ativar ${props.bank.name}` : 'Editar conta'))

function onInitialInput(e) {
  const raw = e.target.value || ''
  // Permite apenas '-' sozinho enquanto digita
  if (raw === '-') {
    initialStr.value = '-'
    props.form.initial_balance = 0
    return
  }
  const negative = raw.trim().startsWith('-')
  const digits = raw.replace(/\D/g, '')
  if (!digits) {
    initialStr.value = ''
    props.form.initial_balance = 0
    return
  }
  const cents = parseInt(digits, 10)
  const val = (cents / 100) * (negative ? -1 : 1)
  props.form.initial_balance = val
  initialStr.value = formatBRL(val)
}

function onInitialBlur() {
  // Se o usuário deixar vazio, mantemos vazio visualmente e 0 no modelo
  if (!initialStr.value || initialStr.value === '-') {
    initialStr.value = ''
    props.form.initial_balance = 0
  } else {
    // Reaplica formatação
    initialStr.value = formatBRL(props.form.initial_balance)
  }
}

function onCancel() {
  emit('update:modelValue', false)
  emit('cancel')
}

function onConfirm() {
  emit('confirm', { ...props.form })
  emit('update:modelValue', false)
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
