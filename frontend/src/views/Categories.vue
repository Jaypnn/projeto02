<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Categorias</h1>
        <p class="mt-1 text-sm text-gray-500">
          Organize suas transações por categorias
        </p>
      </div>
      <button class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
        <PlusIcon class="w-4 h-4 mr-2" />
        Nova Categoria
      </button>
    </div>

    <div class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
      <TagIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">Em desenvolvimento</h3>
      <p>A interface de categorias será implementada em breve.</p>
    </div>
  </div>
</template>

<script setup>
import { PlusIcon, TagIcon } from '@heroicons/vue/24/outline'
</script>