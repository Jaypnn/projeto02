<template>
  <div v-if="isInitializing" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">Carregando...</p>
    </div>
  </div>
  <router-view v-else />
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()

// Mostra loading enquanto verifica a autenticação inicial
const isInitializing = computed(() => store.getters['auth/authLoading'])
</script>
