# Quick session-auth smoke test for Windows PowerShell
param(
  [string]$BaseUrl = "http://localhost:8080",
  [string]$Email = "teste@example.com",
  [string]$Password = "password"
)

# Create a session to persist cookies
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession

Write-Host "[1/5] Fetching CSRF cookie..."
Invoke-WebRequest -UseBasicParsing -Uri "$BaseUrl/sanctum/csrf-cookie" -WebSession $s | Out-Null
$xsrf = $s.Cookies.GetCookies($BaseUrl)['XSRF-TOKEN'].Value

Write-Host "[2/5] Logging in with session..."
$loginBody = @{ email = $Email; password = $Password } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri "$BaseUrl/api/auth/login" -WebSession $s -Headers @{ 'X-XSRF-TOKEN' = $xsrf } -ContentType 'application/json' -Body $loginBody | Out-Null

Write-Host "[3/5] Fetching /api/me to confirm auth..."
$me = Invoke-RestMethod -Method Get -Uri "$BaseUrl/api/me" -WebSession $s
Write-Host "    Authenticated as: $($me.email) (id: $($me.id))"

Write-Host "[4/5] Listing categories..."
$cats = Invoke-RestMethod -Method Get -Uri "$BaseUrl/api/categories" -WebSession $s
Write-Host "    Categories returned: $($cats | Measure-Object | Select-Object -ExpandProperty Count)"

Write-Host "[5/5] Logging out of session..."
Invoke-RestMethod -Method Post -Uri "$BaseUrl/api/auth/logout" -WebSession $s -Headers @{ 'X-XSRF-TOKEN' = $xsrf } | Out-Null
Write-Host "Done."
