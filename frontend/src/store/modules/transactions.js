import apiService from '@/services'

export default {
  namespaced: true,
  
  state: {
    transactions: [],
    currentTransaction: null,
    loading: false,
    error: null,
    pagination: {
      currentPage: 1,
      perPage: 20,
      total: 0
    },
    filters: {
      category: null,
      account: null,
      type: null,
      dateFrom: null,
      dateTo: null,
      search: ''
    }
  },

  getters: {
    allTransactions: state => state.transactions,
    transactionById: state => id => state.transactions.find(t => t.id === id),
    transactionsLoading: state => state.loading,
    transactionsError: state => state.error,
    
    // Filtros e estatísticas
    incomeTransactions: state => state.transactions.filter(t => t.type === 'income'),
    expenseTransactions: state => state.transactions.filter(t => t.type === 'expense'),
    
    totalIncome: state => {
      return state.transactions
        .filter(t => t.type === 'income')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0)
    },
    
    totalExpenses: state => {
      return state.transactions
        .filter(t => t.type === 'expense')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0)
    },
    
    balance: (state, getters) => getters.totalIncome - getters.totalExpenses,
    
    transactionsByCategory: state => {
      return state.transactions.reduce((acc, transaction) => {
        const categoryName = transaction.category?.name || 'Sem categoria'
        if (!acc[categoryName]) {
          acc[categoryName] = {
            name: categoryName,
            total: 0,
            count: 0,
            transactions: []
          }
        }
        acc[categoryName].total += parseFloat(transaction.amount)
        acc[categoryName].count++
        acc[categoryName].transactions.push(transaction)
        return acc
      }, {})
    }
  },

  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    
    SET_ERROR(state, error) {
      state.error = error
    },
    
    SET_TRANSACTIONS(state, { transactions, pagination }) {
      state.transactions = transactions
      if (pagination) {
        state.pagination = { ...state.pagination, ...pagination }
      }
    },
    
    ADD_TRANSACTION(state, transaction) {
      state.transactions.unshift(transaction)
    },
    
    UPDATE_TRANSACTION(state, updatedTransaction) {
      const index = state.transactions.findIndex(t => t.id === updatedTransaction.id)
      if (index !== -1) {
        state.transactions.splice(index, 1, updatedTransaction)
      }
    },
    
    REMOVE_TRANSACTION(state, transactionId) {
      state.transactions = state.transactions.filter(t => t.id !== transactionId)
    },
    
    SET_CURRENT_TRANSACTION(state, transaction) {
      state.currentTransaction = transaction
    },
    
    SET_FILTERS(state, filters) {
      state.filters = { ...state.filters, ...filters }
    },
    
    CLEAR_FILTERS(state) {
      state.filters = {
        category: null,
        account: null,
        type: null,
        dateFrom: null,
        dateTo: null,
        search: ''
      }
    }
  },

  actions: {
    async fetchTransactions({ commit, state }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API com filtros e paginação
        const params = {
          page: state.pagination.currentPage,
          per_page: state.pagination.perPage,
          ...state.filters
        }
        
        const response = await apiService.transactions.getAll(params)

        const resData = response?.data
        let transactions = []
        let pagination = null

        if (Array.isArray(resData)) {
          // API retornou uma lista simples
          transactions = resData
        } else if (resData && Array.isArray(resData.data)) {
          // API paginada (Laravel paginator)
          transactions = resData.data
          pagination = {
            currentPage: resData.current_page ?? resData.meta?.current_page ?? state.pagination.currentPage,
            total: resData.total ?? resData.meta?.total ?? resData.data.length,
            perPage: resData.per_page ?? resData.meta?.per_page ?? state.pagination.perPage
          }
        }

        commit('SET_TRANSACTIONS', { transactions, pagination })
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao carregar transações')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async createTransaction({ commit }, transactionData) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
        const response = await apiService.transactions.create(transactionData)
        
        commit('ADD_TRANSACTION', response.data)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao criar transação')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateTransaction({ commit }, { id, data }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
        const response = await apiService.transactions.update(id, data)
        
        commit('UPDATE_TRANSACTION', response.data)
        
        return response.data
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao atualizar transação')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteTransaction({ commit }, transactionId) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      
      try {
        // TODO: Implementar chamada à API
        await apiService.transactions.delete(transactionId)
        
        commit('REMOVE_TRANSACTION', transactionId)
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Erro ao deletar transação')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    setFilters({ commit }, filters) {
      commit('SET_FILTERS', filters)
    },

    clearFilters({ commit }) {
      commit('CLEAR_FILTERS')
    }
  }
}