param(
    [switch]$ResetDb
)

# PowerShell Script One-Click Deployment ke Server
$configFile = Join-Path $PSScriptRoot "deploy.config.json"

if (-not (Test-Path $configFile)) {
    Write-Host ""
    Write-Host "File deploy.config.json belum ditemukan!" -ForegroundColor Red
    exit 1
}

$config = Get-Content $configFile | ConvertFrom-Json

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host " MEMULAI ONE-CLICK DEPLOYMENT KE SERVER" -ForegroundColor Cyan
Write-Host " Target: $($config.domain) ($($config.host))" -ForegroundColor Cyan
if ($ResetDb) {
    Write-Host " MODE: RESET DATABASE & SEEDER FRESH" -ForegroundColor Magenta
}
Write-Host "==================================================" -ForegroundColor Cyan

$deployed = $false

# METODE 1: Webhook HTTPS
if ($config.deploy_url) {
    $targetUrl = $config.deploy_url
    if ($ResetDb) {
        $targetUrl += "&reset_db=yes"
    }
    Write-Host " Mengirim sinyal deploy via Webhook HTTPS..." -ForegroundColor Yellow
    try {
        $resp = Invoke-RestMethod -Uri $targetUrl -Method Get -TimeoutSec 60
        if ($resp.success -eq $true) {
            Write-Host ""
            Write-Host " DEPLOYMENT VIA WEBHOOK BERHASIL!" -ForegroundColor Green
            Write-Host "--------------------------------------------------" -ForegroundColor DarkGray
            Write-Host "Git Output     : $($resp.git_pull.output)" -ForegroundColor Gray
            Write-Host "Artisan Migrate: $($resp.artisan_migrate)" -ForegroundColor Gray
            Write-Host "Artisan Cache  : $($resp.artisan_optimize)" -ForegroundColor Gray
            Write-Host "--------------------------------------------------" -ForegroundColor DarkGray
            $deployed = $true
        } else {
            Write-Host " Webhook merespons: $($resp.message)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host " Webhook belum aktif di server atau koneksi terputus." -ForegroundColor DarkYellow
    }
}

# METODE 2: SSH Fallback
if (-not $deployed) {
    if ($config.user -and $config.host) {
        $port = if ($config.port) { $config.port } else { 22 }
        $remotePath = $config.remote_path
        Write-Host ""
        Write-Host " Mencoba via SSH ($($config.user)@$($config.host):$port)..." -ForegroundColor Yellow
        $migrateCmd = if ($ResetDb) { "php artisan migrate:fresh --seed --force" } else { "php artisan migrate --force" }
        $cmd = "cd `"$remotePath`" && git pull origin main && $migrateCmd && php artisan optimize:clear"
        ssh -o ConnectTimeout=8 -p $port "$($config.user)@$($config.host)" "$cmd"
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host " DEPLOYMENT VIA SSH BERHASIL!" -ForegroundColor Green
            $deployed = $true
        }
    }
}

if ($deployed) {
    Write-Host ""
    Write-Host " SERVER ANDA TELAH TER-UPDATE DENGAN VERSI TERBARU!" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host " CATATAN: Untuk update pertama kali ini, silakan jalankan 1 kali di terminal cPanel Anda:" -ForegroundColor Yellow
    Write-Host "cd $($config.remote_path) && git pull origin main && php artisan migrate --force && php artisan optimize:clear" -ForegroundColor White
    Write-Host ""
}
