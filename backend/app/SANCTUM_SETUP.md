# Configuração Sanctum - Apenas Autenticação por Sessão

Este projeto utiliza **apenas autenticação por sessão** com CSRF tokens e cookies. 
O suporte a Personal Access Tokens foi removido para simplificar a arquitetura.

## Configuração Aplicada

### 1. Variáveis de Ambiente (.env)
```env
# Configurações de Sessão
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Configurações do Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173,localhost:4200,127.0.0.1:3000,127.0.0.1:5173,127.0.0.1:4200
SPA_URL=http://localhost:3000
```

### 2. CORS Configurado (config/cors.php)
- `supports_credentials: true` - Permite cookies e credenciais
- Domínios permitidos: localhost e 127.0.0.1 nas portas 3000, 5173, 4200
- Paths incluem: `api/*`, `sanctum/csrf-cookie`, `web-auth/*`, `auth/*`

### 3. Middleware Sanctum
- `EnsureFrontendRequestsAreStateful` configurado para web e API
- `statefulApi()` ativo para sessões SPA

### 4. Rotas Sanctum
- `/sanctum/csrf-cookie` disponível automaticamente no Laravel 11

## Como Usar no Frontend (SPA)

### 1. Obter Token CSRF
Antes de fazer login, obtenha o token CSRF:
```javascript
// Fetch CSRF cookie
await fetch('http://localhost:8000/sanctum/csrf-cookie', {
    method: 'GET',
    credentials: 'include', // Importante!
});
```

### 2. Login
```javascript
// Login
const response = await fetch('http://localhost:8000/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    credentials: 'include', // Importante!
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password',
        remember: true
    })
});
```

### 3. Requisições Autenticadas
Após o login, todas as requisições devem incluir `credentials: 'include'`:
```javascript
// Buscar dados do usuário
const user = await fetch('http://localhost:8000/api/me', {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
    },
    credentials: 'include', // Importante!
});
```

### 4. Logout
```javascript
// Logout
await fetch('http://localhost:8000/api/auth/logout', {
    method: 'POST',
    headers: {
        'Accept': 'application/json',
    },
    credentials: 'include',
});
```

## Rotas Disponíveis

### Autenticação (apenas sessão)
- `POST /api/auth/register` - Registrar usuário
- `POST /api/auth/login` - Login (cria sessão)
- `POST /api/auth/logout` - Logout (destrói sessão)
- `GET /api/me` - Dados do usuário logado

### CSRF
- `GET /sanctum/csrf-cookie` - Obter CSRF token (necessário antes do login)

**Nota:** Todas as rotas protegidas usam `auth:sanctum` middleware, mas apenas com autenticação por sessão.

## Importante

1. **Sempre usar `credentials: 'include'`** nas requisições do frontend
2. **Obter CSRF token antes do primeiro login**
3. **Configurar o frontend para o domínio correto** (deve estar em SANCTUM_STATEFUL_DOMAINS)
4. **Usar HTTPS em produção** (atualizar SESSION_SECURE_COOKIE=true)

## Testando

Para testar se está funcionando:

1. Faça uma requisição GET para `/sanctum/csrf-cookie`
2. Verifique se o cookie de sessão é definido
3. Faça login via `/api/auth/login`
4. Acesse `/api/me` sem token, apenas com cookies de sessão

Ou use o script PowerShell: `scripts/auth-session-test.ps1`