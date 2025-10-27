import apiService from '@/services'

export default {
  namespaced: true,
  
  state: {
    goals: [],
    currentGoal: null,
    loading: false,
    error: null
  },

  getters: {
    allGoals: state => state.goals,
    goalById: state => id => state.goals.find(g => g.id === id),
    goalsLoading: state => state.loading,
    goalsError: state => state.error,
    
  // Align with backend: use status field instead of non-existent is_active/is_completed
  activeGoals: state => state.goals.filter(g => g.status === 'active'),
  completedGoals: state => state.goals.filter(g => g.status === 'completed'),
    
    goalProgress: state => goalId => {
      const goal = state.goals.find(g => g.id === goalId)
      if (!goal) return 0
      
      return goal.target_amount > 0 ? (goal.current_amount / goal.target_amount) * 100 : 0
    },
    
    goalsByPriority: state => {
      return [...state.goals].sort((a, b) => {
        const priorityOrder = { high: 3, medium: 2, low: 1 }
        return (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0)
      })
    },
    
    upcomingDeadlines: state => {
      const now = new Date()
      const thirtyDaysFromNow = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000)
      
      return state.goals.filter(goal => {
        if (!goal.target_date || goal.is_completed) return false
        
        const targetDate = new Date(goal.target_date)
        return targetDate >= now && targetDate <= thirtyDaysFromNow
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
    
    SET_GOALS(state, goals) {
      state.goals = goals
    },
    
    ADD_GOAL(state, goal) {
      state.goals.push(goal)
    },
    
    UPDATE_GOAL(state, updatedGoal) {
      const index = state.goals.findIndex(g => g.id === updatedGoal.id)
      if (index !== -1) {
        state.goals.splice(index, 1, updatedGoal)
      }
    },
    
    REMOVE_GOAL(state, goalId) {
      state.goals = state.goals.filter(g => g.id !== goalId)
    },
    
    SET_CURRENT_GOAL(state, goal) {
      state.currentGoal = goal
    }
  },

  actions: {
  async fetchGoals({ commit }, params = {}) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
  const response = await apiService.goals.getAll(params)

        const resData = response?.data
        const goals = Array.isArray(resData)
          ? resData
          : (Array.isArray(resData?.data) ? resData.data : [])
        
        commit('SET_GOALS', goals)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao carregar metas')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

  async createGoal({ commit }, goalData) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await apiService.goals.create(goalData)
        const payload = response?.data?.data || response?.data

        if (payload) {
          commit('ADD_GOAL', payload)
        }
        
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao criar meta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateGoal({ commit }, { id, data }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await apiService.goals.update(id, data)
        const payload = response?.data?.data || response?.data

        if (payload) {
          commit('UPDATE_GOAL', payload)
        }
        
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao atualizar meta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteGoal({ commit }, goalId) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        await apiService.goals.delete(goalId)
        commit('REMOVE_GOAL', goalId)
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao deletar meta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async addContribution({ commit }, { goalId, amount, description }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await apiService.goals.addContribution(goalId, { amount, description })
        const payload = response?.data?.data || response?.data

        if (payload) {
          commit('UPDATE_GOAL', payload)
        }
        
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao adicionar contribuição')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async toggleStatus({ commit }, goalId) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)

      try {
        const response = await apiService.goals.toggleStatus(goalId)
        const payload = response?.data?.data || response?.data
        if (payload) {
          commit('UPDATE_GOAL', payload)
        }
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao alterar status da meta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  }
}