#!/bin/bash

echo "=== Debug Upload Configuration ==="
echo "Date: $(date)"
echo

echo "=== Current PHP Configuration ==="
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time|memory_limit|max_input_time)"
echo

echo "=== Nginx Configuration ==="
echo "Main nginx.conf client_max_body_size:"
grep -n "client_max_body_size" /etc/nginx/nginx.conf | head -5

echo
echo "Site specific client_max_body_size:"
find /etc/nginx/sites-available -name "*" -type f -exec grep -H "client_max_body_size" {} \; | head -5

echo
echo "=== Apache Configuration (if applicable) ==="
if [ -f /etc/apache2/apache2.conf ]; then
    echo "Apache found - checking LimitRequestBody:"
    grep -r "LimitRequestBody" /etc/apache2/ 2>/dev/null | head -3
else
    echo "Apache not found or not configured"
fi

echo
echo "=== Check .htaccess in web root ==="
WEB_ROOT="/var/www/aergas/public"
if [ -f "$WEB_ROOT/.htaccess" ]; then
    echo "Found .htaccess:"
    grep -n -E "(LimitRequestBody|upload_max_filesize|post_max_size)" "$WEB_ROOT/.htaccess"
else
    echo ".htaccess not found in $WEB_ROOT"
fi

echo
echo "=== Test file creation ==="
TEST_FILE="/tmp/test_upload_30mb.txt"
echo "Creating 30MB test file..."
dd if=/dev/zero of="$TEST_FILE" bs=1M count=30 2>/dev/null
if [ -f "$TEST_FILE" ]; then
    echo "✓ Created 30MB test file: $(ls -lh $TEST_FILE | awk '{print $5}')"
    rm "$TEST_FILE"
else
    echo "✗ Failed to create test file"
fi

echo
echo "=== Check disk space ==="
df -h | grep -E "(Filesystem|/var|/tmp)"

echo
echo "=== Check system limits ==="
echo "System ulimits:"
ulimit -a | grep -E "(file size|virtual memory|open files)"

echo
echo "=== Check process limits for nginx/php-fpm ==="
if pgrep nginx > /dev/null; then
    NGINX_PID=$(pgrep nginx | head -1)
    echo "Nginx process limits:"
    cat /proc/$NGINX_PID/limits 2>/dev/null | grep -E "(Max file size|Max address space)" || echo "Cannot read nginx limits"
fi

if pgrep php-fpm > /dev/null; then
    PHP_FPM_PID=$(pgrep php-fpm | head -1)
    echo "PHP-FPM process limits:"
    cat /proc/$PHP_FPM_PID/limits 2>/dev/null | grep -E "(Max file size|Max address space)" || echo "Cannot read php-fpm limits"
fi

echo
echo "=== Check web server error logs ==="
echo "Recent nginx errors (last 10 lines):"
if [ -f /var/log/nginx/error.log ]; then
    tail -10 /var/log/nginx/error.log
else
    echo "Nginx error log not found"
fi

echo
echo "Recent apache errors (if applicable):"
if [ -f /var/log/apache2/error.log ]; then
    tail -5 /var/log/apache2/error.log
else
    echo "Apache error log not found"
fi

echo
echo "=== Check PHP-FPM errors ==="
if [ -f /var/log/php8.3-fpm.log ]; then
    echo "Recent PHP-FPM errors:"
    tail -10 /var/log/php8.3-fpm.log
else
    echo "PHP-FPM log not found at /var/log/php8.3-fpm.log"
    
    # Try alternative locations
    find /var/log -name "*fpm*" -type f 2>/dev/null | head -3
fi

echo
echo "=== Test basic connectivity ==="
echo "Testing if Laravel is reachable:"
curl -I http://localhost/dashboard 2>/dev/null | head -3 || echo "Cannot reach Laravel application"

echo
echo "=== Check Laravel application logs ==="
LARAVEL_LOG="/var/www/aergas/storage/logs/laravel.log"
if [ -f "$LARAVEL_LOG" ]; then
    echo "Recent Laravel log entries (last 10 lines):"
    tail -10 "$LARAVEL_LOG"
else
    echo "Laravel log not found at $LARAVEL_LOG"
fi

echo
echo "=== Final Summary ==="
echo "✓ PHP upload_max_filesize: $(php -r "echo ini_get('upload_max_filesize');")"
echo "✓ PHP post_max_size: $(php -r "echo ini_get('post_max_size');")"
echo "✓ Nginx client_max_body_size: $(grep -h client_max_body_size /etc/nginx/sites-available/* 2>/dev/null | head -1 | awk '{print $2}' | tr -d ';')"
echo "✓ Services status:"
systemctl is-active nginx php8.3-fpm 2>/dev/null || echo "Cannot check service status"

echo
echo "=== Debug Upload Configuration Complete ===