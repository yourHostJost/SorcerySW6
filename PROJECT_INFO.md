# SorcerySW6 - Projekt-Informationen

## 📋 Projekt-Übersicht
**Projekt:** SorcerySW6 - Shopware 6 Development Environment mit TCG Manager Plugin
**Zweck:** Trading Card Game Manager Plugin für Shopware 6
**Repository:** https://github.com/yourHostJost/SorcerySW6
**Erstellt:** 2024
**Status:** ✅ Plugin vollständig funktionsfähig (Stand: 2024-12-28)

## 🛠️ Technische Spezifikationen

### Software & Frameworks
| Komponente | Version | Zweck |
|------------|---------|-------|
| **Shopware** | 6.6.10.4 | E-Commerce Platform |
| **PHP** | 8.2 | Backend-Sprache |
| **MySQL** | 8.0 | Datenbank |
| **Docker** | Latest | Containerisierung |
| **Dockware** | dev:6.6.10.4 | Shopware Docker Image |
| **Symfony** | 6.x | Framework (Teil von Shopware) |
| **Twig** | 3.x | Template Engine |
| **Bootstrap** | 5.x | Frontend Framework |

### Entwicklungsumgebung
- **Docker Compose** für lokale Entwicklung
- **GitHub Actions** für CI/CD
- **Hetzner Cloud** für Production-Deployment
- **VSCode** als bevorzugte IDE

## 🌐 Umgebungen

### Lokale Entwicklung
- **URL:** http://localhost
- **Admin:** http://localhost/admin
- **Docker Setup:** `docker-compose.yml`
- **Ports:** 80 (HTTP), 443 (HTTPS), 3306 (MySQL), 22 (SSH), 8888 (Adminer), 9999 (Mailcatcher)

### Production (Hetzner Cloud)
- **URL:** http://91.99.27.91
- **Admin:** http://91.99.27.91/admin
- **Server:** Hetzner Cloud VPS
- **Docker Setup:** `docker-compose.production.yml`
- **SSH:** `ssh -i server_key root@91.99.27.91`

## 🗂️ Verzeichnisstruktur
```
SorcerySW6/
├── custom/plugins/TcgManager/          # TCG Manager Plugin
│   ├── src/
│   │   ├── Core/Content/               # Entity Definitionen
│   │   ├── Service/                    # Business Logic Services
│   │   ├── Storefront/Controller/      # Frontend Controller
│   │   ├── Resources/                  # Templates, Config, Migrationen
│   │   └── Migration/                  # Datenbank-Migrationen
│   ├── composer.json                   # Plugin-Konfiguration
│   └── README.md                       # Plugin-Dokumentation
├── docker-compose.yml                  # Development Docker Setup
├── docker-compose.production.yml       # Production Docker Setup
├── .github/workflows/                  # GitHub Actions CI/CD
├── server_key                          # SSH Key für Hetzner Cloud
└── README.md                           # Projekt-Dokumentation
```

## 🔐 Anmeldedaten

### Lokale Entwicklung
- **Shopware Admin:** admin / shopware
- **MySQL:** shopware / shopware (Root: root / root)
- **SSH Container:** root / root

### Production (Hetzner Cloud)
- **SSH:** Schlüssel-basiert (`server_key`)
- **Shopware Admin:** [Wird bei Deployment gesetzt]
- **MySQL:** [Container-intern]

### GitHub
- **Repository:** https://github.com/yourHostJost/SorcerySW6
- **Branch:** staging (Hauptentwicklung)
- **Actions:** Automatisches Deployment bei Push

## 🚀 Deployment-Prozess
1. **Lokale Entwicklung** → Code-Änderungen
2. **Git Commit & Push** → GitHub Repository
3. **GitHub Actions** → Automatisches Deployment
4. **Hetzner Cloud** → Production-Update

## 📦 Plugin-Architektur

### TCG Manager Plugin ✅ FUNKTIONSFÄHIG
- **Namespace:** TcgManager
- **Typ:** shopware-platform-plugin
- **Entities:** 5 Haupttabellen (Card, Collection, CollectionCard, Deck, DeckCard)
- **Services:** CollectionService, DeckService, CardService, ShopIntegrationService
- **Frontend:** Account-Integration mit Sidebar-Navigation
- **Status:** Collection-CRUD vollständig funktionsfähig, API-Authentifizierung repariert
- **Bekannte Probleme:** Deployment-Script repariert (2024-12-28)

## 🔧 Wichtige Befehle

### Docker Management
```bash
# Development starten
docker-compose up -d

# Production starten
docker-compose -f docker-compose.production.yml up -d

# Container stoppen
docker-compose down

# Logs anzeigen
docker-compose logs -f shopware
```

### Plugin Management
```bash
# Plugin refreshen
docker-compose exec shopware bin/console plugin:refresh

# Plugin installieren
docker-compose exec shopware bin/console plugin:install TcgManager --activate

# Cache leeren
docker-compose exec shopware bin/console cache:clear
```

### SSH Zugriff
```bash
# Hetzner Cloud Server
ssh -i server_key root@91.99.27.91

# Lokaler Container
docker-compose exec shopware bash
```

## 📊 Performance-Optimierungen
- **HTTP Cache** aktiviert
- **Komprimierung** eingeschaltet
- **Cache Warming** implementiert
- **Optimierte Datenbankindizes**

## 🔍 Monitoring & Debugging
- **Error Logs:** `docker-compose logs shopware`
- **PHP Logs:** Container `/var/log/`
- **Browser DevTools:** Frontend-Debugging
- **Adminer:** http://localhost:8888 (Datenbank-Management)

## 📝 Notizen
- **Wichtig:** Immer `docker-compose.yml` für Development verwenden (hat Volume-Mapping)
- **SSH Key:** `server_key` für Hetzner Cloud Zugriff
- **Branches:** `staging` für Entwicklung, `main` für Production-Releases
- **Plugin-Pfad:** `/custom/plugins/TcgManager/` (gemountet in Development)
