import api from './api'
import { authService } from './api'

// Re-exportar o authService atualizado para manter compatibilidade
export { authService }

export const transactionService = {
  // Get all transactions
  async getAll(params = {}) {
    const response = await api.get('/transactions', { params })
    return response
  },

  // Get transactions scoped to current authenticated user (backend-enforced)
  async getMine(params = {}) {
    const response = await api.get('/my-transactions', { params })
    return response
  },

  // Get transaction by ID
  async getById(id) {
    const response = await api.get(`/transactions/${id}`)
    return response
  },

  // Create transaction
  async create(data) {
    const response = await api.post('/transactions', data)
    return response
  },

  // Update transaction
  async update(id, data) {
    const response = await api.put(`/transactions/${id}`, data)
    return response
  },

  // Delete transaction
  async delete(id) {
    const response = await api.delete(`/transactions/${id}`)
    return response
  },


  // Get transactions summary
  async getSummary(params = {}) {
    const response = await api.get('/transactions-summary', { params })
    return response
  }
}

export const budgetService = {
  // Get all budgets
  async getAll(params = {}) {
    const response = await api.get('/budgets', { params })
    return response
  },

  // Get budget by ID
  async getById(id) {
    const response = await api.get(`/budgets/${id}`)
    return response
  },

  // Create budget
  async create(data) {
    const response = await api.post('/budgets', data)
    return response
  },

  // Update budget
  async update(id, data) {
    const response = await api.put(`/budgets/${id}`, data)
    return response
  },

  // Delete budget
  async delete(id) {
    const response = await api.delete(`/budgets/${id}`)
    return response
  },

  // Add category to budget
  async addCategory(budgetId, data) {
    const response = await api.post(`/budgets/${budgetId}/categories`, data)
    return response
  },

  // Update budget category
  async updateCategory(budgetId, categoryId, data) {
    const response = await api.put(`/budgets/${budgetId}/categories/${categoryId}`, data)
    return response
  },

  // Remove category from budget
  async removeCategory(budgetId, categoryId) {
    const response = await api.delete(`/budgets/${budgetId}/categories/${categoryId}`)
    return response
  },

  // Get budget performance
  async getPerformance(id) {
    const response = await api.get(`/budgets/${id}/performance`)
    return response
  },

  // Create next period budget
  async createNextPeriod(id) {
    const response = await api.post(`/budgets/${id}/next-period`)
    return response
  }
}

export const categoryService = {
  // Get all categories
  async getAll(params = {}) {
    const response = await api.get('/categories', { params })
    return response
  },

  // Get category by ID
  async getById(id) {
    const response = await api.get(`/categories/${id}`)
    return response
  },

  // Create category
  async create(data) {
    const response = await api.post('/categories', data)
    return response
  },

  // Update category
  async update(id, data) {
    const response = await api.put(`/categories/${id}`, data)
    return response
  },

  // Delete category
  async delete(id) {
    const response = await api.delete(`/categories/${id}`)
    return response
  },

  // Get category statistics
  async getStats(id) {
    const response = await api.get(`/categories/${id}/stats`)
    return response
  },

  // Get top categories
  async getTopCategories() {
    const response = await api.get('/categories-top')
    return response
  }
}

export const goalService = {
  // Get all financial goals
  async getAll(params = {}) {
    const response = await api.get('/financial-goals', { params })
    return response
  },

  // Get goal by ID
  async getById(id) {
    const response = await api.get(`/financial-goals/${id}`)
    return response
  },

  // Create goal
  async create(data) {
    const response = await api.post('/financial-goals', data)
    return response
  },

  // Update goal
  async update(id, data) {
    const response = await api.put(`/financial-goals/${id}`, data)
    return response
  },

  // Delete goal
  async delete(id) {
    const response = await api.delete(`/financial-goals/${id}`)
    return response
  },

  // Add contribution to goal
  async addContribution(goalId, data) {
    const response = await api.post(`/financial-goals/${goalId}/contributions`, data)
    return response
  },

  // Get goal progress
  async getProgress(id) {
    const response = await api.get(`/financial-goals/${id}/progress`)
    return response
  },

  // Get goals summary
  async getSummary() {
    const response = await api.get('/financial-goals-summary')
    return response
  },

  // Get upcoming goals
  async getUpcoming() {
    const response = await api.get('/financial-goals-upcoming')
    return response
  },

  // Toggle goal status
  async toggleStatus(id) {
    const response = await api.post(`/financial-goals/${id}/toggle-status`)
    return response
  }
}

export const accountService = {
  // Get all accounts
  async getAll(params = {}) {
    const response = await api.get('/accounts', { params })
    return response
  },

  // Get account by ID
  async getById(id) {
    const response = await api.get(`/accounts/${id}`)
    return response
  },

  // Create account
  async create(data) {
    const response = await api.post('/accounts', data)
    return response
  },

  // Update account
  async update(id, data) {
    const response = await api.put(`/accounts/${id}`, data)
    return response
  },

  // Delete account
  async delete(id) {
    const response = await api.delete(`/accounts/${id}`)
    return response
  },

  // Transfer between accounts
  async transfer(data) {
    const response = await api.post('/accounts/transfer', data)
    return response
  },

  // Get account balance history
  async getBalanceHistory(id, params = {}) {
    const response = await api.get(`/accounts/${id}/balance-history`, { params })
    return response
  },

  // Get accounts summary
  async getSummary() {
    const response = await api.get('/accounts-summary')
    return response
  },

  // Adjust account balance
  async adjustBalance(id, data) {
    const response = await api.post(`/accounts/${id}/adjust-balance`, data)
    return response
  },

  // Reactivate archived account
  async reactivate(id) {
    const response = await api.post(`/accounts/${id}/reactivate`)
    return response
  }
}

// Objeto principal com todos os serviços
const apiService = {
  auth: authService,
  transactions: transactionService,
  budgets: budgetService,
  categories: categoryService,
  goals: goalService,
  accounts: accountService
}

export default apiService