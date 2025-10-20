<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900">Criar conta</h2>
      <p class="mt-2 text-sm text-gray-600">
        Ou
        <router-link 
          to="/auth/login" 
          class="font-medium text-blue-600 hover:text-blue-500"
        >
          entre em sua conta existente
        </router-link>
      </p>
    </div>

    <!-- Alert de erro -->
    <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
      <div class="flex">
        <ExclamationCircleIcon class="w-5 h-5 text-red-400" />
        <div class="ml-3">
          <p class="text-sm text-red-700">{{ error }}</p>
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
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
          Nome completo
        </label>
        <div class="mt-1">
          <input
            id="name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            required
            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :class="{ 'border-red-300': errors.name }"
            placeholder="João da Silva"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
        </div>
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
          Email
        </label>
        <div class="mt-1">
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :class="{ 'border-red-300': errors.email }"
            placeholder="seu@email.com"
          />
          <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
        </div>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
          Senha
        </label>
        <div class="mt-1 relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="new-password"
            required
            class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :class="{ 'border-red-300': errors.password }"
            placeholder="Crie uma senha forte"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
          >
            <EyeIcon v-if="!showPassword" class="w-5 h-5 text-gray-400 hover:text-gray-600" />
            <EyeSlashIcon v-else class="w-5 h-5 text-gray-400 hover:text-gray-600" />
          </button>
          <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
        </div>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
          Confirmar senha
        </label>
        <div class="mt-1 relative">
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            autocomplete="new-password"
            required
            class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :class="{ 'border-red-300': errors.password_confirmation }"
            placeholder="Confirme sua senha"
          />
          <button
            type="button"
            @click="showPasswordConfirmation = !showPasswordConfirmation"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
          >
            <EyeIcon v-if="!showPasswordConfirmation" class="w-5 h-5 text-gray-400 hover:text-gray-600" />
            <EyeSlashIcon v-else class="w-5 h-5 text-gray-400 hover:text-gray-600" />
          </button>
          <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ errors.password_confirmation }}</p>
        </div>
      </div>

      <!-- Password strength indicator -->
      <div v-if="form.password" class="space-y-2">
        <div class="text-sm text-gray-600">Força da senha:</div>
        <div class="flex space-x-1">
          <div 
            v-for="i in 4" 
            :key="i"
            class="h-2 w-full rounded-full"
            :class="getPasswordStrengthColor(i)"
          ></div>
        </div>
        <div class="text-xs text-gray-500">
          {{ getPasswordStrengthText() }}
        </div>
      </div>

      <div class="flex items-center">
        <input
          id="accept-terms"
          v-model="form.acceptTerms"
          type="checkbox"
          required
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          :class="{ 'border-red-300': errors.acceptTerms }"
        />
        <label for="accept-terms" class="ml-2 block text-sm text-gray-900">
          Eu aceito os 
          <a href="#" class="text-blue-600 hover:text-blue-500">termos de uso</a>
          e a 
          <a href="#" class="text-blue-600 hover:text-blue-500">política de privacidade</a>
        </label>
      </div>
      <p v-if="errors.acceptTerms" class="text-sm text-red-600">{{ errors.acceptTerms }}</p>

      <div>
        <button
          type="submit"
          :disabled="loading"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="loading" class="absolute left-0 inset-y-0 flex items-center pl-3">
            <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          </span>
          {{ loading ? 'Criando conta...' : 'Criar conta' }}
        </button>
      </div>
    </form>
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
const showPasswordConfirmation = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  acceptTerms: false
})

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  acceptTerms: ''
})

// Computed
const loading = computed(() => store.getters['auth/authLoading'])
const error = computed(() => store.getters['auth/authError'])

const passwordStrength = computed(() => {
  const password = form.password
  let score = 0
  
  if (password.length >= 8) score++
  if (/[a-z]/.test(password)) score++
  if (/[A-Z]/.test(password)) score++
  if (/\d/.test(password)) score++
  if (/[^A-Za-z0-9]/.test(password)) score++
  
  return Math.min(score, 4)
})

// Methods
const getPasswordStrengthColor = (index) => {
  if (passwordStrength.value >= index) {
    if (passwordStrength.value <= 1) return 'bg-red-400'
    if (passwordStrength.value <= 2) return 'bg-yellow-400'
    if (passwordStrength.value <= 3) return 'bg-blue-400'
    return 'bg-green-400'
  }
  return 'bg-gray-200'
}

const getPasswordStrengthText = () => {
  const texts = ['Muito fraca', 'Fraca', 'Regular', 'Boa', 'Forte']
  return texts[passwordStrength.value] || 'Muito fraca'
}

const validateForm = () => {
  // Reset errors
  Object.keys(errors).forEach(key => errors[key] = '')
  
  let isValid = true
  
  if (!form.name.trim()) {
    errors.name = 'Nome é obrigatório'
    isValid = false
  } else if (form.name.trim().length < 2) {
    errors.name = 'Nome deve ter pelo menos 2 caracteres'
    isValid = false
  }
  
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
  } else if (form.password.length < 8) {
    errors.password = 'Senha deve ter pelo menos 8 caracteres'
    isValid = false
  } else if (passwordStrength.value < 2) {
    errors.password = 'Senha muito fraca. Use letras maiúsculas, minúsculas e números'
    isValid = false
  }
  
  if (!form.password_confirmation) {
    errors.password_confirmation = 'Confirmação de senha é obrigatória'
    isValid = false
  } else if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Senhas não coincidem'
    isValid = false
  }
  
  if (!form.acceptTerms) {
    errors.acceptTerms = 'Você deve aceitar os termos de uso'
    isValid = false
  }
  
  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  try {
    await store.dispatch('auth/register', {
      name: form.name.trim(),
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    
    // Redireciona para o dashboard após registro bem-sucedido
    router.push({ name: 'Dashboard' })
  } catch (error) {
    console.error('Erro no registro:', error)
    
    // Se houver erro de validação específico, mostra nos campos
    if (error.response?.status === 422 && error.response?.data?.errors) {
      const serverErrors = error.response.data.errors
      
      // Mapeia erros do servidor para os campos locais
      if (serverErrors.name) {
        errors.name = serverErrors.name[0]
      }
      if (serverErrors.email) {
        errors.email = serverErrors.email[0]
      }
      if (serverErrors.password) {
        errors.password = serverErrors.password[0]
      }
    }
    // Outros erros são tratados pelo store e aparecem no alert
  }
}

const clearError = () => {
  store.dispatch('auth/clearError')
}

// Lifecycle
onMounted(() => {
  // Limpa erros quando o componente é montado
  store.dispatch('auth/clearError')
})
</script>