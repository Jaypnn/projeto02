import axios from 'axios'
import store from '../store'
import router from '../router'

// Configuração base do Axios para autenticação por sessão
const api = axios.create({
  baseURL: import.meta.env.VITE_APP_API_URL || 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  // Necessário para que o Axios (>=1.6) envie o header X-XSRF-TOKEN
  // em requisições cross-site (ex.: frontend 5173 -> backend 8000)
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN'
})

// API separada para obter cookie CSRF
const csrfApi = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_URL || 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN'
})

// Função para obter token CSRF
const getCsrfToken = async () => {
  try {
    await csrfApi.get('/sanctum/csrf-cookie')
  } catch (error) {
    console.error('Erro ao obter token CSRF:', error)
  }
}

// Interceptor de requisição
api.interceptors.request.use(
  async (config) => {
    // Para rotas de autenticação e outras que precisam de CSRF, garante que temos o token
    if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
      try {
        await getCsrfToken()
      } catch (error) {
        console.warn('Não foi possível obter token CSRF:', error.message)
      }
    }
    
    // Log para desenvolvimento (mais limpo)
    if (import.meta.env.DEV) {
      const method = config.method?.toUpperCase()
      const hasData = config.data && Object.keys(config.data).length > 0
      console.log(`🔵 ${method} ${config.url}${hasData ? ' (com dados)' : ''}`)
    }
    
    return config
  },
  (error) => {
    console.error('Erro na requisição:', error)
    return Promise.reject(error)
  }
)

// Interceptor de resposta
api.interceptors.response.use(
  (response) => {
    // Log para desenvolvimento (mais limpo)
    if (import.meta.env.DEV) {
      const method = response.config.method?.toUpperCase()
      console.log(`🟢 ${response.status} ${method} ${response.config.url}`)
    }
    
    return response
  },
  (error) => {
    // Log para desenvolvimento
    if (import.meta.env.DEV && error.config?.url && !error.config.url.includes('csrf-cookie')) {
      const method = error.config.method?.toUpperCase()
      const status = error.response?.status || 'Network'
      console.error(`🔴 ${status} ${method} ${error.config.url}`)
      
      // Mostra detalhes do erro apenas se não for erro comum de autenticação
      if (error.response?.status !== 401 && error.response?.status !== 419) {
        console.error('Detalhes do erro:', error.response?.data)
      }
    }
    
    // Tratamento de erros globais
    if (error.response?.status === 401 && !error.config?.url?.includes('me')) {
      // Token inválido ou expirado (mas não para rota /me que é usada para verificar autenticação)
      console.warn('Erro de autenticação detectado')
    } else if (error.response?.status === 419) {
      // CSRF token mismatch - tenta obter novo token
      console.warn('Token CSRF expirado, tentando renovar...')
    } else if (error.response?.status === 403) {
      console.warn('Acesso negado')
    } else if (error.response?.status >= 500) {
      console.error('Erro interno do servidor')
    } else if (!error.response) {
      console.error('Erro de conexão com o servidor')
    }
    
    return Promise.reject(error)
  }
)

// API para rotas web (autenticação)
const webApi = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_URL || 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN'
});

// Serviços de autenticação
export const authService = {
  async login(credentials) {
    try {
      await getCsrfToken()
      const response = await api.post('/auth/login', credentials)
      return response
    } catch (error) {
      if (error.response?.status === 419) {
        // CSRF token mismatch, tenta novamente
        await getCsrfToken()
        return await api.post('/auth/login', credentials)
      }
      throw error
    }
  },
  
  async register(userData) {
    try {
      await getCsrfToken()
      const response = await api.post('/auth/register', userData)
      return response
    } catch (error) {
      if (error.response?.status === 419) {
        // CSRF token mismatch, tenta novamente
        await getCsrfToken()
        return await api.post('/auth/register', userData)
      }
      throw error
    }
  },
  
  async logout() {
    return api.post('/auth/logout')
  },
  
  async getUser() {
    return api.get('/me')
  }
}

export { getCsrfToken }
export default api