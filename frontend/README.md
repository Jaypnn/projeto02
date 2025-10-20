# Finance App Frontend

Frontend SPA moderno para gerenciamento de finanças pessoais, desenvolvido com Vue.js 3, Vuex, Vue Router e Tailwind CSS.

## 🚀 Tecnologias

- **Vue.js 3** - Framework JavaScript reativo
- **Vuex 4** - Gerenciamento de estado global
- **Vue Router 4** - Roteamento SPA
- **Tailwind CSS** - Framework CSS utilitário
- **Axios** - Cliente HTTP para API
- **Chart.js** - Biblioteca de gráficos
- **Heroicons** - Ícones SVG
- **Vite** - Build tool moderna

## 📁 Estrutura do Projeto

```
src/
├── assets/          # Imagens, fontes e recursos estáticos
├── components/      # Componentes reutilizáveis
│   └── charts/     # Componentes de gráficos
├── layouts/        # Layouts da aplicação
├── router/         # Configuração de rotas
├── services/       # Camada de API
├── store/          # Gerenciamento de estado (Vuex)
│   └── modules/    # Módulos do store
├── views/          # Páginas/views da aplicação
│   └── auth/       # Páginas de autenticação
├── App.vue         # Componente raiz
├── main.js         # Ponto de entrada
└── style.css       # Estilos globais
```

## 🏗️ Funcionalidades Implementadas

### ✅ Core Features
- [x] Arquitetura SPA com Vue.js 3
- [x] Gerenciamento de estado com Vuex
- [x] Sistema de roteamento protegido
- [x] Interface responsiva com Tailwind CSS
- [x] Layouts reutilizáveis
- [x] Service layer para APIs

### ✅ Autenticação
- [x] Tela de login com validação
- [x] Tela de registro com força de senha
- [x] Proteção de rotas
- [x] Guards de navegação
- [x] Interceptors HTTP com tokens

### ✅ Dashboard
- [x] Cards de resumo financeiro
- [x] Gráficos de fluxo de caixa
- [x] Distribuição por categorias
- [x] Transações recentes
- [x] Metas financeiras

### ✅ Store Modules
- [x] Auth (autenticação)
- [x] Transactions (transações)
- [x] Budgets (orçamentos)
- [x] Categories (categorias)
- [x] Goals (metas)
- [x] Accounts (contas)

### 🚧 Em Desenvolvimento
- [ ] CRUD completo de transações
- [ ] Interface de orçamentos
- [ ] Gestão de categorias
- [ ] Sistema de metas
- [ ] Perfil do usuário
- [ ] Relatórios avançados

## ⚙️ Configuração

### Pré-requisitos
- Node.js >= 16.0.0
- npm >= 7.0.0

### Instalação

1. **Instalar dependências:**
   ```bash
   npm install
   ```

2. **Configurar variáveis de ambiente:**
   ```bash
   cp .env.example .env
   ```
   
   Edite o arquivo `.env` com suas configurações:
   ```env
   VUE_APP_API_URL=http://localhost:8000/api
   VUE_APP_NAME=FinanceApp
   ```

3. **Executar em desenvolvimento:**
   ```bash
   npm run dev
   ```

4. **Build para produção:**
   ```bash
   npm run build
   ```

## 🎨 Design System

### Cores Principais
- **Primary**: Blue (Azul) - #3B82F6
- **Success**: Green (Verde) - #10B981
- **Danger**: Red (Vermelho) - #EF4444
- **Warning**: Yellow (Amarelo) - #F59E0B
- **Info**: Indigo (Índigo) - #6366F1

### Componentes
- Interface minimalista e moderna
- Cards com shadow e bordas arredondadas
- Botões com states (hover, focus, disabled)
- Formulários com validação visual
- Navegação responsiva

## 🔌 Integração com Backend

O frontend está preparado para se comunicar com o backend PHP Laravel através de:

- **API RESTful** - Endpoints padronizados
- **Autenticação JWT** - Tokens Bearer
- **Interceptors** - Tratamento automático de erros
- **Retry Logic** - Reenvio automático em falhas
- **Loading States** - Estados de carregamento

### Endpoints Esperados
```
GET    /api/auth/user
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout

GET    /api/transactions
POST   /api/transactions
PUT    /api/transactions/{id}
DELETE /api/transactions/{id}

... (similar para budgets, categories, goals, accounts)
```

## 🚀 Próximos Passos

1. **Finalizar CRUD de Transações**
   - Modal de criação/edição
   - Filtros avançados
   - Paginação

2. **Implementar Orçamentos**
   - Interface de criação
   - Acompanhamento visual
   - Alertas de limite

3. **Sistema de Notificações**
   - Toast messages
   - Alertas em tempo real
   - Notificações push

4. **Relatórios e Analytics**
   - Gráficos avançados
   - Exportação PDF/Excel
   - Comparativos temporais

## 📱 Responsividade

A aplicação foi desenvolvida com mobile-first approach:
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px  
- **Desktop**: > 1024px

## 🧪 Testes

```bash
# Executar testes unitários (futuro)
npm run test

# Executar testes e2e (futuro)
npm run test:e2e
```

## 📚 Documentação Adicional

- [Vue.js 3 Guide](https://vuejs.org/guide/)
- [Vuex Documentation](https://vuex.vuejs.org/)
- [Vue Router Documentation](https://router.vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

## 👥 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

Learn more about IDE Support for Vue in the [Vue Docs Scaling up Guide](https://vuejs.org/guide/scaling-up/tooling.html#ide-support).
