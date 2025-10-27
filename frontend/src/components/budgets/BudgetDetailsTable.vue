<template>
  <div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
      <h3 class="text-base font-semibold text-gray-900">Detalhamento por categoria</h3>
      <span class="text-xs text-gray-500">Período: {{ formatDate(dateRange.start) }} – {{ formatDate(dateRange.end) }}</span>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Meta</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gasto até hoje</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pacing</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Previsto EOM</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Diferença</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-if="!rows?.length">
            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Sem categorias no orçamento.</td>
          </tr>
          <tr v-for="r in rows" :key="r.id">
            <td class="px-4 py-2 text-sm text-gray-900">{{ r.category }}</td>
            <td class="px-4 py-2 text-sm text-right">R$ {{ formatCurrency(r.allocated) }}</td>
            <td class="px-4 py-2 text-sm text-right">R$ {{ formatCurrency(r.spent) }}</td>
            <td class="px-4 py-2 text-sm text-right">
              <span :class="pacingColor(r)">{{ (r.pacing*100).toFixed(0) }}%</span>
            </td>
            <td class="px-4 py-2 text-sm text-right" :class="r.forecast <= r.allocated ? 'text-gray-900' : 'text-red-600'">R$ {{ formatCurrency(r.forecast) }}</td>
            <td class="px-4 py-2 text-sm text-right" :class="r.diff >= 0 ? 'text-green-600' : 'text-red-600'">R$ {{ formatCurrency(r.diff) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  rows: { type: Array, default: () => [] },
  dateRange: { type: Object, required: true }
})

function formatDate(d){
  const dt = new Date(d)
  return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
}
function formatCurrency(n){ return (Number(n)||0).toFixed(2).replace('.', ',') }
function pacingColor(r){
  if (r.pacing < 0.9) return 'text-green-600'
  if (r.pacing <= 1.1) return 'text-yellow-600'
  return 'text-red-600'
}
</script>

<style scoped>
</style>