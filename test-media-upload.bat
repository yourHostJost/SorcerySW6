@echo off
echo ========================================
echo TCG Manager - Media Upload Test
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
echo 5. Testing media upload (dry run)...
docker-compose exec shopware bin/console tcg:upload-media --dry-run --limit=5

echo.
echo 6. Running actual media upload (limited to 3 cards)...
docker-compose exec shopware bin/console tcg:upload-media --limit=3

echo.
echo 7. Checking uploaded media...
docker-compose exec shopware bin/console debug:container media.repository

echo.
echo ========================================
echo Media Upload Test Complete!
echo ========================================
pause
