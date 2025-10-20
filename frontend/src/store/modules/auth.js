import { authService } from '@/services'

export default {
  namespaced: true,
  
  state: {
    user: null,
    isAuthenticated: false,
    loading: false,
    error: null
  },

  getters: {
    isAuthenticated: state => !!state.user,
    currentUser: state => state.user,
    authLoading: state => state.loading,
    authError: state => state.error
  },

  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    
    SET_ERROR(state, error) {
      state.error = error
    },
    
    SET_USER(state, user) {
      state.user = user
      state.isAuthenticated = !!user
    },
    
    CLEAR_AUTH(state) {
      state.user = null
      state.isAuthenticated = false
    }
  },

  actions: {
    async login({ commit }, credentials) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await authService.login(credentials)
        
        // Se o login foi bem-sucedido, definir o usuário
        if (response.data && response.data.user) {
          commit('SET_USER', response.data.user)
        } else if (response.data) {
          // Caso a resposta não tenha user, mas tenha dados
          commit('SET_USER', response.data)
        }
        
        return response.data
      } catch (error) {
        let errorMessage = 'Erro ao fazer login'
        
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.response?.status === 401) {
          errorMessage = 'Email ou senha incorretos'
        } else if (error.response?.status === 422) {
          errorMessage = 'Dados inválidos. Verifique os campos.'
        } else if (error.response?.status >= 500) {
          errorMessage = 'Erro interno do servidor'
        } else if (!error.response) {
          errorMessage = 'Erro de conexão. Verifique sua internet.'
        }
        
        commit('SET_ERROR', errorMessage)
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async register({ commit }, userData) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        const response = await authService.register(userData)
        commit('SET_USER', response.data.user)
        
        return response.data
      } catch (error) {
        let errorMessage = 'Erro ao criar conta'
        
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.status === 422) {
          errorMessage = 'Dados inválidos. Verifique os campos.'
        }
        
        commit('SET_ERROR', errorMessage)
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async fetchUser({ commit }) {
      try {
        const response = await authService.getUser()
        commit('SET_USER', response.data)
        return response.data
      } catch (error) {
        // Se não conseguiu buscar usuário, limpa a autenticação
        commit('CLEAR_AUTH')
        // Não mostra erro para não poluir o console na tela de login
        return null
      }
    },

    async logout({ commit }) {
      try {
        await authService.logout()
      } catch (error) {
        // Ignora erros de logout - limpa mesmo assim
        console.warn('Erro ao fazer logout no servidor:', error.message)
      } finally {
        commit('CLEAR_AUTH')
        commit('SET_ERROR', null)
      }
    },

    // Limpa erros
    clearError({ commit }) {
      commit('SET_ERROR', null)
    }
  }
}