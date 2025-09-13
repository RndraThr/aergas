#!/bin/bash

echo "=== Fixing PHP Upload Limits ==="
echo "Current settings:"
php -i | grep -E "(upload_max_filesize|post_max_size)"
echo

# Find all PHP ini files
echo "Finding PHP configuration files..."
find /etc/php -name "php.ini" -type f

echo
echo "=== Updating PHP-FPM configuration ==="

# Update main php.ini for PHP-FPM
PHP_INI="/etc/php/8.3/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    echo "Updating $PHP_INI"
    
    # Backup original
    cp "$PHP_INI" "$PHP_INI.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Update upload_max_filesize
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 35M/' "$PHP_INI"
    
    # Update post_max_size
    sed -i 's/^post_max_size = .*/post_max_size = 40M/' "$PHP_INI"
    
    # Update max_execution_time
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    
    # Update memory_limit
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
    
    echo "Updated $PHP_INI"
else
    echo "PHP-FPM ini file not found at $PHP_INI"
fi

# Also update CLI version to be consistent
CLI_INI="/etc/php/8.3/cli/php.ini"
if [ -f "$CLI_INI" ]; then
    echo "Updating $CLI_INI"
    
    # Backup original
    cp "$CLI_INI" "$CLI_INI.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Update upload_max_filesize
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 35M/' "$CLI_INI"
    
    # Update post_max_size
    sed -i 's/^post_max_size = .*/post_max_size = 40M/' "$CLI_INI"
    
    # Update max_execution_time
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$CLI_INI"
    
    # Update memory_limit
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$CLI_INI"
    
    echo "Updated $CLI_INI"
else
    echo "PHP CLI ini file not found at $CLI_INI"
fi

echo
echo "=== Updating Nginx configuration ==="

# Find nginx site configurations
NGINX_SITES_DIR="/etc/nginx/sites-available"
if [ -d "$NGINX_SITES_DIR" ]; then
    echo "Checking Nginx sites in $NGINX_SITES_DIR"
    
    # Update all site configurations
    for site_file in "$NGINX_SITES_DIR"/*; do
        if [ -f "$site_file" ] && [ "$(basename "$site_file")" != "default" ]; then
            echo "Checking $site_file"
            
            # Backup original
            cp "$site_file" "$site_file.backup.$(date +%Y%m%d_%H%M%S)"
            
            # Update client_max_body_size
            sed -i 's/client_max_body_size [^;]*/client_max_body_size 35M/' "$site_file"
            
            echo "Updated client_max_body_size in $site_file"
        fi
    done
else
    echo "Nginx sites directory not found"
fi

# Also check main nginx.conf
NGINX_CONF="/etc/nginx/nginx.conf"
if [ -f "$NGINX_CONF" ]; then
    echo "Checking main nginx configuration"
    cp "$NGINX_CONF" "$NGINX_CONF.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Update client_max_body_size in http block if exists
    sed -i 's/client_max_body_size [^;]*/client_max_body_size 35M/' "$NGINX_CONF"
    
    echo "Updated $NGINX_CONF"
fi

echo
echo "=== Testing configuration ==="

# Test nginx configuration
echo "Testing Nginx configuration..."
nginx -t

if [ $? -eq 0 ]; then
    echo "Nginx configuration is valid"
else
    echo "ERROR: Nginx configuration has errors!"
    exit 1
fi

echo
echo "=== Restarting services ==="

# Restart PHP-FPM
echo "Restarting PHP-FPM..."
systemctl restart php8.3-fpm

if [ $? -eq 0 ]; then
    echo "PHP-FPM restarted successfully"
else
    echo "ERROR: Failed to restart PHP-FPM"
    exit 1
fi

# Restart Nginx
echo "Restarting Nginx..."
systemctl restart nginx

if [ $? -eq 0 ]; then
    echo "Nginx restarted successfully"
else
    echo "ERROR: Failed to restart Nginx"
    exit 1
fi

echo
echo "=== Verification ==="

# Check service status
echo "Checking service status..."
systemctl is-active php8.3-fpm
systemctl is-active nginx

echo
echo "New PHP settings:"
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time|memory_limit)"

echo
echo "=== Configuration Update Complete ==="
echo "Upload limits should now be set to 35M"
echo "If issues persist, check PHP-FPM pool configuration in /etc/php/8.3/fpm/pool.d/"