<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <router-link to="/" class="flex items-center space-x-2">
              <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">F</span>
              </div>
              <span class="text-xl font-bold text-gray-900">FinanceApp</span>
            </router-link>
          </div>

          <!-- Desktop Navigation -->
          <nav class="hidden md:flex space-x-8">
            <router-link
              v-for="item in navigation"
              :key="item.name"
              :to="item.to"
              class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors"
              :class="$route.name === item.name 
                ? 'bg-blue-100 text-blue-700' 
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
            >
              <component :is="item.icon" class="w-5 h-5 mr-2" />
              {{ item.label }}
            </router-link>
          </nav>

          <!-- User Menu -->
          <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="p-2 text-gray-400 hover:text-gray-600 relative">
              <BellIcon class="w-6 h-6" />
              <span v-if="hasNotifications" class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- User Dropdown -->
            <div class="relative" v-click-outside="closeUserMenu">
              <button 
                @click="toggleUserMenu"
                class="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-50 transition-colors"
              >
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                  <span class="text-white text-sm font-medium">
                    {{ userInitials }}
                  </span>
                </div>
                <ChevronDownIcon class="w-4 h-4 text-gray-400" />
              </button>

              <!-- Dropdown Menu -->
              <div 
                v-show="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
              >
                <div class="py-1">
                  <router-link
                    to="/profile"
                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  >
                    <UserIcon class="w-4 h-4 mr-3" />
                    Perfil
                  </router-link>
                  <button
                    @click="logout"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  >
                    <ArrowRightOnRectangleIcon class="w-4 h-4 mr-3" />
                    Sair
                  </button>
                </div>
              </div>
            </div>

            <!-- Mobile menu button -->
            <button
              @click="toggleMobileMenu"
              class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-50"
            >
              <Bars3Icon v-if="!showMobileMenu" class="w-6 h-6" />
              <XMarkIcon v-else class="w-6 h-6" />
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <div v-show="showMobileMenu" class="md:hidden bg-white border-t border-gray-200">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.to"
            @click="showMobileMenu = false"
            class="flex items-center px-3 py-2 rounded-md text-base font-medium transition-colors"
            :class="$route.name === item.name 
              ? 'bg-blue-100 text-blue-700' 
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
          >
            <component :is="item.icon" class="w-5 h-5 mr-3" />
            {{ item.label }}
          </router-link>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import {
  BellIcon,
  UserIcon,
  ArrowRightOnRectangleIcon,
  ChevronDownIcon,
  Bars3Icon,
  XMarkIcon,
  HomeIcon,
  CreditCardIcon,
  ChartBarIcon,
  TagIcon,
  TrophyIcon,
  BuildingLibraryIcon
} from '@heroicons/vue/24/outline'

const store = useStore()
const router = useRouter()

// State
const showUserMenu = ref(false)
const showMobileMenu = ref(false)
const hasNotifications = ref(false) // TODO: Implementar notificações

// Navigation items
const navigation = [
  { name: 'Dashboard', label: 'Dashboard', to: '/', icon: HomeIcon },
  { name: 'Transactions', label: 'Transações', to: '/transactions', icon: CreditCardIcon },
  { name: 'Budgets', label: 'Orçamentos', to: '/budgets', icon: ChartBarIcon },
  { name: 'Categories', label: 'Categorias', to: '/categories', icon: TagIcon },
  { name: 'Goals', label: 'Metas', to: '/goals', icon: TrophyIcon },
  { name: 'Accounts', label: 'Contas', to: '/accounts', icon: BuildingLibraryIcon }
]

// Computed
const currentUser = computed(() => store.getters['auth/currentUser'])

const userInitials = computed(() => {
  if (!currentUser.value?.name) return 'U'
  
  return currentUser.value.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// Methods
const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const closeUserMenu = () => {
  showUserMenu.value = false
}

const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
}

const logout = async () => {
  try {
    await store.dispatch('auth/logout')
    router.push('/auth/login')
  } catch (error) {
    console.error('Erro ao fazer logout:', error)
  }
}

// Directive for clicking outside
const vClickOutside = {
  beforeMount(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>