# SorcerySW6 - Shopware 6 Development Environment

A Docker-based Shopware 6 development environment using Dockware.

## 🌐 Production Environment
- **Live Site:** http://91.99.27.91
- **Admin Panel:** http://91.99.27.91/admin
- **Auto-Deployment:** GitHub Actions → Hetzner Cloud
- **Last Update:** $(date)

## 🚀 Quick Start

### Prerequisites
- Docker Desktop (Windows/Mac) or Docker Engine (Linux)
- Docker Compose

### Installation

1. Clone this repository:
```bash
git clone <your-repo-url>
cd SorcerySW6
```

2. Start the development environment:
```bash
docker-compose up -d
```

3. Wait for the installation to complete (this may take several minutes on first run)

4. Access your Shopware 6 installation:
   - **Frontend**: http://localhost
   - **Admin Panel**: http://localhost/admin
   - **Default Admin Credentials**:
     - Username: `admin`
     - Password: `shopware`

## 📋 Configuration

### Environment Details
- **Shopware Version**: 6.4.20.0
- **PHP Version**: 8.1
- **Database**: MySQL
- **Image**: dockware/dev:latest

### Port Mappings
- `80` - Web server (HTTP)
- `3306` - MySQL database
- `22` - SSH access
- `8888` - Adminer (Database management)
- `9999` - Mailcatcher

### Database Access
- **Host**: localhost
- **Port**: 3306
- **Database**: shopware
- **Username**: shopware
- **Password**: shopware
- **Root Password**: root

## 🛠️ Development

### Accessing the Container
```bash
docker-compose exec shopware bash
```

### Stopping the Environment
```bash
docker-compose down
```

### Viewing Logs
```bash
docker-compose logs -f shopware
```

## 📁 Project Structure

```
SorcerySW6/
├── docker-compose.yml    # Docker configuration
├── shopware/            # Shopware installation (created after first run)
├── .gitignore          # Git ignore rules
└── README.md           # This file
```

## 🔧 Troubleshooting

### Common Issues
1. **Port conflicts**: Make sure ports 80, 3306, etc. are not in use by other applications
2. **Docker not running**: Ensure Docker Desktop is running
3. **Slow installation**: First-time setup downloads and installs Shopware, which can take 10-15 minutes

### Useful Commands
```bash
# Restart containers
docker-compose restart

# Rebuild containers
docker-compose up --build

# Remove everything and start fresh
docker-compose down -v
docker-compose up -d
```

## 📚 Resources

- [Shopware 6 Documentation](https://docs.shopware.com/)
- [Dockware Documentation](https://dockware.io/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
 
<!-- Deployment Test: 28.05.2025 19:31:08,38 --> 
