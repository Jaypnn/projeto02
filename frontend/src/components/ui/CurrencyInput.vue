<template>
  <div class="relative">
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">R$</span>
    <input
      :value="display"
      @input="onInput"
      @keydown="onKeydown"
      @blur="onBlur"
      inputmode="numeric"
      autocomplete="off"
      type="text"
      class="w-full h-10 rounded-lg border border-gray-300 bg-white text-right pr-3 pl-10 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400"
      :placeholder="placeholder"
      :disabled="disabled"
    />
  </div>
</template>

<script setup>
import { computed, watch, ref } from 'vue'

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  placeholder: { type: String, default: '0,00' },
  disabled: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue'])

const amountNum = ref(0)
const display = ref('')

function toBRLString(n){
  const num = Number.isFinite(Number(n)) ? Number(n) : 0
  return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function setFromNumber(n){
  amountNum.value = Number(n || 0)
  display.value = amountNum.value ? toBRLString(amountNum.value) : ''
}

watch(() => props.modelValue, (v) => {
  if (v === null || v === undefined || v === '') { amountNum.value = 0; display.value = '' }
  else setFromNumber(v)
}, { immediate: true })

function onInput(e){
  const digits = (e.target.value || '').replace(/\D/g, '')
  if (!digits) { setFromNumber(0); emit('update:modelValue', 0); return }
  const cents = parseInt(digits, 10)
  const val = cents / 100
  setFromNumber(val)
  emit('update:modelValue', val)
}
function onBlur(){ if (display.value === '') return; display.value = toBRLString(amountNum.value || 0) }
function onKeydown(e){
  const allowedControl = [8,9,13,27,46,35,36,37,38,39,40]
  const isCtrl = e.ctrlKey || e.metaKey
  if (isCtrl && ['a','c','v','x'].includes(e.key.toLowerCase())) return
  if (allowedControl.includes(e.keyCode)) return
  if (/^[0-9]$/.test(e.key)) return
  e.preventDefault()
}
</script>
