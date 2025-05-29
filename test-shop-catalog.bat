@echo off
echo ========================================
echo TCG Manager - Shop Catalog Test
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
echo 5. Building storefront assets...
docker-compose exec shopware bin/build-storefront.sh

echo.
echo 6. Checking available cards with products...
docker-compose exec shopware bin/console tcg:test:cards

echo.
echo 7. Testing shop routes...
echo Testing catalog route...
docker-compose exec shopware bin/console router:match /tcg/shop

echo.
echo Testing categories route...
docker-compose exec shopware bin/console router:match /tcg/shop/categories

echo.
echo 8. Checking if we have products with images...
docker-compose exec shopware bin/console tcg:upload-media --dry-run --limit=5

echo.
echo ========================================
echo Shop Catalog Test Complete!
echo.
echo You can now visit:
echo - http://localhost/tcg/shop (Catalog)
echo - http://localhost/tcg/shop/categories (Categories)
echo ========================================
pause
