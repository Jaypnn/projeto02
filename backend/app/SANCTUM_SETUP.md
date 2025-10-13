# Configuração Sanctum com Sessões, CSRF e Cookies

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

### 2. Login com Sessão
```javascript
// Login
const response = await fetch('http://localhost:8000/api/auth/session/login', {
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
await fetch('http://localhost:8000/api/auth/session/logout', {
    method: 'POST',
    headers: {
        'Accept': 'application/json',
    },
    credentials: 'include',
});
```

## Rotas Disponíveis

### Autenticação por Token
- `POST /api/auth/register` - Registrar
- `POST /api/auth/login` - Login (retorna token)
- `POST /api/auth/logout` - Logout (token)

### Autenticação por Sessão
- `GET /sanctum/csrf-cookie` - Obter CSRF token
- `POST /api/auth/session/login` - Login (sessão)
- `POST /api/auth/session/logout` - Logout (sessão)
- `GET /api/me` - Dados do usuário logado

### Rotas Web (alternativas)
- `POST /web-auth/login` - Login via web
- `POST /web-auth/logout` - Logout via web
- `GET /web-auth/me` - Dados do usuário via web

## Importante

1. **Sempre usar `credentials: 'include'`** nas requisições do frontend
2. **Obter CSRF token antes do primeiro login**
3. **Configurar o frontend para o domínio correto** (deve estar em SANCTUM_STATEFUL_DOMAINS)
4. **Usar HTTPS em produção** (atualizar SESSION_SECURE_COOKIE=true)

## Testando

Para testar se está funcionando:

1. Faça uma requisição GET para `/sanctum/csrf-cookie`
2. Verifique se o cookie de sessão é definido
3. Faça login via `/api/auth/session/login`
4. Acesse `/api/me` sem token, apenas com cookies