import apiService from '@/services'

export default {
  namespaced: true,
  
  state: {
    categories: [],
    loading: false,
    error: null
  },

  getters: {
    allCategories: state => state.categories,
    categoryById: state => id => state.categories.find(c => c.id === id),
    categoriesLoading: state => state.loading,
    categoriesError: state => state.error,
    // categorias não possuem mais tipo fixo; filtros por tipo devem ser feitos via transações
    
    activeCategoriesOptions: state => {
      return state.categories
        .filter(c => c.is_active)
        .map(c => ({
          value: c.id,
          label: c.name,
          color: c.color,
          icon: c.icon
        }))
    }
  },

  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    
    SET_ERROR(state, error) {
      state.error = error
    },
    
    SET_CATEGORIES(state, categories) {
      state.categories = categories
    },
    
    ADD_CATEGORY(state, category) {
      state.categories.push(category)
    },
    
    UPDATE_CATEGORY(state, updatedCategory) {
      const index = state.categories.findIndex(c => c.id === updatedCategory.id)
      if (index !== -1) {
        state.categories.splice(index, 1, updatedCategory)
      }
    },
    
    REMOVE_CATEGORY(state, categoryId) {
      state.categories = state.categories.filter(c => c.id !== categoryId)
    }
  },

  actions: {
    async fetchCategories({ commit }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
        const response = await apiService.categories.getAll()

        const resData = response?.data
        const categories = Array.isArray(resData)
          ? resData
          : (Array.isArray(resData?.data) ? resData.data : [])
        
        commit('SET_CATEGORIES', categories)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao carregar categorias')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async createCategory({ commit }, categoryData) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await apiService.categories.create(categoryData)
        const payload = response?.data?.data || response?.data
        
        if (payload) {
          commit('ADD_CATEGORY', payload)
        }
        
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao criar categoria')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateCategory({ commit }, { id, data }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await apiService.categories.update(id, data)
        const payload = response?.data?.data || response?.data
        
        if (payload) {
          commit('UPDATE_CATEGORY', payload)
        }
        
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao atualizar categoria')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteCategory({ commit }, categoryId) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  await apiService.categories.delete(categoryId)
        
        commit('REMOVE_CATEGORY', categoryId)
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao deletar categoria')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  }
}