# Script para testar o registro de usuário
# Serve para verificar se a API de registro está funcionando

Write-Host "🔍 Testando API de Registro..." -ForegroundColor Blue

# Primeiro, obter o CSRF token
Write-Host "1. Obtendo CSRF token..." -ForegroundColor Yellow
$csrf_response = Invoke-WebRequest -Uri "http://localhost:8000/sanctum/csrf-cookie" -UseBasicParsing -SessionVariable session
Write-Host "✅ CSRF cookie obtido" -ForegroundColor Green

# Dados do usuário de teste
$userData = @{
    name = "João da Silva Teste"
    email = "joao.teste@email.com"
    password = "Senha123!"
    password_confirmation = "Senha123!"
} | ConvertTo-Json

Write-Host "2. Testando registro com dados:" -ForegroundColor Yellow
Write-Host "   Nome: João da Silva Teste"
Write-Host "   Email: joao.teste@email.com"

try {
    # Fazer registro
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/auth/register" `
                                 -Method POST `
                                 -Body $userData `
                                 -ContentType "application/json" `
                                 -WebSession $session `
                                 -UseBasicParsing

    Write-Host "✅ Registro realizado com sucesso!" -ForegroundColor Green
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    
    $responseData = $response.Content | ConvertFrom-Json
    Write-Host "Resposta: $($responseData.message)" -ForegroundColor Green
    
    if ($responseData.user) {
        Write-Host "Usuário criado:" -ForegroundColor Green
        Write-Host "  ID: $($responseData.user.id)"
        Write-Host "  Nome: $($responseData.user.name)"
        Write-Host "  Email: $($responseData.user.email)"
    }
    
} catch {
    Write-Host "❌ Erro no registro:" -ForegroundColor Red
    Write-Host "Status: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $errorResponse = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($errorResponse)
        $errorBody = $reader.ReadToEnd()
        Write-Host "Detalhes: $errorBody" -ForegroundColor Red
    }
}

Write-Host "`n🔍 Teste finalizado" -ForegroundColor Blue