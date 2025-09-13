#!/bin/bash

echo "=== Checking Timeout Configurations ==="
echo

echo "=== PHP Timeouts ==="
echo "max_execution_time: $(php -r "echo ini_get('max_execution_time');")"
echo "max_input_time: $(php -r "echo ini_get('max_input_time');")"
echo "default_socket_timeout: $(php -r "echo ini_get('default_socket_timeout');")"

echo
echo "=== PHP-FPM Configuration ==="
FPM_CONFIG="/etc/php/8.3/fpm/pool.d/www.conf"
if [ -f "$FPM_CONFIG" ]; then
    echo "Checking $FPM_CONFIG:"
    grep -n -E "(request_terminate_timeout|request_slowlog_timeout)" "$FPM_CONFIG" | head -5
else
    echo "PHP-FPM pool config not found"
fi

echo
echo "=== Nginx Timeouts ==="
echo "Main nginx.conf:"
grep -n -E "(proxy_read_timeout|proxy_connect_timeout|proxy_send_timeout|client_header_timeout|client_body_timeout|send_timeout|keepalive_timeout)" /etc/nginx/nginx.conf

echo
echo "Site-specific configs:"
find /etc/nginx/sites-available -name "*" -type f -exec grep -H -E "(proxy_read_timeout|proxy_connect_timeout|proxy_send_timeout|client_header_timeout|client_body_timeout|send_timeout)" {} \;

echo
echo "=== System Limits ==="
echo "Current user limits:"
ulimit -t  # CPU time
ulimit -v  # Virtual memory
ulimit -n  # Open files

echo
echo "=== Test with curl (simulated upload) ==="
echo "Testing basic connectivity..."
timeout 10 curl -I http://localhost/ 2>/dev/null | head -3 || echo "Connection test failed"

echo
echo "=== Check for running processes ==="
echo "Nginx processes:"
ps aux | grep nginx | grep -v grep | wc -l

echo "PHP-FPM processes:"  
ps aux | grep php-fpm | grep -v grep | wc -l

echo
echo "=== Memory usage ==="
free -h