@echo off
REM Einfacher Performance Test fuer Shopware 6 (Deutsche Windows-Konsole)
REM Usage: performance\simple-german-test.bat

set TARGET_URL=http://91.99.27.91

echo.
echo ==========================================
echo    Shopware 6 Performance Test
echo ==========================================
echo Ziel: %TARGET_URL%
echo Zeit: %date% %time%
echo.

echo 1. Homepage Test...
echo -------------------
curl -o nul -s -w "Zeit: %%{time_total} Sekunden - Status: %%{http_code}\n" %TARGET_URL%
echo.

echo 2. Admin Test...
echo ----------------
curl -o nul -s -w "Zeit: %%{time_total} Sekunden - Status: %%{http_code}\n" %TARGET_URL%/admin
echo.

echo 3. Cache-Effektivitaet (5 Anfragen)...
echo ---------------------------------------
curl -o nul -s -w "Anfrage 1: %%{time_total}s\n" %TARGET_URL%
curl -o nul -s -w "Anfrage 2: %%{time_total}s\n" %TARGET_URL%
curl -o nul -s -w "Anfrage 3: %%{time_total}s\n" %TARGET_URL%
curl -o nul -s -w "Anfrage 4: %%{time_total}s\n" %TARGET_URL%
curl -o nul -s -w "Anfrage 5: %%{time_total}s\n" %TARGET_URL%
echo.

echo ==========================================
echo Performance Bewertung:
echo ==========================================
echo Sehr gut:     unter 0.5 Sekunden
echo Gut:          0.5 - 1.0 Sekunden
echo Akzeptabel:   1.0 - 2.0 Sekunden
echo Optimierung:  über 2.0 Sekunden
echo.
echo Cache funktioniert gut wenn:
echo - Erste Anfrage langsamer (Cache-Miss)
echo - Folgende Anfragen schneller (Cache-Hit)
echo ==========================================
echo.

pause
