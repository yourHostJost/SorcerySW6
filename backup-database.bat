@echo off
echo ========================================
echo Shopware Database Backup
echo ========================================
echo.

set BACKUP_DIR=database-backups
set TIMESTAMP=%date:~-4,4%-%date:~-10,2%-%date:~-7,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_FILE=%BACKUP_DIR%\shopware_backup_%TIMESTAMP%.sql

echo Creating backup directory...
if not exist %BACKUP_DIR% mkdir %BACKUP_DIR%

echo.
echo Creating database backup...
echo Backup file: %BACKUP_FILE%

docker-compose exec -T mysql mysqldump -u root -proot shopware > %BACKUP_FILE%

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Database backup created successfully!
    echo File: %BACKUP_FILE%
    
    echo.
    echo Backup size:
    for %%A in (%BACKUP_FILE%) do echo %%~zA bytes
    
    echo.
    echo Recent backups:
    dir %BACKUP_DIR%\*.sql /O-D /B | head -5
) else (
    echo.
    echo ❌ Database backup failed!
)

echo.
echo ========================================
pause
