# TcgManager Plugin - Installationsanleitung

## 🚀 Schnellstart

### 1. Plugin installieren
```bash
# Im Shopware-Root-Verzeichnis
cd /var/www/html  # oder dein Shopware-Pfad

# Plugin aktivieren
bin/console plugin:refresh
bin/console plugin:install --activate TcgManager

# Cache leeren
bin/console cache:clear
```

### 2. Beispieldaten laden (empfohlen)
```bash
# Über Shopware CLI (wenn verfügbar)
bin/console tcg:fixtures:load

# Oder manuell über Admin-Panel:
# Admin > Einstellungen > Erweiterungen > TcgManager > Beispieldaten laden
```

### 3. Frontend testen
1. Neuen Kunden registrieren oder einloggen
2. Zu "Mein Konto" > "Kartensammlungen" navigieren
3. Erste Sammlung wird automatisch erstellt
4. Karten suchen und zur Sammlung hinzufügen

## 🔧 Detaillierte Installation

### Voraussetzungen prüfen
```bash
# PHP Version (mindestens 8.1)
php -v

# Shopware Version (mindestens 6.4)
bin/console --version

# Datenbank-Verbindung testen
bin/console dbal:run-sql "SELECT VERSION()"
```

### Plugin-Struktur überprüfen
```bash
# Plugin-Dateien sollten vorhanden sein:
ls -la custom/plugins/TcgManager/
# Erwartete Ausgabe:
# composer.json
# src/
# README.md
# INSTALLATION.md
```

### Datenbank-Migrationen überprüfen
```bash
# Migrationen anzeigen
bin/console database:migrate --dry-run

# Tabellen überprüfen (nach Installation)
bin/console dbal:run-sql "SHOW TABLES LIKE 'tcg_%'"
# Erwartete Tabellen:
# tcg_card
# tcg_collection  
# tcg_collection_card
# tcg_deck
# tcg_deck_card
```

## 🧪 Installation testen

### 1. Backend-Test
```bash
# Plugin-Status prüfen
bin/console plugin:list | grep TcgManager
# Status sollte "active" sein

# Services prüfen
bin/console debug:container | grep TcgManager
# Sollte alle Services anzeigen
```

### 2. Datenbank-Test
```bash
# Tabellen-Struktur prüfen
bin/console dbal:run-sql "DESCRIBE tcg_card"

# Beispieldaten prüfen (falls geladen)
bin/console dbal:run-sql "SELECT COUNT(*) as card_count FROM tcg_card"
```

### 3. Frontend-Test
1. **Kundenregistrierung testen**:
   - Neuen Kunden registrieren
   - Prüfen ob Standard-Sammlung erstellt wurde

2. **API-Endpunkte testen**:
   ```bash
   # Karten-Suche (als eingeloggter Kunde)
   curl -X GET "http://localhost/api/tcg/cards/search?q=Lightning"
   
   # Sammlungen abrufen (mit Session-Cookie)
   curl -X GET "http://localhost/api/tcg/collections" \
        -H "Cookie: session=YOUR_SESSION_ID"
   ```

3. **Frontend-Navigation**:
   - Einloggen als Kunde
   - Zu `/account/tcg/collections` navigieren
   - Neue Sammlung erstellen
   - Karten suchen und hinzufügen

## 🛠️ Troubleshooting

### Plugin wird nicht erkannt
```bash
# Plugin-Cache leeren
bin/console plugin:refresh
rm -rf var/cache/*

# Autoloader neu generieren
composer dump-autoload
```

### Datenbank-Fehler
```bash
# Migrationen manuell ausführen
bin/console database:migrate --all

# Tabellen manuell erstellen (Notfall)
bin/console dbal:run-sql "$(cat custom/plugins/TcgManager/install.sql)"
```

### Frontend-Fehler
```bash
# Template-Cache leeren
bin/console cache:clear
rm -rf var/cache/*/twig

# Asset-Cache leeren
rm -rf public/bundles/*
bin/console assets:install
```

### Service-Fehler
```bash
# Container neu kompilieren
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod

# Services debuggen
bin/console debug:container TcgManager
```

## 🔍 Logs und Debugging

### Log-Dateien prüfen
```bash
# Shopware Logs
tail -f var/log/dev.log
tail -f var/log/prod.log

# Plugin-spezifische Logs (falls konfiguriert)
tail -f var/log/tcg_manager.log
```

### Debug-Modus aktivieren
```bash
# .env Datei bearbeiten
echo "APP_ENV=dev" >> .env
echo "APP_DEBUG=1" >> .env

# Cache leeren
bin/console cache:clear
```

### SQL-Debugging
```bash
# Doctrine Query Log aktivieren (config/packages/dev/doctrine.yaml)
# doctrine:
#   dbal:
#     logging: true
#     profiling: true
```

## 📊 Performance-Optimierung

### Produktionsumgebung
```bash
# Produktions-Cache erstellen
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod

# Assets optimieren
bin/console assets:install --env=prod

# Opcache aktivieren (php.ini)
# opcache.enable=1
# opcache.memory_consumption=256
```

### Datenbank-Optimierung
```sql
-- Indizes prüfen
SHOW INDEX FROM tcg_card;
SHOW INDEX FROM tcg_collection;

-- Query-Performance analysieren
EXPLAIN SELECT * FROM tcg_card WHERE edition = 'Alpha';
```

## 🔄 Updates

### Plugin aktualisieren
```bash
# Neue Plugin-Version installieren
bin/console plugin:update TcgManager

# Migrationen ausführen
bin/console database:migrate

# Cache leeren
bin/console cache:clear
```

### Daten-Migration
```bash
# Backup vor Update
mysqldump shopware > backup_before_tcg_update.sql

# Nach Update prüfen
bin/console dbal:run-sql "SELECT COUNT(*) FROM tcg_card"
```

## 🆘 Support

### Häufige Probleme

1. **"Plugin not found"**
   - `bin/console plugin:refresh` ausführen
   - Plugin-Pfad prüfen

2. **"Table doesn't exist"**
   - Migrationen manuell ausführen
   - Datenbank-Berechtigungen prüfen

3. **"Service not found"**
   - Container-Cache leeren
   - services.xml Syntax prüfen

4. **"Template not found"**
   - Template-Pfade prüfen
   - Twig-Cache leeren

### Hilfe anfordern
1. **GitHub Issues**: Für Bugs und Feature-Requests
2. **Shopware Community**: Für allgemeine Fragen
3. **Logs sammeln**: Immer relevante Log-Ausgaben mitschicken

### System-Informationen sammeln
```bash
# System-Info für Support-Anfragen
echo "=== System Information ===" > debug_info.txt
echo "Shopware Version: $(bin/console --version)" >> debug_info.txt
echo "PHP Version: $(php -v | head -1)" >> debug_info.txt
echo "Plugin Status: $(bin/console plugin:list | grep TcgManager)" >> debug_info.txt
echo "Database Tables: $(bin/console dbal:run-sql 'SHOW TABLES LIKE "tcg_%"')" >> debug_info.txt
```

---

**Bei Problemen nicht zögern, Hilfe zu suchen! 🤝**
