# PowerShell Script untuk One-Click Deployment ke Server
$configFile = Join-Path $PSScriptRoot "deploy.config.json"

if (-not (Test-Path $configFile)) {
    Write-Host "`n❌ File deploy.config.json belum ditemukan!" -ForegroundColor Red
    Write-Host "Silakan buat file deploy.config.json berdasarkan deploy.config.example.json dengan mengisi Host, Port, User, dan Remote Path server Anda.`n" -ForegroundColor Yellow
    exit 1
}

$config = Get-Content $configFile | ConvertFrom-Json

$hostName = $config.host
$port = if ($config.port) { $config.port } else { 22 }
$user = $config.user
$remotePath = $config.remote_path

Write-Host "`n==========================================" -ForegroundColor Cyan
Write-Host "🚀 Menghubungkan ke $user@$hostName`:$port..." -ForegroundColor Cyan
Write-Host "📁 Target Folder: $remotePath" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# Perintah remote yang akan dijalankan di server
$remoteCommand = "cd `"$remotePath`" && (if [ -f deploy.sh ]; then bash deploy.sh; else git pull origin main && php artisan migrate --force && php artisan optimize:clear; fi)"

ssh -p $port "$user@$hostName" "$remoteCommand"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✅ DEPLOY KE SERVER BERHASIL DILAKUKAN!" -ForegroundColor Green
} else {
    Write-Host "`n❌ TERJADI KESALAHAN SAAT DEPLOY KE SERVER (Exit code: $LASTEXITCODE)" -ForegroundColor Red
}
