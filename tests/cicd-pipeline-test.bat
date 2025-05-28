@echo off
REM CI/CD Pipeline Test für GitHub Actions
REM Usage: tests\cicd-pipeline-test.bat

set TIMESTAMP=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set TEST_FILE=test-deployment-%TIMESTAMP%.txt
set HETZNER_IP=91.99.27.91

echo ==========================================
echo CI/CD Pipeline Test
echo ==========================================
echo Zeitstempel: %TIMESTAMP%
echo Test-Datei: %TEST_FILE%
echo Ziel-Server: %HETZNER_IP%
echo.

echo 1. Erstelle Test-Datei mit unwirksamen Aenderungen...
echo ------------------------------------------------------
echo # Deployment Test > %TEST_FILE%
echo Zeitstempel: %TIMESTAMP% >> %TEST_FILE%
echo Test-ID: CICD-TEST-%TIMESTAMP% >> %TEST_FILE%
echo Status: Lokale Aenderung erstellt >> %TEST_FILE%
echo. >> %TEST_FILE%
echo Dieser Test prueft ob GitHub Actions korrekt funktioniert. >> %TEST_FILE%
echo Die Datei sollte nach dem Deployment auf dem Server erscheinen. >> %TEST_FILE%
echo [OK] Test-Datei erstellt: %TEST_FILE%
echo.

echo 2. Aktualisiere README mit Test-Info...
echo ---------------------------------------
echo. >> README.md
echo ^<!-- CI/CD Test: %TIMESTAMP% --^> >> README.md
echo [OK] README aktualisiert
echo.

echo 3. Git Status pruefen...
echo ------------------------
git status
echo.

echo 4. Aenderungen zu Git hinzufuegen...
echo ------------------------------------
git add %TEST_FILE%
git add README.md
echo [OK] Dateien zu Git hinzugefuegt
echo.

echo 5. Git Commit erstellen...
echo ---------------------------
git commit -m "CI/CD Pipeline Test - %TIMESTAMP%"
if %errorlevel% equ 0 (
    echo [OK] Git Commit erfolgreich
) else (
    echo [FEHLER] Git Commit fehlgeschlagen
    pause
    exit /b 1
)
echo.

echo 6. Push zu GitHub (staging branch)...
echo -------------------------------------
git push origin staging
if %errorlevel% equ 0 (
    echo [OK] Push zu GitHub erfolgreich
) else (
    echo [FEHLER] Push zu GitHub fehlgeschlagen
    pause
    exit /b 1
)
echo.

echo 7. Warte auf GitHub Actions Deployment...
echo ------------------------------------------
echo GitHub Actions wird jetzt automatisch ausgeloest...
echo Warte 60 Sekunden fuer Deployment...
timeout /t 60 /nobreak
echo.

echo 8. Teste ob Deployment erfolgreich war...
echo ------------------------------------------
echo Pruefe ob Test-Datei auf Server existiert...
ssh -i server_key root@%HETZNER_IP% "test -f /var/www/SorcerySW6/%TEST_FILE% && echo 'DEPLOYMENT ERFOLGREICH: Test-Datei gefunden' || echo 'DEPLOYMENT FEHLGESCHLAGEN: Test-Datei nicht gefunden'"
echo.

echo 9. Zeige Datei-Inhalt vom Server...
echo ------------------------------------
ssh -i server_key root@%HETZNER_IP% "cat /var/www/SorcerySW6/%TEST_FILE% 2>/dev/null || echo 'Datei nicht lesbar oder nicht vorhanden'"
echo.

echo 10. Pruefe GitHub Actions Status...
echo ------------------------------------
echo Oeffne GitHub Actions Seite zum manuellen Check...
start https://github.com/yourHostJost/SorcerySW6/actions
echo.

echo 11. Teste Website-Funktionalitaet...
echo -------------------------------------
echo Pruefe ob Website noch funktioniert...
curl -w "Homepage: %%{time_total}s - Status: %%{http_code}" -o nul -s http://%HETZNER_IP%
echo.
curl -w "Admin: %%{time_total}s - Status: %%{http_code}" -o nul -s http://%HETZNER_IP%/admin
echo.

echo 12. Aufraeumen (Test-Datei loeschen)...
echo ----------------------------------------
echo Loesche Test-Datei lokal...
del %TEST_FILE% 2>nul
echo Loesche Test-Datei auf Server...
ssh -i server_key root@%HETZNER_IP% "rm -f /var/www/SorcerySW6/%TEST_FILE%"
echo [OK] Test-Dateien geloescht
echo.

echo ==========================================
echo CI/CD Pipeline Test abgeschlossen
echo ==========================================
echo.
echo Test-Ergebnisse:
echo ================
echo 1. Lokale Aenderungen: [OK]
echo 2. Git Commit: [OK]
echo 3. GitHub Push: [OK]
echo 4. GitHub Actions: [Siehe Browser]
echo 5. Server Deployment: [Siehe oben]
echo 6. Website Funktionalitaet: [Siehe oben]
echo.
echo Naechste Schritte:
echo ==================
echo 1. Pruefe GitHub Actions Status im Browser
echo 2. Wenn erfolgreich: CI/CD Pipeline funktioniert
echo 3. Wenn fehlgeschlagen: Logs in GitHub Actions pruefen
echo.
echo GitHub Actions: https://github.com/yourHostJost/SorcerySW6/actions
echo Website: http://%HETZNER_IP%
echo ==========================================

pause
