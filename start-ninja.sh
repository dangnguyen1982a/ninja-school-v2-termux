#!/data/data/com.termux/files/usr/bin/bash

BASE="$HOME/ninja"
WEB="$BASE/web"
LOG="$BASE/server.log"

echo "======================================"
echo "      NINJA SCHOOL V2 - TERMUX"
echo "======================================"

# Kiểm tra Java
if ! command -v java >/dev/null 2>&1; then
    echo "[!] Chưa có Java"
    echo "    pkg install openjdk-21"
    exit 1
fi

# Kiểm tra MySQL
if ! command -v mariadb >/dev/null 2>&1; then
    echo "[!] Chưa có MariaDB"
    echo "    pkg install mariadb"
    exit 1
fi

# Tạo thư mục tạm PHP
mkdir -p "$PREFIX/tmp"
chmod 700 "$PREFIX/tmp"

# Dừng server cũ nếu có
pkill -f 'server.NinjaSchool' 2>/dev/null || true

echo "[+] Starting Ninja School Server..."

cd "$BASE/NsoC"

nohup java -cp "dist/Monter.jar:lib/*" server.NinjaSchool \
    > "$LOG" 2>&1 &

SERVER_PID=$!

echo "[+] Server PID: $SERVER_PID"
echo "[+] Log: $LOG"

sleep 2

echo ""
echo "[+] Starting Admin Panel..."
echo "[+] http://127.0.0.1:8080"
echo ""

cd "$WEB"

exec php -c "$WEB/php-termux.ini" \
    -S 127.0.0.1:8080 \
    -t "$WEB"
