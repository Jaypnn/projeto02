import { createStore } from 'vuex'
import auth from './modules/auth'
import transactions from './modules/transactions'
import budgets from './modules/budgets'
import categories from './modules/categories'
import goals from './modules/goals'
import accounts from './modules/accounts'

export default createStore({
  modules: {
    auth,
    transactions,
    budgets,
    categories,
    goals,
    accounts
  },
  strict: import.meta.env.DEV
})