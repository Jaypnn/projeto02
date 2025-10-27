<template>
  <div class="bg-white shadow rounded-lg">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
      <h3 class="text-base font-semibold text-gray-900">Insights</h3>
    </div>
    <div class="p-4">
      <div v-if="!items?.length" class="text-gray-500 text-center">Sem insights no momento.</div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="(ins, idx) in items" :key="idx" class="border rounded-md p-3" :class="boxClass(ins.type)">
          <div class="text-sm font-medium mb-1">{{ ins.title }}</div>
          <ul class="list-disc ml-5 text-xs text-gray-700 space-y-1">
            <li v-for="(d, i) in ins.details" :key="i">{{ d }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ items: { type: Array, default: () => [] } })
function boxClass(type){
  return type === 'alert' ? 'border-red-200 bg-red-50' : (type === 'info' ? 'border-blue-200 bg-blue-50' : 'border-gray-200')
}
</script>

<style scoped>
</style>