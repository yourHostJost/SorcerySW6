# Shopware 6 Performance Testing

Dieses Verzeichnis enthält Performance-Tests für die Shopware 6 Installation auf Hetzner Cloud.

## 🎯 Ziel

Performance-Tests für die Shopware 6 Staging-Umgebung auf Hetzner Cloud (91.99.27.91) durchführen und Bottlenecks identifizieren.

## 🛠️ Setup

### Voraussetzungen

1. **k6 installieren** (Load Testing Tool):
   ```bash
   # Windows (mit winget)
   winget install k6
   
   # macOS
   brew install k6
   
   # Linux
   sudo apt-get update
   sudo apt-get install k6
   ```

2. **Optional: jq für JSON-Analyse**:
   ```bash
   # Windows
   winget install jqlang.jq
   
   # macOS
   brew install jq
   
   # Linux
   sudo apt-get install jq
   ```

## 🚀 Tests ausführen

### Automatischer Test-Lauf
```bash
# Alle Performance-Tests ausführen
./performance/run-performance-tests.sh
```

### Manueller Test-Lauf
```bash
# Basis Load Test
k6 run performance/load-test.js

# Mit Ergebnis-Export
k6 run --out json=results.json performance/load-test.js
```

## 📊 Test-Szenarien

Die Tests simulieren folgende Benutzer-Szenarien:

1. **Homepage-Aufrufe** - Startseite laden
2. **Kategorie-Navigation** - Durch Kategorien browsen
3. **Produkt-Ansichten** - Produktdetailseiten aufrufen
4. **Suchfunktion** - Produktsuche verwenden
5. **Warenkorb-Operationen** - Warenkorb-Seite aufrufen

## 📈 Test-Konfiguration

- **Benutzer-Simulation**: 10-20 gleichzeitige Benutzer
- **Test-Dauer**: 16 Minuten total
- **Ziel-Metriken**:
  - 95% der Requests < 2 Sekunden
  - Fehlerrate < 10%
  - Durchschnittliche Antwortzeit < 1 Sekunde

## 📋 Ergebnisse interpretieren

### Wichtige Metriken

- **http_req_duration**: Antwortzeiten
  - `avg`: Durchschnittliche Antwortzeit
  - `p95`: 95% der Requests sind schneller als dieser Wert
  - `max`: Langsamste Antwortzeit

- **http_req_failed**: Fehlerrate
  - Sollte unter 10% bleiben

- **http_reqs**: Request-Rate
  - Anzahl Requests pro Sekunde

### Benchmark-Werte für Shopware 6

- **Gut**: < 500ms durchschnittliche Antwortzeit
- **Akzeptabel**: < 1000ms durchschnittliche Antwortzeit
- **Verbesserung nötig**: > 2000ms durchschnittliche Antwortzeit

## 🔧 Performance-Optimierung

### Shopware-spezifische Optimierungen

1. **Cache aktivieren**:
   ```bash
   # Im Shopware Container
   php bin/console cache:clear --env=prod
   php bin/console cache:warmup --env=prod
   ```

2. **HTTP Cache aktivieren**:
   - Reverse Proxy (Varnish/nginx) konfigurieren
   - Browser-Caching optimieren

3. **Datenbank-Optimierung**:
   - MySQL Query Cache aktivieren
   - Indizes überprüfen

### Server-Optimierungen

1. **PHP-FPM Tuning**:
   - `pm.max_children` erhöhen
   - `pm.start_servers` anpassen

2. **MySQL Tuning**:
   - `innodb_buffer_pool_size` optimieren
   - Query Cache aktivieren

3. **nginx/Apache Optimierung**:
   - Gzip Kompression aktivieren
   - Keep-Alive Verbindungen nutzen

## 📁 Ergebnis-Dateien

Alle Test-Ergebnisse werden in `performance/results/` gespeichert:

- `load-test-TIMESTAMP.json`: Detaillierte Testergebnisse
- `summary-TIMESTAMP.json`: Zusammenfassung der Metriken
- `report-TIMESTAMP.html`: HTML-Report (falls k6-reporter installiert)

## 🎯 Nächste Schritte

1. **Baseline etablieren**: Erste Tests durchführen
2. **Bottlenecks identifizieren**: Langsame Bereiche finden
3. **Optimierungen implementieren**: Performance verbessern
4. **Regression Tests**: Regelmäßige Tests nach Änderungen

## 🔗 Nützliche Links

- [k6 Dokumentation](https://k6.io/docs/)
- [Shopware Performance Guide](https://docs.shopware.com/en/shopware-6-en/hosting/performance)
- [Hetzner Cloud Monitoring](https://console.hetzner.cloud/)
