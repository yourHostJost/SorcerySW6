#!/bin/bash

# Setup script for Hetzner Cloud server
# Run this on your Hetzner server after initial creation

set -e

echo "🔧 Setting up Hetzner Cloud server for Shopware 6..."

# Update system
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install Docker
echo "🐳 Installing Docker..."
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
rm get-docker.sh

# Install Docker Compose
echo "🔧 Installing Docker Compose..."
apt install -y docker-compose-plugin

# Start and enable Docker
systemctl start docker
systemctl enable docker

# Install Git
echo "📚 Installing Git..."
apt install -y git curl

# Create project directory
echo "📁 Creating project directory..."
mkdir -p /var/www
cd /var/www

# Clone repository (you'll need to do this manually or set up SSH keys)
echo "📥 Ready to clone repository..."
echo "Run: git clone https://github.com/yourHostJost/SorcerySW6.git"

# Install UFW firewall
echo "🔒 Setting up firewall..."
apt install -y ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Create swap file (recommended for small servers)
echo "💾 Creating swap file..."
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab

echo "✅ Hetzner server setup completed!"
echo "🔑 Next steps:"
echo "1. Clone your repository: git clone https://github.com/yourHostJost/SorcerySW6.git /var/www/SorcerySW6"
echo "2. Run: cd /var/www/SorcerySW6 && docker compose -f docker-compose.production.yml up -d"
