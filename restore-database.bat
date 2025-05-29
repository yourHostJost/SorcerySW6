@echo off
echo ========================================
echo Shopware Database Restore
echo ========================================
echo.

set BACKUP_DIR=database-backups

echo Available backups:
echo.
dir %BACKUP_DIR%\*.sql /O-D /B

echo.
set /p BACKUP_FILE="Enter backup filename (without path): "

if not exist "%BACKUP_DIR%\%BACKUP_FILE%" (
    echo.
    echo ❌ Backup file not found: %BACKUP_DIR%\%BACKUP_FILE%
    pause
    exit /b 1
)

echo.
echo ⚠️  WARNING: This will overwrite the current database!
set /p CONFIRM="Are you sure you want to restore from %BACKUP_FILE%? (y/N): "

if /i not "%CONFIRM%"=="y" (
    echo.
    echo Restore cancelled.
    pause
    exit /b 0
)

echo.
echo Restoring database from %BACKUP_FILE%...

docker-compose exec -T mysql mysql -u root -proot shopware < "%BACKUP_DIR%\%BACKUP_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Database restored successfully!
    echo.
    echo Clearing Shopware cache...
    docker-compose exec shopware bin/console cache:clear
) else (
    echo.
    echo ❌ Database restore failed!
)

echo.
echo ========================================
pause
