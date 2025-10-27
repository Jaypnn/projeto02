<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/30" @click="$emit('close')"></div>
    <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-lg p-6">
      <div class="flex items-start justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Novo Orçamento</h3>
          <p class="text-sm text-gray-500">Crie um orçamento para o mês selecionado</p>
        </div>
        <button class="text-gray-400 hover:text-gray-600" @click="$emit('close')">✕</button>
      </div>

      <form @submit.prevent="submit" class="space-y-4" novalidate>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Valor</label>
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
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-right pl-10"
                placeholder="0,00"
                aria-label="Valor"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Data prevista (mês/ano)</label>
            <input v-model="form.month" type="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
          </div>
          <div>
            <CategorySelect v-model="form.category_id" :options="categoryOptions" label="Categoria" placeholder="Selecione uma categoria" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" class="inline-flex items-center px-3 py-2 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="$emit('close')">Cancelar</button>
          <button type="submit" :disabled="submitting" class="inline-flex items-center px-3 py-2 text-sm rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60">
            {{ submitting ? 'Criando...' : 'Criar Orçamento' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useStore } from 'vuex'
import CategorySelect from '@/components/categories/CategorySelect.vue'
import { budgetService } from '@/services'

const props = defineProps({ month: { type: String, required: true } })
const emit = defineEmits(['close', 'created'])

const store = useStore()

const form = ref({
  total_amount_num: 0,
  period_type: 'monthly',
  month: '',
  category_id: null,
})

const submitting = ref(false)
const displayAmount = ref('')

const categoryOptions = computed(() => store.getters['categories/activeCategoriesOptions'] || [])

const periodStart = computed(() => new Date(`${(form.value.month || props.month)}-01T00:00:00`))
const periodEnd = computed(() => {
  const d = new Date(periodStart.value)
  d.setMonth(d.getMonth() + 1)
  d.setDate(0)
  return d
})

function fmt(d){ return d.toISOString().slice(0,10) }

async function ensureCategories(){
  try {
    const list = store.state.categories?.categories || []
    if (!Array.isArray(list) || list.length === 0) {
      await store.dispatch('categories/fetchCategories')
    }
  } catch {}
}

function init() {
  form.value.month = props.month
  displayAmount.value = ''
  ensureCategories()
}

watch(() => props.month, init, { immediate: true })

function toBRLString(n){
  const num = Number.isFinite(Number(n)) ? Number(n) : 0
  return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function handleAmountInput(e){
  const digits = (e.target.value || '').replace(/\D/g, '')
  if (!digits) {
    displayAmount.value = ''
    form.value.total_amount_num = 0
    return
  }
  const cents = parseInt(digits, 10)
  const val = cents / 100
  displayAmount.value = toBRLString(val)
  form.value.total_amount_num = val
}
function formatAmountOnBlur(){
  if ((displayAmount.value || '') === '') return
  displayAmount.value = toBRLString(form.value.total_amount_num || 0)
}
function onAmountKeydown(e){
  const allowedControl = [8,9,13,27,46,35,36,37,38,39,40]
  const isCtrl = e.ctrlKey || e.metaKey
  if (isCtrl && ['a','c','v','x'].includes(e.key.toLowerCase())) return
  if (allowedControl.includes(e.keyCode)) return
  if (/^[0-9]$/.test(e.key)) return
  e.preventDefault()
}

async function submit() {
  submitting.value = true
  try {
    const start = periodStart.value
    const end = periodEnd.value
    const monthStr = start.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
    const getById = store.getters['categories/categoryById']
    const cat = typeof getById === 'function' ? getById(form.value.category_id) : null
    const catName = cat?.name || 'Orçamento'

    const payload = {
      name: `${catName} ${monthStr}`,
      total_amount: Number(form.value.total_amount_num || 0),
      period_type: 'monthly',
      start_date: fmt(start),
      end_date: fmt(end),
    }

    if (form.value.category_id) {
      payload.categories = [
        { category_id: form.value.category_id, allocated_amount: Number(form.value.total_amount_num || 0) }
      ]
    }

    await budgetService.create(payload)
    emit('created')
  } catch (e) {
    console.error(e)
    alert('Não foi possível criar o orçamento. Verifique os dados.')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
</style>
