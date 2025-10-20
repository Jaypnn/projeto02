import apiService from '@/services'

export default {
  namespaced: true,
  
  state: {
    accounts: [],
    currentAccount: null,
    loading: false,
    error: null
  },

  getters: {
    allAccounts: state => state.accounts,
    accountById: state => id => state.accounts.find(a => a.id === id),
    accountsLoading: state => state.loading,
    accountsError: state => state.error,
    
    activeAccounts: state => state.accounts.filter(a => a.is_active),
    
    totalBalance: state => {
      return state.accounts.reduce((sum, account) => {
        return sum + parseFloat(account.balance || 0)
      }, 0)
    },
    
    accountsByType: state => type => state.accounts.filter(a => a.type === type),
    
    accountsOptions: state => {
      return state.accounts
        .filter(a => a.is_active)
        .map(a => ({
          value: a.id,
          label: `${a.name} (${a.type})`,
          balance: a.balance
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
    
    SET_ACCOUNTS(state, accounts) {
      state.accounts = accounts
    },
    
    ADD_ACCOUNT(state, account) {
      state.accounts.push(account)
    },
    
    UPDATE_ACCOUNT(state, updatedAccount) {
      const index = state.accounts.findIndex(a => a.id === updatedAccount.id)
      if (index !== -1) {
        state.accounts.splice(index, 1, updatedAccount)
      }
    },
    
    REMOVE_ACCOUNT(state, accountId) {
      state.accounts = state.accounts.filter(a => a.id !== accountId)
    },
    
    SET_CURRENT_ACCOUNT(state, account) {
      state.currentAccount = account
    },
    
    UPDATE_ACCOUNT_BALANCE(state, { accountId, newBalance }) {
      const account = state.accounts.find(a => a.id === accountId)
      if (account) {
        account.balance = newBalance
      }
    }
  },

  actions: {
    async fetchAccounts({ commit }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
        const response = await apiService.accounts.getAll()

        const resData = response?.data
        const accounts = Array.isArray(resData)
          ? resData
          : (Array.isArray(resData?.data) ? resData.data : [])
        
        commit('SET_ACCOUNTS', accounts)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao carregar contas')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async createAccount({ commit }, accountData) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  const response = await apiService.accounts.create(accountData)
        
        commit('ADD_ACCOUNT', response.data)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao criar conta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateAccount({ commit }, { id, data }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  const response = await apiService.accounts.update(id, data)
        
        commit('UPDATE_ACCOUNT', response.data)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao atualizar conta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteAccount({ commit }, accountId) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
  await apiService.accounts.delete(accountId)
        
        commit('REMOVE_ACCOUNT', accountId)
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao deletar conta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  }
}