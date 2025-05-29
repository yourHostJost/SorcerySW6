@echo off
echo ========================================
echo TCG Manager - Bulk Import ALL Cards
echo ========================================
echo.

echo ⚠️  WARNING: This will import ALL 636 cards as products!
echo This process may take 30-60 minutes to complete.
echo.

set /p CONFIRM="Are you sure you want to proceed? (y/N): "

if /i not "%CONFIRM%"=="y" (
    echo.
    echo Bulk import cancelled.
    pause
    exit /b 0
)

echo.
echo 🚀 Starting bulk import process...

echo.
echo 1. Creating database backup...
call backup-database.bat

echo.
echo 2. Refreshing plugin...
docker-compose exec shopware bin/console plugin:refresh

echo.
echo 3. Installing/updating plugin...
docker-compose exec shopware bin/console plugin:install TcgManager --activate

echo.
echo 4. Running database migrations...
docker-compose exec shopware bin/console database:migrate --all TcgManager

echo.
echo 5. Clearing cache...
docker-compose exec shopware bin/console cache:clear

echo.
echo 6. Testing bulk sync (dry run)...
docker-compose exec shopware bin/console tcg:bulk-sync-products --dry-run --batch-size=10

echo.
echo 7. Starting REAL bulk sync using existing command...
echo This will take a while - please be patient!
echo.
echo Phase 1: Sync ALL cards without limit (this creates products + uploads images)
docker-compose exec shopware bin/console tcg:sync-products --limit=1000

echo.
echo 8. Verifying results...
docker-compose exec shopware bin/console tcg:test:cards

echo.
echo 9. Building storefront assets...
docker-compose exec shopware bin/console cache:clear
docker-compose exec shopware bin/build-storefront.sh

echo.
echo ========================================
echo Bulk Import Complete!
echo.
echo You can now visit:
echo - http://localhost/tcg/shop (Full Catalog)
echo - http://localhost/tcg/shop/categories (Categories)
echo.
echo Next steps:
echo 1. Test the shop functionality
echo 2. Create a Git commit (images excluded)
echo 3. Deploy images to server separately
echo ========================================
pause
