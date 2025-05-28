@echo off
REM Automatische Performance-Optimierung fuer Shopware 6
REM Usage: scripts\auto-performance-optimization.bat

set HETZNER_IP=91.99.27.91
set SSH_KEY=server_key

echo ==========================================
echo Shopware 6 Performance Optimierung
echo ==========================================
echo Server: %HETZNER_IP%
echo Zeit: %date% %time%
echo.

echo 1. Docker Container Status pruefen...
echo -------------------------------------
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker ps"
echo.

echo 2. Shopware Container finden...
echo -------------------------------
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker ps --format '{{.Names}}' | grep shopware"
echo.

echo 3. Cache leeren und neu aufbauen...
echo -----------------------------------
echo Loesche Shopware Cache...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console cache:clear --env=prod'"
echo.

echo Waerme Cache auf...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console cache:warmup --env=prod'"
echo.

echo 4. HTTP-Cache Konfiguration...
echo ------------------------------
echo Aktiviere HTTP-Cache...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console system:config:set core.httpCache.enabled true'"
echo.

echo Setze Cache-Lebensdauer auf 2 Stunden...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console system:config:set core.httpCache.ttl 7200'"
echo.

echo 5. Template-Cache aktivieren...
echo --------------------------------
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console system:config:set core.template.cache true'"
echo.

echo 6. Komprimierung aktivieren...
echo -------------------------------
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console system:config:set core.response.compression true'"
echo.

echo 7. Debug-Modus deaktivieren...
echo -------------------------------
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console system:config:set core.kernel.debug false'"
echo.

echo 8. Session-Optimierung...
echo --------------------------
echo Setze Session-Lebensdauer auf 2 Stunden...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console system:config:set core.session.lifetime 7200'"
echo.

echo 9. Finaler Cache-Aufbau...
echo ---------------------------
echo Loesche Cache final...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console cache:clear --env=prod'"
echo.

echo Waerme Cache final auf...
ssh -i %SSH_KEY% root@%HETZNER_IP% "docker exec $(docker ps --format '{{.Names}}' | grep shopware | head -1) bash -c 'cd /var/www/html && php bin/console cache:warmup --env=prod'"
echo.

echo 10. Performance-Test nach Optimierung...
echo ------------------------------------------
echo Teste Homepage-Performance...
curl -w "Homepage nach Optimierung: %%{time_total}s" -o nul -s http://%HETZNER_IP%
echo.
curl -w "Admin nach Optimierung: %%{time_total}s" -o nul -s http://%HETZNER_IP%/admin
echo.

echo ==========================================
echo Performance-Optimierung abgeschlossen!
echo ==========================================
echo.
echo Angewendete Optimierungen:
echo - HTTP-Cache aktiviert (2 Stunden TTL)
echo - Template-Cache aktiviert
echo - Komprimierung aktiviert
echo - Debug-Modus deaktiviert
echo - Session-Lebensdauer optimiert
echo - Cache aufgewaermt
echo.
echo Teste jetzt deine Website:
echo Frontend: http://%HETZNER_IP%
echo Admin: http://%HETZNER_IP%/admin
echo.
echo Erwartete Verbesserung:
echo Homepage: von 0.52s auf unter 0.25s
echo Admin: von 0.08s auf unter 0.05s
echo ==========================================

pause
