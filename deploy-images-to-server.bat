@echo off
echo ========================================
echo Deploy TCG Images to Hetzner Server
echo ========================================
echo.

set SERVER_IP=91.99.27.91
set SERVER_USER=root
set SERVER_PATH=/var/www/html/card_images
set LOCAL_PATH=card_images
set SSH_KEY=server_key

echo Checking local images...
if not exist %LOCAL_PATH% (
    echo ❌ Local card_images directory not found!
    echo Please ensure %LOCAL_PATH% exists with TCG card images.
    pause
    exit /b 1
)

echo.
echo Local images found:
for /f %%i in ('dir %LOCAL_PATH% /s /b *.png ^| find /c ".png"') do echo Total PNG files: %%i

echo.
echo Server: %SERVER_IP%
echo Target path: %SERVER_PATH%
echo.

set /p CONFIRM="Deploy images to server? This may take a while... (y/N): "

if /i not "%CONFIRM%"=="y" (
    echo.
    echo Deployment cancelled.
    pause
    exit /b 0
)

echo.
echo 🚀 Starting deployment...

echo.
echo 1. Creating target directory on server...
ssh -i %SSH_KEY% %SERVER_USER%@%SERVER_IP% "mkdir -p %SERVER_PATH%"

echo.
echo 2. Syncing images to server (this may take several minutes)...
echo Using rsync for efficient transfer...

REM Use rsync for efficient transfer (requires rsync on Windows or WSL)
rsync -avz --progress -e "ssh -i %SSH_KEY%" %LOCAL_PATH%/ %SERVER_USER%@%SERVER_IP%:%SERVER_PATH%/

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Images deployed successfully!
    
    echo.
    echo 3. Verifying deployment...
    ssh -i %SSH_KEY% %SERVER_USER%@%SERVER_IP% "find %SERVER_PATH% -name '*.png' | wc -l"
    
    echo.
    echo 4. Setting correct permissions...
    ssh -i %SSH_KEY% %SERVER_USER%@%SERVER_IP% "chown -R www-data:www-data %SERVER_PATH%"
    ssh -i %SSH_KEY% %SERVER_USER%@%SERVER_IP% "chmod -R 755 %SERVER_PATH%"
    
    echo.
    echo 🎉 Deployment completed successfully!
    echo Images are now available on the server.
) else (
    echo.
    echo ❌ Deployment failed!
    echo.
    echo Alternative: Manual SCP transfer
    echo scp -i %SSH_KEY% -r %LOCAL_PATH% %SERVER_USER%@%SERVER_IP%:/var/www/html/
)

echo.
echo ========================================
pause
