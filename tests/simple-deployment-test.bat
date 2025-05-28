@echo off
REM Einfacher Deployment Test
REM Usage: tests\simple-deployment-test.bat

set TIMESTAMP=%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

echo ==========================================
echo Einfacher GitHub Actions Deployment Test
echo ==========================================
echo Zeit: %date% %time%
echo.

echo 1. Erstelle kleine Aenderung in README...
echo ------------------------------------------
echo. >> README.md
echo ^<!-- Deployment Test: %date% %time% --^> >> README.md
echo [OK] README aktualisiert
echo.

echo 2. Git Commit und Push...
echo -------------------------
git add README.md
git commit -m "Deployment Test - %TIMESTAMP%"
git push origin staging
echo [OK] Aenderungen gepusht
echo.

echo 3. Oeffne GitHub Actions zum Monitoring...
echo ------------------------------------------
start https://github.com/yourHostJost/SorcerySW6/actions
echo.

echo 4. Warte 90 Sekunden fuer Deployment...
echo ----------------------------------------
echo Deployment laeuft... Bitte warten...
timeout /t 90 /nobreak
echo.

echo 5. Teste Website nach Deployment...
echo ------------------------------------
curl -w "Homepage: %%{time_total}s" -o nul -s http://91.99.27.91
echo.

echo ==========================================
echo Test abgeschlossen!
echo ==========================================
echo.
echo Pruefe GitHub Actions Status im Browser:
echo https://github.com/yourHostJost/SorcerySW6/actions
echo.
echo Website testen:
echo http://91.99.27.91
echo ==========================================

pause
