#!/data/data/com.termux/files/usr/bin/bash
set -e

BASE="$HOME/ninja-school-v2-final"
NsoC="$BASE/NsoC"
WEB="$BASE/web"
SQL="$BASE/database/nso.sql"
SOCKET="$PREFIX/var/run/mysqld/mysqld.sock"

echo "=========================================="
echo "        NINJA SCHOOL V2 - INSTALL"
echo "=========================================="

if [ ! -d "$BASE" ]; then
    echo "LỖI: Không tìm thấy $BASE"
    exit 1
fi

echo "[1] Cài dependency..."
pkg update -y
pkg install -y git ant openjdk-21 mariadb php

echo "[2] Kiểm tra NsoC..."

if [ ! -f "$NsoC/build.xml" ]; then
    echo "LỖI: Không tìm thấy NsoC/build.xml"
    exit 1
fi

echo "[3] Build NsoC..."
cd "$NsoC"
ant clean jar

if [ ! -f "$NsoC/dist/Monter.jar" ]; then
    echo "LỖI: Không tạo được Monter.jar"
    exit 1
fi

echo "[4] Kiểm tra Panel..."

if [ ! -f "$WEB/index.php" ]; then
    echo "LỖI: Không tìm thấy web/index.php"
    exit 1
fi

echo "[5] Tạo start-ninja.sh..."

cat > "$BASE/start-ninja.sh" <<'RUN'
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
    echo "MariaDB chưa chạy. Đang khởi động..."

    mariadbd-safe >/dev/null 2>&1 &

    echo "Đang chờ MariaDB..."

    for i in {1..30}; do
        if mariadb -h 127.0.0.1 -P 3306 -u root -e "SELECT 1;" >/dev/null 2>&1; then
            echo "MariaDB đã sẵn sàng."
            break
        fi
        sleep 1
    done

    if ! mariadb -h 127.0.0.1 -P 3306 -u root -e "SELECT 1;" >/dev/null 2>&1; then
        echo "LỖI: Không thể khởi động MariaDB."
        exit 1
    fi
else
    echo "MariaDB đang chạy."
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
RUN

chmod +x "$BASE/start-ninja.sh"

echo
echo "=========================================="
echo "       CÀI ĐẶT HOÀN TẤT"
echo "=========================================="
echo
echo "Server + Panel:"
echo "  bash ~/ninja-school-v2-final/start-ninja.sh"
echo
echo "Panel:"
echo "  http://127.0.0.1:8080"
echo
