# TcgManager - Trading Card Game Manager Plugin für Shopware 6

Ein umfassendes Plugin für Trading Card Game Sammler und Spieler, das eine vollständige Lösung für Kartenorganisation, Deck-Management und Shop-Integration bietet.

## 🎯 Features

### ✅ Bereits implementiert (Phase 1)

#### 🗃️ Kartensammlung-Organizer
- **Persönliche Sammlungen**: Kunden können mehrere Kartensammlungen erstellen und verwalten
- **Detaillierte Kartenverwaltung**: Jede Karte kann mit folgenden Eigenschaften gespeichert werden:
  - Titel, Edition, Threshold-Kosten, Manakosten
  - Seltenheit, Kartentyp, Beschreibung
  - Zustand (Mint, Near Mint, Played, etc.)
  - Sprache und Foil-Typ
  - Anzahl pro Sammlung
- **Such- und Filterfunktionen**: Umfassende Suchoptionen nach allen Karteneigenschaften
- **Automatische Standard-Sammlung**: Neue Kunden erhalten automatisch eine Standard-Sammlung

#### 🃏 Deck-Management
- **Deck-Erstellung**: Vollständige Deck-Verwaltung mit Main Deck und Sideboard
- **Deck-Eigenschaften**: Format, Archetyp, Farben, Beschreibung
- **Sammlungsabgleich**: Automatischer Vergleich zwischen Deck und persönlicher Sammlung
- **Fehlende Karten identifizieren**: Übersichtliche Anzeige welche Karten noch benötigt werden

#### 🛒 Shop-Integration (Grundlagen)
- **Lagerbestand-Abgleich**: Karten sind mit Shop-Produkten verknüpft
- **API-Endpunkte**: Vorbereitet für Warenkorb-Integration

#### 🗄️ Datenbank-Struktur
- **5 Hauptentitäten**: Card, Collection, CollectionCard, Deck, DeckCard
- **Vollständige Migrationen**: Automatische Datenbank-Erstellung bei Plugin-Installation
- **Optimierte Indizes**: Performance-optimierte Datenbankstruktur

#### 🎨 Frontend-Integration
- **Kundenbereich-Erweiterung**: Nahtlose Integration in Shopware Account-Bereich
- **Responsive Design**: Mobile-optimierte Benutzeroberfläche
- **AJAX-API**: Moderne JavaScript-Integration für dynamische Inhalte

### 🚧 Geplante Features (Nächste Phasen)

#### Phase 2: Erweiterte Shop-Integration
- **Ein-Klick Warenkorb**: Fehlende Karten direkt in Warenkorb legen
- **Bulk-Operationen**: Alle fehlenden Karten eines Decks auf einmal hinzufügen
- **Preisberechnung**: Automatische Kostenberechnung für fehlende Karten
- **Verfügbarkeitsprüfung**: Echtzeitprüfung des Lagerbestands

#### Phase 3: Community-Features
- **Deck-Feed**: Öffentliche Deck-Vorschläge von anderen Nutzern
- **Externe Quellen**: Integration von Deck-Datenbanken (MTGGoldfish, EDHRec, etc.)
- **Bewertungssystem**: Likes und Kommentare für Decks
- **Deck-Import/Export**: Standard-Formate unterstützen

#### Phase 4: Erweiterte Features
- **Wunschlisten**: Karten für später merken
- **Preisalerts**: Benachrichtigungen bei Preisänderungen
- **Statistiken**: Sammlungswert, Deck-Kosten, etc.
- **Mobile App**: Native App-Integration

## 🛠️ Installation

### Voraussetzungen
- Shopware 6.4.0 oder höher
- PHP 8.1 oder höher
- MySQL 8.0 oder höher

### Installation
1. Plugin in den `custom/plugins/` Ordner kopieren
2. Plugin über Admin-Panel oder CLI installieren:
   ```bash
   bin/console plugin:refresh
   bin/console plugin:install --activate TcgManager
   ```
3. Datenbank-Migrationen werden automatisch ausgeführt

### Dummy-Daten laden (optional)
```bash
# Über CLI (wenn implementiert)
bin/console tcg:fixtures:load

# Oder über Admin-Panel
# Einstellungen > Erweiterungen > TcgManager > Beispieldaten laden
```

## 📊 Datenbank-Schema

### Haupttabellen
- `tcg_card`: Kartenstammdaten
- `tcg_collection`: Kundensammlungen  
- `tcg_collection_card`: Karten in Sammlungen (mit Zustand, Anzahl, etc.)
- `tcg_deck`: Deck-Definitionen
- `tcg_deck_card`: Karten in Decks (Main/Sideboard)

### Beispiel-Kartendaten
Das Plugin enthält Beispieldaten für verschiedene Trading Card Games:
- Magic: The Gathering (Alpha, moderne Sets)
- Verschiedene Seltenheiten und Preisklassen
- Realistische Marktpreise und Lagerbestände

## 🎮 Verwendung

### Für Kunden
1. **Sammlung erstellen**: Im Kundenbereich unter "Kartensammlungen"
2. **Karten hinzufügen**: Über Suchfunktion Karten zur Sammlung hinzufügen
3. **Deck erstellen**: Neues Deck im "Meine Decks" Bereich
4. **Deck vs. Sammlung**: Automatischer Abgleich zeigt fehlende Karten
5. **Shop-Integration**: Fehlende Karten direkt kaufen (in Entwicklung)

### Für Shop-Betreiber
1. **Karten verwalten**: Über Admin-Panel neue Karten hinzufügen
2. **Lagerbestände**: Automatische Synchronisation mit Shop-Produkten
3. **Preise**: Marktpreise für bessere Kundenorientierung

## 🔧 Entwicklung

### Architektur
- **Service-orientiert**: Klare Trennung von Business Logic und Präsentation
- **Repository Pattern**: Standardisierte Datenzugriffe
- **Event-driven**: Hooks für Erweiterungen
- **API-first**: RESTful Endpunkte für alle Funktionen

### Wichtige Services
- `CollectionService`: Sammlungsverwaltung
- `DeckService`: Deck-Management und Vergleiche
- `CardService`: Kartensuch- und Verwaltungsfunktionen
- `ShopIntegrationService`: Warenkorb und Produktintegration

### API-Endpunkte
```
GET    /api/tcg/collections              # Sammlungen auflisten
POST   /api/tcg/collections              # Neue Sammlung erstellen
POST   /api/tcg/collections/{id}/cards   # Karte zu Sammlung hinzufügen
DELETE /api/tcg/collections/{id}/cards/{cardId} # Karte entfernen
GET    /api/tcg/cards/search             # Karten suchen
```

## 🧪 Testing

### Testdaten
Das Plugin enthält umfassende Testdaten:
- 12 verschiedene Beispielkarten
- Verschiedene Editionen (Alpha, moderne Sets)
- Realistische Preise von 0,25€ bis 25.000€
- Unterschiedliche Lagerbestände

### Manuelle Tests
1. Neuen Kunden registrieren → Standard-Sammlung wird erstellt
2. Karten zur Sammlung hinzufügen
3. Deck erstellen und Karten hinzufügen
4. Deck mit Sammlung vergleichen
5. API-Endpunkte testen

## 🚀 Deployment

### Produktionsumgebung
1. Plugin-Dateien auf Server kopieren
2. `bin/console plugin:install --activate TcgManager`
3. Cache leeren: `bin/console cache:clear`
4. Beispieldaten laden (optional)

### Performance-Optimierungen
- Datenbankindizes für häufige Abfragen
- Lazy Loading für Assoziationen
- Caching für Suchergebnisse (geplant)

## 📝 Changelog

### Version 1.0.0 (Aktuell)
- ✅ Grundlegende Kartensammlung-Funktionalität
- ✅ Deck-Management mit Sammlungsabgleich
- ✅ Such- und Filterfunktionen
- ✅ Frontend-Integration im Kundenbereich
- ✅ Automatische Standard-Sammlung für neue Kunden
- ✅ Umfassende Beispieldaten

### Version 1.1.0 (Geplant)
- 🚧 Vollständige Shop-Integration
- 🚧 Ein-Klick Warenkorb-Funktionalität
- 🚧 Bulk-Operationen für fehlende Karten

### Version 1.2.0 (Geplant)
- 🚧 Community-Features und Deck-Feed
- 🚧 Externe Deck-Quellen Integration
- 🚧 Bewertungs- und Kommentarsystem

## 🤝 Beitragen

Das Plugin ist als Basis für ein umfassendes TCG-Management-System konzipiert. Erweiterungen und Verbesserungen sind willkommen!

### Entwicklungsrichtlinien
- PSR-12 Coding Standards
- Shopware 6 Best Practices
- Umfassende Dokumentation
- Test-driven Development

## 📄 Lizenz

MIT License - Siehe LICENSE Datei für Details.

## 🆘 Support

Bei Fragen oder Problemen:
1. GitHub Issues für Bugs und Feature-Requests
2. Shopware Community Forum für allgemeine Fragen
3. Dokumentation in `/docs` Ordner

---

**Entwickelt für Trading Card Game Enthusiasten** 🎮✨
