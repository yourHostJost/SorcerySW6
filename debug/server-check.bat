@echo off
REM Server Status Check for Shopware 6
REM Usage: debug\server-check.bat

set TARGET_IP=91.99.27.91

echo 🔍 Shopware Server Diagnostic
echo =============================
echo Target: %TARGET_IP%
echo.

echo 📡 1. Ping Test...
ping -n 3 %TARGET_IP%
echo.

echo 🌐 2. HTTP Homepage Test...
curl -I http://%TARGET_IP% 2>nul
if %errorlevel% equ 0 (
    echo ✅ Homepage accessible
) else (
    echo ❌ Homepage not accessible
)
echo.

echo 🔐 3. Admin URL Test...
curl -I http://%TARGET_IP%/admin 2>nul
if %errorlevel% equ 0 (
    echo ✅ Admin URL accessible
) else (
    echo ❌ Admin URL not accessible
)
echo.

echo 🔧 4. Alternative Admin URLs...
echo Testing /backend...
curl -I http://%TARGET_IP%/backend 2>nul
if %errorlevel% equ 0 (
    echo ✅ /backend accessible
) else (
    echo ❌ /backend not accessible
)

echo Testing /admin/login...
curl -I http://%TARGET_IP%/admin/login 2>nul
if %errorlevel% equ 0 (
    echo ✅ /admin/login accessible
) else (
    echo ❌ /admin/login not accessible
)
echo.

echo 🏥 5. API Health Check...
curl -s http://%TARGET_IP%/api/_info/health-check 2>nul
if %errorlevel% equ 0 (
    echo ✅ API accessible
) else (
    echo ❌ API not accessible
)
echo.

echo 📊 6. Detailed Homepage Response...
curl -v http://%TARGET_IP% 2>&1 | findstr "HTTP\|Location\|Server"
echo.

echo 🔍 Diagnostic completed!
echo.
echo 💡 Troubleshooting Tips:
echo ========================
echo - If homepage works but admin doesn't: Admin route issue
echo - If nothing works: Server/Docker problem
echo - If redirects occur: Check for HTTPS redirects
echo.

pause
