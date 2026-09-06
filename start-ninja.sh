#!/data/data/com.termux/files/usr/bin/bash
set -e

BASE="$HOME/ninja-school-v2-final"
NsoC="$BASE/NsoC"
WEB="$BASE/web"

echo "=========================================="
echo "          NINJA SCHOOL V2"
echo "=========================================="

echo "[1] Kiểm tra MariaDB..."

if ! mariadb -h 127.0.0.1 -P 3306 -u root -e "SELECT 1;" >/dev/null 2>&1; then
    echo "MariaDB chưa chạy."
    echo "Hãy chạy: mariadbd-safe &"
    exit 1
fi

echo "[2] Kiểm tra database..."

mariadb -h 127.0.0.1 -P 3306 -u root \
    -e "USE acc; USE aov; SELECT 1;" >/dev/null

echo "Database OK."

echo "[3] Khởi động Panel..."

php -d opcache.enable=0 \
    -d opcache.enable_cli=0 \
    -S 127.0.0.1:8080 \
    -t "$WEB" \
    > "$BASE/panel.log" 2>&1 &

echo "Panel: http://127.0.0.1:8080"

echo "[4] Khởi động NsoC..."

cd "$NsoC"

exec java -cp "dist/Monter.jar:lib/*" server.NinjaSchool
