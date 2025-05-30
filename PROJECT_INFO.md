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
- **Kartendaten:** 636 echte Sorcery-Karten importiert und funktionsfähig
- **Drag & Drop:** Vollständig implementiert mit Demo-Modus
- **Demo-URL:** `http://localhost/tcg/demo/drag-drop` (funktionsfähig)
- **Shop-URLs:** `http://localhost/tcg/shop` (Katalog), `http://localhost/tcg/shop/categories` (Kategorien)
- **Bekannte Probleme:** 4K-Display Layout-Optimierung ungelöst (2024-12-29)

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

## 🔧 Technische Erkenntnisse & Best Practices

### Shopware 6 AJAX-Entwicklung ⚡ KRITISCH
- **AJAX-Routes:** `defaults: ['_routeScope' => ['storefront'], 'XmlHttpRequest' => true]` ZWINGEND erforderlich
- **Route-Scopes:** Korrekte Scope-Definition verhindert 403-Fehler bei AJAX-Requests
- **CSRF-Schutz:** `defaults={"csrf_protected"=false}` in Route-Annotation für API-Endpunkte
- **Session-Handling:** `credentials: 'same-origin'` in fetch-Requests für Session-basierte Authentifizierung
- **API-Authentifizierung:** `if (!$context->getCustomer())` statt `denyAccessUnlessLoggedIn()` für Customer-Checks
- **JavaScript-Integration:** `{% block base_body_script %}` mit `{{ parent() }}` für korrekte Script-Einbindung
- **FormData vs JSON:** FormData für CSRF-Token-kompatible Formulare, JSON für reine API-Calls
- **AJAX-Headers:** `X-Requested-With: XMLHttpRequest` für Shopware-AJAX-Erkennung

### Sorcery TCG API-Integration 🃏
- **API-Endpoint:** https://api.sorcerytcg.com/cards
- **Kartendaten:** 636 Karten, 3 Editionen, 4 Seltenheiten
- **Datenbank-Schema:** 15 neue Felder für Sorcery-Mechaniken
- **Performance:** Import in <2 Minuten, optimierte Indizes
- **Zufälligkeit:** `mt_srand()` + `shuffle()` für echte Zufälligkeit

### Frontend-Template-Debugging 🔍
- **Problem:** AJAX-Requests ohne korrekte Route-Parameter → 403 Forbidden
- **Lösung:** Shopware-Dokumentation als Referenz für systematische Fehlereingrenzung
- **Template-Trennung:** Server-seitige und AJAX-Container separieren
- **Error-Handling:** Detaillierte Console-Logs für besseres Debugging

### Shopware 6 Plugin SCSS ⚡ KRITISCH
- **Korrekte Implementierung:** `src/Resources/app/storefront/src/scss/base.scss`
- **Build-Befehl:** `bin/build-storefront.sh` (NICHT `theme:compile`)
- **Unterschied zu Themes:** Plugins haben andere Build-Pipeline als Themes
- **Automatische Erkennung:** Shopware findet base.scss automatisch
- **Debug-Test:** `body { border-top: 5px solid red !important; }` zum Testen
- **Hot-Reload:** `bin/build-storefront.sh --hot` für Live-Entwicklung
- **Wichtig:** Theme-Dokumentation gilt NICHT für Plugins!

## 📝 Notizen
- **Wichtig:** Immer `docker-compose.yml` für Development verwenden (hat Volume-Mapping)
- **SSH Key:** `server_key` für Hetzner Cloud Zugriff
- **Branches:** `staging` für Entwicklung, `main` für Production-Releases
- **Plugin-Pfad:** `/custom/plugins/TcgManager/` (gemountet in Development)
- **AJAX-Debugging:** Immer Shopware-Dokumentation zuerst konsultieren!

## 📋 Arbeitsprotokoll

### 2024-12-28 - TCG Manager Plugin Reparatur
- **Problem:** Collection-CRUD, API-Authentifizierung und Deployment-Pipeline defekt
- **Lösung:** Systematische Reparatur aller Komponenten
- **Ergebnis:** Plugin vollständig funktionsfähig, 636 echte Sorcery-Karten importiert
- **Status:** ✅ Abgeschlossen

### 2024-12-28 - Frontend-Template-Debugging
- **Problem:** Server-seitige und AJAX-Container überschrieben sich
- **Lösung:** Getrennte Container für server-seitigen und AJAX-Content
- **Ergebnis:** Frontend-AJAX vollständig funktionsfähig
- **Status:** ✅ Abgeschlossen

### 2024-12-28 - Drag & Drop Interface Implementation
- **Problem:** Collections-Management benötigt intuitive Benutzeroberfläche
- **Lösung:** Vollständiges Drag & Drop Interface mit echten Sorcery-Karten
- **Features:** Card Browser, Drop Zone, Collection Management, Demo-Modus
- **Status:** ✅ Abgeschlossen

### 2024-12-29 - VOLLSTÄNDIGER PRODUKTKATALOG ✅ ABGESCHLOSSEN
- **Bildanalyse abgeschlossen:** 2.221 PNG-Bilder (1,8 GB) analysiert
- **Bildformat:** 380x531 Pixel, RGBA, ~810 KB pro Bild
- **Struktur:** 3 Editionen (Alpha, Beta, Arthurian Legends) × 10 Finish-Varianten
- **Namenskonvention:** `{kartenname}_{finish_code}.png` (konsistent)
- **Finish-Codes:** b_f/b_s (Base), bt_f/bt_s (Borderless), p_f/p_s (Promo), etc.
- **ProductSyncService:** Automatische Shopware-Produkterstellung implementiert
- **MediaUploadService:** Bildupload-Pipeline mit Multi-Finish-Support
- **Produktkatalog:** Vollständiger Shop mit Filter, Kategorien, Detailansichten
- **Navigation:** TCG-Shop in Hauptnavigation integriert
- **BULK-IMPORT ERFOLGREICH:** 596 Produkte aus 605 Karten (98,5% Erfolgsrate)
- **Performance:** 7min 18s für 596 Karten (~0,72s pro Karte inkl. Bildupload)
- **Editionen:** Alpha (405), Beta (9), Arthurian Legends (222) - alle importiert
- **Media-Pipeline:** Automatischer Upload aller verfügbaren Finish-Varianten
- **Shop-Integration:** Vollständiger Katalog mit Filtern, Kategorien, Detailansichten
- **Ergebnis:** PRODUCTION-READY TCG-Shop mit 596 Produkten + tausenden Bildern

### 2024-12-29 - VOLLSTÄNDIGE INTEGRATION ABGESCHLOSSEN ✅
- **Datenbank-Backup:** 8,5 MB SQL-Dump mit allen 636 Produkten erstellt
- **Git-Commit:** Vollständiger Code ohne Bilder committed und gepusht
- **Collections Card Browser:** Kartenbilder erfolgreich implementiert
- **API-Erweiterung:** Media-URLs in tcg/api/cards und tcg/random-cards
- **Template-Updates:** Beide Card-HTML-Funktionen mit Bildern erweitert
- **Deployment-Scripts:** Bereit für Bildupload zum Hetzner-Server
- **Authentisches Kartenformat:** 2:3 Verhältnis wie echte TCG-Karten implementiert
- **Foil-Effekte:** Elegante Animationen nur auf Produkt-Detailseite bei Foil-Auswahl
- **Produktnummer-Fix:** 9 problematische Karten repariert, alle 636 Karten haben Produkte
- **Fehlende Bilder behoben:** 32 Karten ohne Bilder identifiziert und repariert
- **Katalog-Paginierung:** Limit von 24 auf 100 erhöht, "Alle anzeigen" Option hinzugefügt
- **Status:** VOLLSTÄNDIG FUNKTIONSFÄHIGER TCG-SHOP + COLLECTIONS-SYSTEM MIT FOIL-EFFEKTEN

### 2024-12-29 - 4K-Display Layout-Optimierung ⚠️ UNGELÖST
- **Problem:** Layout auf großem 4K-TV nicht optimal
  - Card Browser nach rechts versetzt (nicht bündig mit Überschrift)
  - Bootstrap-Grid zeigt 2 Bereiche untereinander statt nebeneinander
  - Sidebar-Navigation zu breit für große Displays

#### Durchgeführte Lösungsversuche:

1. **Inline-CSS im Template**
   - **Ansatz:** CSS direkt in collection-detail.html.twig
   - **Ergebnis:** ❌ Funktioniert nicht, wird überschrieben

2. **SCSS nach Theme-Dokumentation**
   - **Ansatz:** SCSS-Datei + `theme:compile`
   - **Problem:** ❌ Falsche Dokumentation - gilt nur für Themes, nicht Plugins

3. **Korrekte Plugin-SCSS-Implementierung**
   - **Pfad:** `src/Resources/app/storefront/src/scss/base.scss`
   - **Build:** `bin/build-storefront.sh` (nicht `theme:compile`)
   - **Ergebnis:** ✅ SCSS wird geladen (Debug-Rahmen sichtbar)

4. **CSS-Override-Strategien**
   - **Versuch 1:** Einfache Bootstrap-Grid-Overrides
   - **Versuch 2:** ULTRA-STRONG Selektoren mit !important
   - **Versuch 3:** MEGA-STRONG mit allen möglichen Parent-Selektoren
   - **Versuch 4:** Minimalistischer Ansatz
   - **Versuch 5:** Gezielte Card Browser Container-Fixes
   - **Ergebnis:** ❌ Alle Versuche ohne Erfolg

#### Aktuelle Diagnose:
- ✅ **SCSS-System funktioniert** - Roter Debug-Rahmen am Body sichtbar
- ❌ **Card Browser versetzt** - Debug-Rahmen nur innerhalb Card Browser
- ❌ **Bootstrap-Grid defekt** - col-lg-6 Bereiche stapeln sich vertikal
- ✅ **HTML-Struktur korrekt** - row + col-lg-6 vorhanden
- 🔍 **Vermutung:** Übergeordneter Container oder Shopware-spezifische CSS-Klassen überschreiben Bootstrap

#### Technische Details der Lösungsversuche:

**SCSS-Implementierung (erfolgreich):**
- **Pfad:** `custom/plugins/TcgManager/src/Resources/app/storefront/src/scss/base.scss`
- **Build-Befehl:** `docker-compose exec shopware bin/build-storefront.sh`
- **Test:** `body { border-top: 5px solid red !important; }` → ✅ Funktioniert
- **Ergebnis:** SCSS wird korrekt geladen und angewendet

**CSS-Override-Versuche (alle erfolglos):**
1. **Einfache Selektoren:** `.drag-drop-container .row { display: flex !important; }`
2. **Starke Selektoren:** `.account-content .drag-drop-container .row`
3. **ULTRA-STRONG:** Alle möglichen Parent-Container-Kombinationen
4. **MEGA-STRONG:** Zusätzlich mit Position- und Transform-Properties
5. **Minimalistisch:** Nur die nötigsten CSS-Regeln
6. **Gezielt:** Spezifische Card Browser Container-Fixes

**Beobachtungen:**
- Debug-Rahmen erscheinen NUR innerhalb des Card Browsers
- Überschrift, Text und Back-Button sind korrekt ausgerichtet
- HTML-Struktur ist korrekt: `<div class="row">` + `<div class="col-lg-6">`
- Alle CSS-Regeln mit !important werden ignoriert

**Vermutung:** Shopware-spezifische CSS-Klassen oder JavaScript überschreiben Bootstrap-Grid

#### Dateien-Status nach Debugging:
- **base.scss:** Sauber mit TODO-Kommentaren, funktionsfähige Styles behalten
- **collection-detail.html.twig:** Debug-CSS entfernt, nur Kommentar-Verweis auf SCSS
- **PROJECT_INFO.md:** Vollständige Dokumentation aller Schritte

#### Nächste Schritte (für neuen Chat):
1. **Browser DevTools:** Detaillierte CSS-Analyse der betroffenen Elemente
2. **Shopware CSS:** Identifikation der überschreibenden CSS-Klassen
3. **JavaScript-Lösung:** Layout-Korrektur via JavaScript als Alternative
4. **Template-Umstrukturierung:** HTML-Layout komplett überarbeiten
5. **Shopware-Community:** Problem in Shopware-Foren recherchieren

#### Wichtige Erkenntnisse für Fortsetzung:
- ✅ SCSS-System funktioniert perfekt (Plugin-spezifische Implementierung)
- ❌ CSS-Overrides funktionieren nicht (Shopware überschreibt Bootstrap)
- 🔍 Problem ist spezifisch für Card Browser (andere Elemente korrekt)
- 📋 HTML-Struktur ist korrekt (Bootstrap-Grid-Syntax stimmt)
- 🎯 User bevorzugt einfache HTML-Lösungen über komplexe CSS-Overrides

#### Relevante Dateien für Layout-Problem:
```
custom/plugins/TcgManager/src/Resources/
├── app/storefront/src/scss/base.scss          # Plugin-SCSS (funktioniert)
└── views/storefront/page/account/
    └── collection-detail.html.twig            # Template mit Layout-Problem
```

#### Wichtige Code-Stellen:
- **HTML-Grid:** Zeile 97-195 in collection-detail.html.twig
- **SCSS-TODO:** Zeile 8-25 in base.scss (auskommentierte Fixes)
- **Demo-Route:** `/tcg/demo/drag-drop` für Testing
- **Build-Befehl:** `docker-compose exec shopware bin/build-storefront.sh`

#### Browser-Test-URL für neuen Chat:
- **Demo-Seite:** `http://localhost/tcg/demo/drag-drop`
- **Erwartung:** 2 Spalten nebeneinander, Card Browser bündig
- **Aktuell:** 2 Spalten untereinander, Card Browser nach rechts versetzt

- **Status:** 🔧 **UNGELÖST** - Vollständig dokumentiert, bereit für neue Lösungsansätze
