#!/bin/bash
set -e

echo "=========================================="
echo "🚀 MEMULAI DEPLOYMENT KE SERVER"
echo "=========================================="

echo "📥 [1/4] Mengambil kode terbaru dari Git..."
git pull origin main

echo "🗄️ [2/4] Menjalankan migrasi database..."
php artisan migrate --force

echo "⚡ [3/4] Optimasi cache Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=========================================="
echo "✅ DEPLOYMENT BERHASIL SEPENUHNYA!"
echo "=========================================="
