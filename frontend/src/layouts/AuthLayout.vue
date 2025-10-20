<template>
  <!-- Layout de tela cheia com fundo gradiente azul -->
  <div class="min-h-screen bg-gradient-to-br from-blue-500 via-blue-600 to-purple-700 flex items-center justify-center px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    
    <!-- Elementos decorativos de fundo -->
    <div class="absolute inset-0 overflow-hidden">
      <!-- Círculos decorativos -->
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-white bg-opacity-5 rounded-full"></div>
      <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-white bg-opacity-5 rounded-full"></div>
      <div class="absolute top-1/2 left-1/4 w-32 h-32 bg-white bg-opacity-3 rounded-full"></div>
    </div>

    <!-- Container principal centralizado -->
    <div class="relative z-10 w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 backdrop-blur-sm rounded-2xl mb-4">
          <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
            <span class="text-blue-600 font-bold text-lg">S</span>
          </div>
        </div>
        <h1 class="text-white text-2xl font-bold">STARCATCHER</h1>
      </div>

      <!-- Card branco com formulário -->
      <div class="bg-white rounded-2xl shadow-2xl p-8 backdrop-blur-sm">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goBack = () => {
  router.go(-1)
}
</script>