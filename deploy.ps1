# PowerShell Script untuk One-Click Deployment ke Server
$configFile = Join-Path $PSScriptRoot "deploy.config.json"

if (-not (Test-Path $configFile)) {
    Write-Host "`n❌ File deploy.config.json belum ditemukan!" -ForegroundColor Red
    exit 1
}

$config = Get-Content $configFile | ConvertFrom-Json

Write-Host "`n==================================================" -ForegroundColor Cyan
Write-Host "🚀 MEMULAI ONE-CLICK DEPLOYMENT KE SERVER" -ForegroundColor Cyan
Write-Host "🌐 Target: $($config.domain) ($($config.host))" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

$deployed = $false

# METODE 1: Webhook HTTPS (Cepat, Aman, Anti-Blokir Firewall)
if ($config.deploy_url) {
    try {
        Write-Host "📡 Mengirim sinyal deploy via Webhook HTTPS..." -ForegroundColor Yellow
        $response = Invoke-RestMethod -Uri $config.deploy_url -Method Get -TimeoutSec 45
        
        if ($response.success) {
            Write-Host "`n✅ DEPLOYMENT VIA WEBHOOK BERHASIL!" -ForegroundColor Green
            Write-Host "--------------------------------------------------" -ForegroundColor DarkGray
            Write-Host "Git Pull Output :`n$($response.git_pull.output)" -ForegroundColor Gray
            Write-Host "Artisan Migrate : $($response.artisan_migrate)" -ForegroundColor Gray
            Write-Host "Artisan Cache   : $($response.artisan_optimize)" -ForegroundColor Gray
            Write-Host "--------------------------------------------------" -ForegroundColor DarkGray
            $deployed = $true
        } else {
            Write-Host "⚠️ Webhook merespons tetapi gagal: $($response.message)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "⚠️ Webhook belum aktif di server atau koneksi timeout: $_" -ForegroundColor DarkYellow
        Write-Host "Mencoba fallback via SSH..." -ForegroundColor DarkYellow
    }
}

# METODE 2: Fallback via SSH (jika webhook belum tersedia di server)
if (-not $deployed -and $config.user -and $config.host) {
    $port = if ($config.port) { $config.port } else { 22 }
    $remotePath = $config.remote_path
    
    Write-Host "`n🔑 Mencoba deployment via SSH ($($config.user)@$($config.host):$port)..." -ForegroundColor Yellow
    $remoteCommand = "cd `"$remotePath`" && (if [ -f deploy.sh ]; then bash deploy.sh; else git pull origin main && php artisan migrate --force && php artisan optimize:clear; fi)"
    
    ssh -o ConnectTimeout=8 -p $port "$($config.user)@$($config.host)" "$remoteCommand"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n✅ DEPLOYMENT VIA SSH BERHASIL!" -ForegroundColor Green
        $deployed = $true
    }
}

if ($deployed) {
    Write-Host "`n🎉 SERVER ANDA TELAH TER-UPDATE DENGAN VERSI TERBARU!" -ForegroundColor Green
} else {
    Write-Host "`n💡 CATATAN: Untuk update pertama kali ini, silakan jalankan 1 kali di terminal cPanel Anda:" -ForegroundColor Yellow
    Write-Host "cd $($config.remote_path) && bash deploy.sh`n" -ForegroundColor White
}
