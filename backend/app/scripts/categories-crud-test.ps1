# Categories CRUD smoke test with session auth
param(
  [string]$BaseUrl = "http://localhost:8080",
  [string]$Email = "teste@example.com",
  [string]$Password = "password"
)

$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession

Write-Host "[1/8] CSRF cookie"
Invoke-WebRequest -UseBasicParsing -Uri "$BaseUrl/sanctum/csrf-cookie" -WebSession $s | Out-Null
$xsrf = $s.Cookies.GetCookies($BaseUrl)['XSRF-TOKEN'].Value

Write-Host "[2/8] Login"
$loginBody = @{ email = $Email; password = $Password } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri "$BaseUrl/api/auth/session/login" -WebSession $s -Headers @{ 'X-XSRF-TOKEN' = $xsrf } -ContentType 'application/json' -Body $loginBody | Out-Null

Write-Host "[3/8] List categories"
$before = Invoke-RestMethod -Method Get -Uri "$BaseUrl/api/categories?per_page=5" -WebSession $s
$beforeCount = $before.data.Count
Write-Host "    Count before: $beforeCount"

Write-Host "[4/8] Create category"
$newCatBody = @{ name = "Teste CRUD $(Get-Random)"; type = "expense"; description = "categoria teste"; color = "#3366FF"; icon = "mdi-cash" } | ConvertTo-Json
$createResp = Invoke-RestMethod -Method Post -Uri "$BaseUrl/api/categories" -WebSession $s -Headers @{ 'X-XSRF-TOKEN' = $xsrf } -ContentType 'application/json' -Body $newCatBody
$catId = $createResp.data.id
Write-Host "    Created id: $catId"

Write-Host "[5/8] Show category"
$show = Invoke-RestMethod -Method Get -Uri "$BaseUrl/api/categories/$catId" -WebSession $s
Write-Host "    Name: $($show.data.name) Type: $($show.data.type)"

Write-Host "[6/8] Update category"
$updBody = @{ name = ($show.data.name + " (upd)"); color = "#FF6633" } | ConvertTo-Json
$upd = Invoke-RestMethod -Method Put -Uri "$BaseUrl/api/categories/$catId" -WebSession $s -Headers @{ 'X-XSRF-TOKEN' = $xsrf } -ContentType 'application/json' -Body $updBody
Write-Host "    New name: $($upd.data.name) Color: $($upd.data.color)"

Write-Host "[7/8] Delete category"
Invoke-RestMethod -Method Delete -Uri "$BaseUrl/api/categories/$catId" -WebSession $s -Headers @{ 'X-XSRF-TOKEN' = $xsrf } | Out-Null

Write-Host "[8/8] Verify deletion"
$after = Invoke-RestMethod -Method Get -Uri "$BaseUrl/api/categories?per_page=5" -WebSession $s
Write-Host "    Count after: $($after.data.Count)"

Write-Host "Done."