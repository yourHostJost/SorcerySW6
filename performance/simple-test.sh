#!/bin/bash

# Simple Performance Test for Shopware 6
# Usage: ./performance/simple-test.sh

TARGET_URL="http://91.99.27.91"
RESULTS_FILE="performance/results/simple-test-$(date +%Y%m%d_%H%M%S).txt"

echo "🚀 Simple Shopware 6 Performance Test"
echo "======================================"
echo "Target: $TARGET_URL"
echo "Results: $RESULTS_FILE"
echo ""

# Create results directory
mkdir -p performance/results

# Start results file
echo "Shopware 6 Performance Test Results" > "$RESULTS_FILE"
echo "Date: $(date)" >> "$RESULTS_FILE"
echo "Target: $TARGET_URL" >> "$RESULTS_FILE"
echo "======================================" >> "$RESULTS_FILE"
echo "" >> "$RESULTS_FILE"

# Test 1: Homepage
echo "📊 Testing Homepage..."
HOMEPAGE_RESULT=$(curl -o /dev/null -s -w "Time: %{time_total}s, Size: %{size_download} bytes, HTTP: %{http_code}" "$TARGET_URL")
echo "Homepage: $HOMEPAGE_RESULT"
echo "Homepage: $HOMEPAGE_RESULT" >> "$RESULTS_FILE"

# Test 2: Admin Login Page
echo "🔐 Testing Admin Login..."
ADMIN_RESULT=$(curl -o /dev/null -s -w "Time: %{time_total}s, Size: %{size_download} bytes, HTTP: %{http_code}" "$TARGET_URL/admin")
echo "Admin: $ADMIN_RESULT"
echo "Admin: $ADMIN_RESULT" >> "$RESULTS_FILE"

# Test 3: API Health Check
echo "🏥 Testing API Health..."
API_RESULT=$(curl -o /dev/null -s -w "Time: %{time_total}s, Size: %{size_download} bytes, HTTP: %{http_code}" "$TARGET_URL/api/_info/health-check")
echo "API Health: $API_RESULT"
echo "API Health: $API_RESULT" >> "$RESULTS_FILE"

# Test 4: Multiple requests to measure consistency
echo "🔄 Testing consistency (5 requests)..."
echo "" >> "$RESULTS_FILE"
echo "Consistency Test (5 requests):" >> "$RESULTS_FILE"

TOTAL_TIME=0
for i in {1..5}; do
    TIME=$(curl -o /dev/null -s -w "%{time_total}" "$TARGET_URL")
    echo "Request $i: ${TIME}s"
    echo "Request $i: ${TIME}s" >> "$RESULTS_FILE"
    TOTAL_TIME=$(echo "$TOTAL_TIME + $TIME" | bc -l 2>/dev/null || echo "$TOTAL_TIME")
done

# Calculate average (if bc is available)
if command -v bc &> /dev/null; then
    AVG_TIME=$(echo "scale=3; $TOTAL_TIME / 5" | bc)
    echo "Average: ${AVG_TIME}s"
    echo "Average: ${AVG_TIME}s" >> "$RESULTS_FILE"
fi

echo "" >> "$RESULTS_FILE"
echo "Test completed at: $(date)" >> "$RESULTS_FILE"

echo ""
echo "✅ Performance test completed!"
echo "📊 Results saved to: $RESULTS_FILE"
echo ""
echo "📈 Quick Analysis:"
echo "=================="
cat "$RESULTS_FILE"
echo ""
echo "💡 Performance Guidelines:"
echo "=========================="
echo "🟢 Excellent: < 0.5s"
echo "🟡 Good: 0.5s - 1.0s"
echo "🟠 Acceptable: 1.0s - 2.0s"
echo "🔴 Needs optimization: > 2.0s"
