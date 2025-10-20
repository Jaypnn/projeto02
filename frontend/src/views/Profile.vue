<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Perfil</h1>
      <p class="mt-1 text-sm text-gray-500">
        Gerencie suas informações pessoais e configurações
      </p>
    </div>

    <div class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
      <UserIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">Em desenvolvimento</h3>
      <p>A interface de perfil será implementada em breve.</p>
    </div>
  </div>
</template>

<script setup>
import { UserIcon } from '@heroicons/vue/24/outline'
</script>