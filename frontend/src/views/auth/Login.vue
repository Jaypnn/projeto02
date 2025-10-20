<template>
  <div>
    <!-- Header -->
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Bem-vindo de volta!</h2>
      <p class="text-sm text-gray-500">
        Sentimos sua falta! Por favor, insira seus dados.
      </p>
    </div>

    <!-- Alert de erro -->
    <div 
      v-if="error" 
      class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg"
    >
      <div class="flex">
        <ExclamationCircleIcon class="w-5 h-5 text-red-400 flex-shrink-0" />
        <div class="ml-3">
          <p class="text-sm font-medium text-red-700">{{ error }}</p>
          <button 
            @click="clearError"
            class="mt-1 text-xs text-red-500 hover:text-red-700 underline"
          >
            Dispensar
          </button>
        </div>
      </div>
    </div>

    <!-- Formulário -->
    <form @submit.prevent="handleSubmit" class="space-y-5">
      <!-- Campo Email -->
      <div class="space-y-2">
        <label for="email" class="block text-sm font-medium text-gray-700">
          Email
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
          :class="{
            'border-red-300 focus:ring-red-500 focus:border-red-500': errors.email,
            'border-gray-300': !errors.email
          }"
          placeholder="Digite seu email"
        />
        <p v-if="errors.email" class="text-sm text-red-600">{{ errors.email }}</p>
      </div>

      <!-- Campo Senha -->
      <div class="space-y-2">
        <label for="password" class="block text-sm font-medium text-gray-700">
          Senha
        </label>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            required
            class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
            :class="{
              'border-red-300 focus:ring-red-500 focus:border-red-500': errors.password,
              'border-gray-300': !errors.password
            }"
            placeholder="Digite sua senha"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 pr-3 flex items-center hover:text-gray-600 transition-colors duration-200"
          >
            <EyeIcon v-if="!showPassword" class="w-5 h-5 text-gray-400" />
            <EyeSlashIcon v-else class="w-5 h-5 text-gray-400" />
          </button>
        </div>
        <p v-if="errors.password" class="text-sm text-red-600">{{ errors.password }}</p>
      </div>

      <!-- Botão Principal -->
      <div>
        <button
          type="submit"
          :disabled="loading"
          class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
        >
          <span v-if="loading" class="mr-2">
            <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          </span>
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
      </div>
    </form>

    <!-- Link para registro -->
    <div class="mt-8 text-center">
      <p class="text-sm text-gray-600">
        Não tem uma conta? 
        <router-link 
          to="/auth/register" 
          class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200"
        >
          Cadastre-se
        </router-link>
      </p>
    </div>

    <!-- Modal de esqueceu senha -->
    <div v-if="showForgotPassword" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recuperar Senha</h3>
        <p class="text-sm text-gray-600 mb-4">
          Digite seu email para receber as instruções de recuperação de senha.
        </p>
        <form @submit.prevent="handleForgotPassword" class="space-y-4">
          <input
            v-model="forgotEmail"
            type="email"
            placeholder="Digite seu email"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <div class="flex space-x-3">
            <button
              type="button"
              @click="showForgotPassword = false"
              class="flex-1 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="flex-1 py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
            >
              Enviar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import {
  ExclamationCircleIcon,
  EyeIcon,
  EyeSlashIcon
} from '@heroicons/vue/24/outline'

const store = useStore()
const router = useRouter()

// State
const showPassword = ref(false)
const showForgotPassword = ref(false)
const forgotEmail = ref('')
const isDev = ref(import.meta.env.DEV)

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const errors = reactive({
  email: '',
  password: ''
})

// Computed
const loading = computed(() => store.getters['auth/authLoading'])
const error = computed(() => store.getters['auth/authError'])

// Methods
const validateForm = () => {
  errors.email = ''
  errors.password = ''
  
  let isValid = true
  
  if (!form.email) {
    errors.email = 'Email é obrigatório'
    isValid = false
  } else if (!/\S+@\S+\.\S+/.test(form.email)) {
    errors.email = 'Email inválido'
    isValid = false
  }
  
  if (!form.password) {
    errors.password = 'Senha é obrigatória'
    isValid = false
  } else if (form.password.length < 6) {
    errors.password = 'Senha deve ter pelo menos 6 caracteres'
    isValid = false
  }
  
  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  try {
    await store.dispatch('auth/login', {
      email: form.email,
      password: form.password,
      remember: form.remember
    })
    
    // Redireciona para o dashboard após login bem-sucedido
    router.push({ name: 'Dashboard' })
  } catch (error) {
    console.error('Erro no login:', error)
    // Erro já está sendo tratado no store
  }
}

const loginWithTestUser = async () => {
  form.email = 'test@example.com'
  form.password = 'password'
  form.remember = false
  await handleSubmit()
}

const clearError = () => {
  store.dispatch('auth/clearError')
}

const handleForgotPassword = async () => {
  if (!forgotEmail.value || !/\S+@\S+\.\S+/.test(forgotEmail.value)) {
    alert('Por favor, insira um email válido')
    return
  }
  
  // TODO: Implementar recuperação de senha
  alert('Funcionalidade de recuperação de senha será implementada em breve!')
  showForgotPassword.value = false
  forgotEmail.value = ''
}

// Lifecycle
onMounted(() => {
  // Limpa erros quando o componente é montado
  store.dispatch('auth/clearError')
})
</script>