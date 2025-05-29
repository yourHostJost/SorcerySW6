@echo off
echo ========================================
echo TCG Manager - Import ALL Cards (Simple)
echo ========================================
echo.

echo Using existing tcg:sync-products command...
echo This will import ALL 636 cards as Shopware products with images.
echo.

set /p CONFIRM="Proceed with full import? (y/N): "

if /i not "%CONFIRM%"=="y" (
    echo Import cancelled.
    pause
    exit /b 0
)

echo.
echo 🚀 Starting import...

echo.
echo 1. Creating database backup first...
call backup-database.bat

echo.
echo 2. Clearing cache...
docker-compose exec shopware bin/console cache:clear

echo.
echo 3. Testing with dry-run (first 10 cards)...
docker-compose exec shopware bin/console tcg:sync-products --dry-run --limit=10

echo.
echo 4. Starting REAL import - ALL CARDS...
echo This will take about 7-10 minutes. Please be patient!
docker-compose exec shopware bin/console tcg:sync-products --limit=700

echo.
echo 5. Verifying results...
docker-compose exec shopware bin/console tcg:test:cards

echo.
echo 6. Final cache clear and asset build...
docker-compose exec shopware bin/console cache:clear
docker-compose exec shopware bin/build-storefront.sh

echo.
echo ========================================
echo ✅ Import Complete!
echo.
echo Visit: http://localhost/tcg/shop
echo ========================================
pause
