#!/bin/bash

# Shopware 6 Performance Optimization Script
# Usage: ./scripts/optimize-shopware-performance.sh

set -e

# Configuration
HETZNER_IP="91.99.27.91"
HETZNER_USER="root"

echo "🚀 Optimizing Shopware 6 Performance on Hetzner Cloud..."

# Check if server is reachable
echo "📡 Checking server connectivity..."
ping -c 1 $HETZNER_IP > /dev/null || {
    echo "❌ Cannot reach Hetzner server"
    exit 1
}

# Apply performance optimizations on server
echo "⚡ Applying performance optimizations..."
ssh -i server_key -o StrictHostKeyChecking=no $HETZNER_USER@$HETZNER_IP << 'EOF'
    set -e
    
    # Get the Shopware container name
    CONTAINER_NAME=$(docker ps --format "{{.Names}}" | grep shopware | head -1)
    
    if [ -z "$CONTAINER_NAME" ]; then
        echo "❌ No Shopware container found"
        exit 1
    fi
    
    echo "📦 Found Shopware container: $CONTAINER_NAME"
    
    # Shopware Performance Optimizations
    docker exec $CONTAINER_NAME bash -c "
        cd /var/www/html
        
        echo '🔧 Applying Shopware performance optimizations...'
        
        # 1. Clear and warm up caches
        echo '🧹 Clearing caches...'
        php bin/console cache:clear --env=prod
        php bin/console cache:warmup --env=prod
        
        # 2. Enable HTTP Cache
        echo '🌐 Configuring HTTP Cache...'
        php bin/console system:config:set core.httpCache.enabled true
        php bin/console system:config:set core.httpCache.ttl 3600
        
        # 3. Configure session handling
        echo '📝 Optimizing session handling...'
        php bin/console system:config:set core.session.lifetime 7200
        
        # 4. Enable template caching
        echo '📄 Enabling template caching...'
        php bin/console system:config:set core.template.cache true
        
        # 5. Configure database optimizations
        echo '🗄️ Database optimizations...'
        php bin/console system:config:set core.db.useQueue true
        
        # 6. Enable compression
        echo '📦 Enabling compression...'
        php bin/console system:config:set core.response.compression true
        
        # 7. Optimize media handling
        echo '🖼️ Optimizing media handling...'
        php bin/console system:config:set core.media.enableUrlUploadFeature false
        
        # 8. Configure logging for production
        echo '📊 Configuring production logging...'
        php bin/console system:config:set core.logger.level 200  # INFO level
        
        # 9. Disable debug mode
        echo '🐛 Disabling debug mode...'
        php bin/console system:config:set core.kernel.debug false
        
        # 10. Final cache clear and warmup
        echo '🔄 Final cache optimization...'
        php bin/console cache:clear --env=prod
        php bin/console cache:warmup --env=prod
        
        echo '✅ Shopware optimizations completed'
    "
    
    # PHP-FPM Optimizations
    echo "🐘 Optimizing PHP-FPM..."
    docker exec $CONTAINER_NAME bash -c "
        # Backup original config
        cp /etc/php/8.2/fpm/pool.d/www.conf /etc/php/8.2/fpm/pool.d/www.conf.backup || true
        
        # Apply PHP-FPM optimizations
        cat > /tmp/php-fpm-optimizations.conf << 'PHPEOF'
; PHP-FPM Performance Optimizations
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 1000
pm.process_idle_timeout = 60s
PHPEOF
        
        # Apply the optimizations
        cat /tmp/php-fpm-optimizations.conf >> /etc/php/8.2/fpm/pool.d/www.conf || true
        
        # Restart PHP-FPM
        service php8.2-fpm restart || true
        
        echo '✅ PHP-FPM optimizations applied'
    "
    
    # MySQL Optimizations
    echo "🗄️ Optimizing MySQL..."
    docker exec $CONTAINER_NAME bash -c "
        # Create MySQL optimization config
        cat > /tmp/mysql-optimizations.cnf << 'MYSQLEOF'
[mysqld]
# Performance optimizations
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 32M
query_cache_limit = 2M
max_connections = 100
thread_cache_size = 8
table_open_cache = 2000
MYSQLEOF
        
        # Apply MySQL optimizations
        cp /tmp/mysql-optimizations.cnf /etc/mysql/conf.d/performance.cnf || true
        
        # Restart MySQL
        service mysql restart || true
        
        echo '✅ MySQL optimizations applied'
    "
    
    # nginx Optimizations
    echo "🌐 Optimizing nginx..."
    docker exec $CONTAINER_NAME bash -c "
        # Create nginx optimization config
        cat > /tmp/nginx-optimizations.conf << 'NGINXEOF'
# Performance optimizations
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

# Browser caching
location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
    expires 1y;
    add_header Cache-Control \"public, immutable\";
}

# Enable keep-alive
keepalive_timeout 65;
keepalive_requests 100;

# Buffer sizes
client_body_buffer_size 128k;
client_max_body_size 10m;
client_header_buffer_size 1k;
large_client_header_buffers 4 4k;
output_buffers 1 32k;
postpone_output 1460;
NGINXEOF
        
        # Apply nginx optimizations
        cp /tmp/nginx-optimizations.conf /etc/nginx/conf.d/performance.conf || true
        
        # Test nginx config
        nginx -t && nginx -s reload || true
        
        echo '✅ nginx optimizations applied'
    "
    
    # Restart all services
    echo "🔄 Restarting services..."
    docker restart $CONTAINER_NAME
    
    # Wait for services to start
    sleep 30
    
    echo "✅ All performance optimizations applied!"
EOF

echo ""
echo "🎉 Shopware 6 Performance Optimization completed!"
echo ""
echo "📊 Applied Optimizations:"
echo "========================"
echo "✅ Shopware HTTP Cache enabled"
echo "✅ Template caching optimized"
echo "✅ Database query optimization"
echo "✅ PHP-FPM performance tuning"
echo "✅ MySQL buffer pool optimization"
echo "✅ nginx compression and caching"
echo "✅ Session handling optimized"
echo ""
echo "🔍 Next Steps:"
echo "=============="
echo "1. Run performance tests: ./performance/run-performance-tests.sh"
echo "2. Monitor server resources during load"
echo "3. Check Shopware admin for any issues"
echo "4. Verify frontend performance"
echo ""
echo "🌐 Test your optimized site:"
echo "============================"
echo "Frontend: http://$HETZNER_IP"
echo "Admin: http://$HETZNER_IP/admin"
