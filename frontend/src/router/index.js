import { createRouter, createWebHistory } from 'vue-router'
import store from '../store'

// Layouts
import DefaultLayout from '../layouts/DefaultLayout.vue'
import AuthLayout from '../layouts/AuthLayout.vue'

// Views
import Dashboard from '../views/Dashboard.vue'
import Login from '../views/auth/Login.vue'
import Register from '../views/auth/Register.vue'
import Transactions from '../views/Transactions.vue'
import Budgets from '../views/Budgets.vue'
import Categories from '../views/Categories.vue'
import Goals from '../views/Goals.vue'
import Accounts from '../views/Accounts.vue'
import Profile from '../views/Profile.vue'

const routes = [
  // Rotas de autenticação
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        name: 'Login',
        component: Login,
        meta: { requiresGuest: true }
      },
      {
        path: 'register',
        name: 'Register',
        component: Register,
        meta: { requiresGuest: true }
      }
    ]
  },
  
  // Rotas protegidas
  {
    path: '/',
    component: DefaultLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: Dashboard
      },
      {
        path: 'transactions',
        name: 'Transactions',
        component: Transactions
      },
      {
        path: 'budgets',
        name: 'Budgets',
        component: Budgets
      },
      {
        path: 'categories',
        name: 'Categories',
        component: Categories
      },
      {
        path: 'goals',
        name: 'Goals',
        component: Goals
      },
      {
        path: 'accounts',
        name: 'Accounts',
        component: Accounts
      },
      {
        path: 'profile',
        name: 'Profile',
        component: Profile
      }
    ]
  },
  
  // Catch all 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)
  const isAuthenticated = store.getters['auth/isAuthenticated']
  
  // Log leve em dev
  if (import.meta.env.DEV) {
    console.log('Router Guard:', {
      to: to.fullPath,
      requiresAuth,
      requiresGuest,
      isAuthenticated
    })
  }

  // Se a rota requer autenticação mas o usuário não está logado
  if (requiresAuth && !isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }
  
  // Se a rota é apenas para guests mas o usuário está logado
  if (requiresGuest && isAuthenticated) {
    return next({ name: 'Dashboard' })
  }
  
  next()
})

export default router