#!/bin/bash

# Deploy SorcerySW6 to Hetzner Cloud
# Usage: ./scripts/deploy-to-hetzner.sh

set -e

# Configuration
HETZNER_IP="91.99.27.91"
HETZNER_USER="root"
PROJECT_PATH="/var/www/SorcerySW6"
GITHUB_REPO="https://github.com/yourHostJost/SorcerySW6.git"

echo "🚀 Deploying SorcerySW6 to Hetzner Cloud..."

# Check if server is reachable
echo "📡 Checking server connectivity..."
ssh -o ConnectTimeout=10 $HETZNER_USER@$HETZNER_IP "echo 'Server is reachable'" || {
    echo "❌ Cannot connect to Hetzner server"
    exit 1
}

# Deploy to server
echo "📦 Deploying application..."
ssh $HETZNER_USER@$HETZNER_IP << EOF
    set -e

    # Create project directory if it doesn't exist
    if [ ! -d "$PROJECT_PATH" ]; then
        echo "📁 Cloning repository..."
        git clone $GITHUB_REPO $PROJECT_PATH
    fi

    cd $PROJECT_PATH

    # Pull latest changes
    echo "🔄 Pulling latest changes..."
    git pull origin main

    # Stop existing containers
    echo "🛑 Stopping existing containers..."
    docker compose down || true

    # Pull latest images
    echo "📥 Pulling latest Docker images..."
    docker compose pull

    # Start containers
    echo "🚀 Starting containers..."
    docker compose up -d

    # Wait for services to be ready
    echo "⏳ Waiting for services to start..."
    sleep 30

    # Health check
    echo "🏥 Performing health check..."
    curl -f http://localhost || {
        echo "❌ Health check failed"
        docker compose logs
        exit 1
    }

    echo "✅ Deployment completed successfully!"
    echo "🌐 Your Shopware 6 staging environment is available at: http://$HETZNER_IP"
EOF

echo "🎉 Deployment to Hetzner Cloud completed!"
echo "🌐 Access your staging environment at: http://$HETZNER_IP"
echo "🔧 Admin panel: http://$HETZNER_IP/admin"
