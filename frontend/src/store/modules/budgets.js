import apiService from '@/services'

export default {
  namespaced: true,
  
  state: {
    budgets: [],
    currentBudget: null,
    loading: false,
    error: null
  },

  getters: {
    allBudgets: state => state.budgets,
    budgetById: state => id => state.budgets.find(b => b.id === id),
    budgetsLoading: state => state.loading,
    budgetsError: state => state.error,
    
    activeBudgets: state => state.budgets.filter(b => b.is_active),
    
    budgetProgress: state => budgetId => {
      const budget = state.budgets.find(b => b.id === budgetId)
      if (!budget) return 0
      
      const spent = budget.categories?.reduce((sum, category) => {
        return sum + (category.spent || 0)
      }, 0) || 0
      
      return budget.total_amount > 0 ? (spent / budget.total_amount) * 100 : 0
    },
    
    budgetsByPeriod: state => period => {
      return state.budgets.filter(budget => {
        const budgetDate = new Date(budget.start_date)
        const now = new Date()
        
        switch(period) {
          case 'current':
            return budgetDate.getMonth() === now.getMonth() && 
                   budgetDate.getFullYear() === now.getFullYear()
          case 'upcoming':
            return budgetDate > now
          case 'past':
            return budgetDate < now
          default:
            return true
        }
      })
    }
  },

  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    
    SET_ERROR(state, error) {
      state.error = error
    },
    
    SET_BUDGETS(state, budgets) {
      state.budgets = budgets
    },
    
    ADD_BUDGET(state, budget) {
      state.budgets.push(budget)
    },
    
    UPDATE_BUDGET(state, updatedBudget) {
      const index = state.budgets.findIndex(b => b.id === updatedBudget.id)
      if (index !== -1) {
        state.budgets.splice(index, 1, updatedBudget)
      }
    },
    
    REMOVE_BUDGET(state, budgetId) {
      state.budgets = state.budgets.filter(b => b.id !== budgetId)
    },
    
    SET_CURRENT_BUDGET(state, budget) {
      state.currentBudget = budget
    }
  },

  actions: {
    async fetchBudgets({ commit }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
        const response = await apiService.budgets.getAll()

        const resData = response?.data
        const budgets = Array.isArray(resData)
          ? resData
          : (Array.isArray(resData?.data) ? resData.data : [])
        
        commit('SET_BUDGETS', budgets)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao carregar orçamentos')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async createBudget({ commit }, budgetData) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  const response = await apiService.budgets.create(budgetData)
        
        commit('ADD_BUDGET', response.data)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao criar orçamento')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateBudget({ commit }, { id, data }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  const response = await apiService.budgets.update(id, data)
        
        commit('UPDATE_BUDGET', response.data)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao atualizar orçamento')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteBudget({ commit }, budgetId) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  await apiService.budgets.delete(budgetId)
        
        commit('REMOVE_BUDGET', budgetId)
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao deletar orçamento')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  }
}