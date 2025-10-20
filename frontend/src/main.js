import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import store from './store'
import router from './router'
import apiService from './services'

const app = createApp(App)

// Disponibiliza o service API globalmente
app.config.globalProperties.$api = apiService

app.use(store)
app.use(router)

// Verificar autenticação ao inicializar
store.dispatch('auth/fetchUser').finally(() => {
  app.mount('#app')
})
