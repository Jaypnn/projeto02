<template>
  <div class="space-y-2">
    <div class="relative">
      <input
        v-model="inner"
        type="date"
        class="w-full h-10 rounded-lg border border-gray-300 bg-white px-3 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400"
        :min="minDate"
      />
    </div>
    <div class="flex flex-wrap gap-2">
      <button type="button" class="px-2.5 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="addDays(30)">+30 dias</button>
      <button type="button" class="px-2.5 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="addMonths(3)">+3 meses</button>
      <button type="button" class="px-2.5 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="addMonths(6)">+6 meses</button>
      <button type="button" class="px-2.5 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="addYears(1)">+1 ano</button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const inner = ref('')

watch(() => props.modelValue, (v) => { inner.value = v || '' }, { immediate: true })
watch(inner, (v) => emit('update:modelValue', v))

const minDate = computed(() => {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
})

function toISO(d){
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
function addDays(n){
  const base = new Date()
  base.setDate(base.getDate() + n)
  inner.value = toISO(base)
}
function addMonths(n){
  const base = new Date()
  base.setMonth(base.getMonth() + n)
  inner.value = toISO(base)
}
function addYears(n){
  const base = new Date()
  base.setFullYear(base.getFullYear() + n)
  inner.value = toISO(base)
}
</script>
