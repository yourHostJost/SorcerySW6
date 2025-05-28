@echo off
REM Baseline Performance Test for Shopware 6 (After Cache Activation)
REM Usage: performance\baseline-test.bat

set TARGET_URL=http://91.99.27.91
set RESULTS_DIR=performance\results
set TIMESTAMP=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

echo 🚀 Shopware 6 Baseline Performance Test
echo =======================================
echo Target: %TARGET_URL%
echo Timestamp: %TIMESTAMP%
echo Cache Status: ACTIVATED
echo.

REM Create results directory
if not exist "%RESULTS_DIR%" mkdir "%RESULTS_DIR%"

echo 📊 Testing Homepage (with cache)...
curl -o nul -s -w "Homepage - Time: %%{time_total}s, HTTP: %%{http_code}, Size: %%{size_download} bytes" %TARGET_URL%
echo.

echo 🔐 Testing Admin Login...
curl -o nul -s -w "Admin - Time: %%{time_total}s, HTTP: %%{http_code}, Size: %%{size_download} bytes" %TARGET_URL%/admin
echo.

echo 🛒 Testing Shop Navigation...
curl -o nul -s -w "Navigation - Time: %%{time_total}s, HTTP: %%{http_code}" %TARGET_URL%/navigation
echo.

echo 🔍 Testing Search...
curl -o nul -s -w "Search - Time: %%{time_total}s, HTTP: %%{http_code}" %TARGET_URL%/search?search=test
echo.

echo 🔄 Cache Performance Test (5 requests)...
echo Testing cache effectiveness...
for /L %%i in (1,1,5) do (
    curl -o nul -s -w "Request %%i - Time: %%%%{time_total}s" %TARGET_URL%
    echo.
)

echo.
echo ✅ Baseline performance test completed!
echo.
echo 📈 Performance Analysis:
echo ========================
echo 🟢 Excellent: ^< 0.3s (cached content)
echo 🟡 Good: 0.3s - 0.8s  
echo 🟠 Acceptable: 0.8s - 1.5s
echo 🔴 Needs optimization: ^> 1.5s
echo.
echo 💡 Next Steps:
echo ==============
echo 1. If times are consistently low (^<0.5s): Cache is working well
echo 2. If first request is slow, others fast: Cache warming needed
echo 3. If all requests are slow: Additional optimization needed
echo.
echo 🌐 Test your optimized site:
echo Frontend: %TARGET_URL%
echo Admin: %TARGET_URL%/admin

pause
