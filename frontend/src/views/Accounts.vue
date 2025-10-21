<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Contas</h1>
        <p class="mt-1 text-sm text-gray-500">Gerencie suas contas bancárias e cartões</p>
      </div>
      <button class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
        <PlusIcon class="w-4 h-4 mr-2" />
        Nova Conta
      </button>
    </div>

    <BankCatalog />
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'
import BankCatalog from '@/components/accounts/BankCatalog.vue'
</script>