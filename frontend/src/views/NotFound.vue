<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
      <div>
        <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-500" />
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
          Página não encontrada
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          A página que você está procurando não existe.
        </p>
      </div>
      <div>
        <router-link 
          to="/"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Voltar ao Dashboard
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ExclamationTriangleIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
</script>