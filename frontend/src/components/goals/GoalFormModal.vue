<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
        <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="p-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">{{ isEdit ? 'Editar meta' : 'Nova meta' }}</h3>
            <p class="text-sm text-gray-600">Preencha os dados da sua meta financeira.</p>
          </div>
          <form class="p-5 space-y-4" @submit.prevent="onSubmit" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input v-model="form.name" type="text" class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select v-model="form.goal_type" class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400" required>
                  <option value="savings">Poupança</option>
                  <option value="investment">Investimento</option>
                  <option value="purchase">Compra</option>
                  <option value="debt_payment">Pagamento de dívida</option>
                  <option value="emergency_fund">Fundo de emergência</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor alvo</label>
                <CurrencyInput v-model="form.target_amount" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data alvo</label>
                <DateInput v-model="form.target_date" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade</label>
                <select v-model="form.priority" class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                  <option value="low">Baixa</option>
                  <option value="medium">Média</option>
                  <option value="high">Alta</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contribuição mensal (opcional)</label>
                <CurrencyInput v-model="form.monthly_contribution" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria associada (opcional)</label>
                <select v-model.number="form.category_id" class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400">
                  <option :value="null">Sem categoria</option>
                  <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Se você associar uma categoria, as receitas dessa categoria podem atualizar automaticamente o valor atual da meta.</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor inicial (opcional)</label>
                <CurrencyInput v-model="form.current_amount" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Anotações (opcional)</label>
                <input v-model="form.notes" type="text" class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
                <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400"></textarea>
              </div>
            </div>

            <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>

            <div class="flex items-center justify-end gap-2 pt-2">
              <button type="button" class="px-4 h-9 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="$emit('close')">Cancelar</button>
              <button type="submit" :disabled="saving" class="px-4 h-9 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60">
                {{ saving ? 'Salvando...' : (isEdit ? 'Salvar' : 'Criar') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useStore } from 'vuex'
import CurrencyInput from '@/components/ui/CurrencyInput.vue'
import DateInput from '@/components/ui/DateInput.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  goal: { type: Object, default: null },
  categoryOptions: { type: Array, default: () => [] }
})

const emit = defineEmits(['close', 'saved'])

const store = useStore()
const saving = ref(false)
const error = ref('')
const isEdit = computed(() => !!props.goal)

const emptyForm = () => ({
  name: '',
  description: '',
  target_amount: null,
  current_amount: 0,
  target_date: '',
  priority: 'medium',
  goal_type: 'savings',
  monthly_contribution: null,
  notes: '',
  category_id: null
})

const form = reactive(emptyForm())

watch(() => props.open, (val) => {
  if (val) {
    error.value = ''
    if (props.goal) Object.assign(form, emptyForm(), props.goal)
    else Object.assign(form, emptyForm())
  }
})

// Min date agora é calculado dentro do DateInput

async function onSubmit(){
  error.value = ''
  // simple validations
  if (!form.name || !form.target_amount || !form.target_date) {
    error.value = 'Preencha os campos obrigatórios.'
    return
  }
  if (Number(form.current_amount || 0) >= Number(form.target_amount || 0)){
    error.value = 'O valor inicial não pode ser maior ou igual ao valor alvo.'
    return
  }

  saving.value = true
  try {
    const payload = { ...form }
    if (!isEdit.value) {
      const created = await store.dispatch('goals/createGoal', payload)
      emit('saved', created)
    } else {
      const updated = await store.dispatch('goals/updateGoal', { id: props.goal.id, data: payload })
      emit('saved', updated)
    }
  } catch (e) {
    console.error(e)
    error.value = e?.response?.data?.message || 'Não foi possível salvar a meta.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
