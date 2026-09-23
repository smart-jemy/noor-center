#!/bin/bash
# تشغيل مركز نور — نسخة Laravel
cd "$(dirname "$0")"

# التحقق من PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP غير موجود — ثبته أولاً (php-cli + pdo_sqlite + mbstring + gd)"
    exit 1
fi

# التثبيت الأولي
if [ ! -d "vendor" ]; then
    echo "📦 تثبيت المكتبات..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f ".env" ]; then
    cp .env.example .env 2>/dev/null || true
    php artisan key:generate --force
fi

# قاعدة البيانات
if [ ! -f "database/database.sqlite" ]; then
    touch database/database.sqlite
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/uploads
chmod -R 775 storage bootstrap/cache 2>/dev/null

php artisan migrate --seed --force 2>/dev/null

echo ""
echo "🏠 مركز نور شغال على: http://localhost:8000"
echo "👤 دخول الأدمن: 01000000000 / admin123"
echo ""
php artisan serve --host=0.0.0.0 --port=8000
