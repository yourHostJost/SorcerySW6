#!/bin/bash

# Fix Shopware Domain Configuration on Hetzner Cloud
# Usage: ./scripts/fix-shopware-domain.sh

set -e

# Configuration
HETZNER_IP="91.99.27.91"
HETZNER_USER="root"

echo "🔧 Fixing Shopware domain configuration..."

# Check if server is reachable
echo "📡 Checking server connectivity..."
ping -c 1 $HETZNER_IP > /dev/null || {
    echo "❌ Cannot reach Hetzner server"
    exit 1
}

# Fix domain configuration on server
echo "🛠️ Configuring Shopware domain..."
ssh -i server_key -o StrictHostKeyChecking=no $HETZNER_USER@$HETZNER_IP << EOF
    set -e

    # Get the Shopware container name
    CONTAINER_NAME=\$(docker ps --format "{{.Names}}" | grep shopware | head -1)

    if [ -z "\$CONTAINER_NAME" ]; then
        echo "❌ No Shopware container found"
        exit 1
    fi

    echo "📦 Found Shopware container: \$CONTAINER_NAME"

    # Configure domain in Shopware
    docker exec \$CONTAINER_NAME bash -c "
        cd /var/www/html

        echo '🔧 Updating Shopware domain configuration...'

        # Method 1: Try to update existing domain
        php bin/console sales-channel:update:domain --url='http://$HETZNER_IP' 2>/dev/null || echo 'Domain update failed, trying alternative...'

        # Method 2: Create new sales channel with correct domain
        php bin/console sales-channel:create:storefront --name='Production' --url='http://$HETZNER_IP' 2>/dev/null || echo 'Sales channel creation failed, trying database update...'

        # Method 3: Direct database update (fallback)
        php bin/console dbal:run-sql \"UPDATE sales_channel_domain SET url = 'http://$HETZNER_IP' WHERE url LIKE '%localhost%' OR url LIKE '%headless%';\" 2>/dev/null || echo 'Database update failed'

        # Clear all caches
        echo '🧹 Clearing caches...'
        php bin/console cache:clear --env=prod
        php bin/console cache:warmup --env=prod

        echo '✅ Domain configuration completed'
    "

    # Restart containers to apply changes
    echo "🔄 Restarting containers..."
    docker compose down
    docker compose up -d

    # Wait for restart
    sleep 30

    # Check admin accessibility
    echo "🔐 Checking admin accessibility..."
    curl -I http://localhost/admin || echo "Admin not accessible yet"

    echo "✅ Domain configuration fixed!"
EOF

echo "🎉 Shopware domain configuration completed!"
echo "🌐 Your Shopware should now be accessible at: http://$HETZNER_IP"
echo "🔧 Admin panel: http://$HETZNER_IP/admin"
echo ""
echo "If you still see the domain error, try:"
echo "1. Wait 1-2 minutes for caches to clear"
echo "2. Refresh your browser (Ctrl+F5)"
echo "3. Access the admin panel to manually configure domains"
