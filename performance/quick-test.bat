@echo off
REM Quick Performance Test for Shopware 6 (Windows)
REM Usage: performance\quick-test.bat

set TARGET_URL=http://91.99.27.91
set RESULTS_DIR=performance\results
set TIMESTAMP=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

echo 🚀 Quick Shopware 6 Performance Test
echo ====================================
echo Target: %TARGET_URL%
echo Timestamp: %TIMESTAMP%
echo.

REM Create results directory
if not exist "%RESULTS_DIR%" mkdir "%RESULTS_DIR%"

echo 📊 Testing Homepage...
curl -o nul -s -w "Homepage - Time: %%{time_total}s, HTTP: %%{http_code}, Size: %%{size_download} bytes" %TARGET_URL%
echo.

echo 🔐 Testing Admin Login...
curl -o nul -s -w "Admin - Time: %%{time_total}s, HTTP: %%{http_code}, Size: %%{size_download} bytes" %TARGET_URL%/admin
echo.

echo 🏥 Testing API Health...
curl -o nul -s -w "API - Time: %%{time_total}s, HTTP: %%{http_code}, Size: %%{size_download} bytes" %TARGET_URL%/api/_info/health-check
echo.

echo 🔄 Testing consistency (3 requests)...
for /L %%i in (1,1,3) do (
    curl -o nul -s -w "Request %%i - Time: %%%%{time_total}s" %TARGET_URL%
    echo.
)

echo.
echo ✅ Performance test completed!
echo.
echo 💡 Performance Guidelines:
echo ==========================
echo 🟢 Excellent: ^< 0.5s
echo 🟡 Good: 0.5s - 1.0s  
echo 🟠 Acceptable: 1.0s - 2.0s
echo 🔴 Needs optimization: ^> 2.0s
echo.
echo 🌐 Test your optimized site:
echo Frontend: %TARGET_URL%
echo Admin: %TARGET_URL%/admin

pause
