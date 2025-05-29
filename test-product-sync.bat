@echo off
echo ========================================
echo TCG Manager - Product Sync Test
echo ========================================
echo.

echo 1. Refreshing plugin...
docker-compose exec shopware bin/console plugin:refresh

echo.
echo 2. Installing/updating plugin...
docker-compose exec shopware bin/console plugin:install TcgManager --activate

echo.
echo 3. Running database migrations...
docker-compose exec shopware bin/console database:migrate --all TcgManager

echo.
echo 4. Clearing cache...
docker-compose exec shopware bin/console cache:clear

echo.
echo 5. Testing product sync (dry run)...
docker-compose exec shopware bin/console tcg:sync-products --dry-run --limit=5

echo.
echo 6. Running actual product sync (limited to 5 cards)...
docker-compose exec shopware bin/console tcg:sync-products --limit=5

echo.
echo ========================================
echo Product Sync Test Complete!
echo ========================================
pause
