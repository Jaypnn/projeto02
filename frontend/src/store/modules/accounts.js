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
    
    // Soma apenas saldos positivos de contas ativas e marcadas para incluir no total
    totalBalance: state => {
      return state.accounts
        .filter(a => a.is_active && (a.include_in_total ?? true))
        .reduce((sum, account) => {
          const val = Number(account.current_balance ?? account.balance ?? 0)
          return val > 0 ? sum + val : sum
        }, 0)
    },

    // Totais por banco (mapa bank_id -> soma de saldos positivos)
    totalByBank: state => {
      return state.accounts
        .filter(a => a.is_active && (a.include_in_total ?? true))
        .reduce((acc, a) => {
          const val = Number(a.current_balance ?? a.balance ?? 0)
          if (val > 0) {
            const key = a.bank_id || 'unknown'
            acc[key] = (acc[key] || 0) + val
          }
          return acc
        }, {})
    },

    // Totais por tipo de conta (checking, savings, wallet, etc.)
    totalByType: state => {
      return state.accounts
        .filter(a => a.is_active && (a.include_in_total ?? true))
        .reduce((acc, a) => {
          const val = Number(a.current_balance ?? a.balance ?? 0)
          if (val > 0) {
            const key = a.type || 'other'
            acc[key] = (acc[key] || 0) + val
          }
          return acc
        }, {})
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
        const response = await apiService.accounts.create(accountData)
        const payload = response?.data?.data ?? response?.data
        
        if (payload) {
          commit('ADD_ACCOUNT', payload)
        }
        
        return payload
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
        const response = await apiService.accounts.update(id, data)
        const payload = response?.data?.data ?? response?.data
        
        if (payload) {
          commit('UPDATE_ACCOUNT', payload)
        }
        
        return payload
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
        await apiService.accounts.delete(accountId)
        
        commit('REMOVE_ACCOUNT', accountId)
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao deletar conta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    // Helpers específicos para ativar/arquivar
    async archiveAccount({ dispatch }, id) {
      return dispatch('updateAccount', { id, data: { is_active: false } })
    },
    async activateAccount({ dispatch }, id) {
      return dispatch('updateAccount', { id, data: { is_active: true } })
    },

    async reactivateAccount({ commit }, id) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const response = await apiService.accounts.reactivate(id)
        const payload = response?.data?.data ?? response?.data
        if (payload) {
          commit('UPDATE_ACCOUNT', payload)
        }
        return payload
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao reativar conta')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  }
}