<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">Adicionar contribuição</h3>
            <p class="text-sm text-gray-600">Informe o valor que deseja contribuir para esta meta.</p>
          </div>
          <form class="p-5 space-y-4" @submit.prevent="save" novalidate>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
              <CurrencyInput v-model="amount" />
              <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
              <input v-model="description" type="text" class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400" />
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
              <button type="button" class="px-4 h-9 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="$emit('close')">Cancelar</button>
              <button type="submit" :disabled="saving" class="px-4 h-9 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60">
                {{ saving ? 'Adicionando...' : 'Adicionar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </teleport>
  </template>

<script setup>
import { ref } from 'vue'
import { useStore } from 'vuex'
import CurrencyInput from '@/components/ui/CurrencyInput.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  goalId: { type: [String, Number], required: true }
})
const emit = defineEmits(['close', 'saved'])

const store = useStore()
const amount = ref(null)
const description = ref('')
const saving = ref(false)
const error = ref('')

async function save(){
  error.value = ''
  if (!amount.value || Number(amount.value) <= 0){
    error.value = 'Informe um valor válido.'
    return
  }
  try {
    saving.value = true
    const res = await store.dispatch('goals/addContribution', { goalId: props.goalId, amount: Number(amount.value), description: description.value || undefined })
    emit('saved', res)
  } catch (e) {
    console.error(e)
    error.value = e?.response?.data?.message || 'Erro ao adicionar contribuição.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
