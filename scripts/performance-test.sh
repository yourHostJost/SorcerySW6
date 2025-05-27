#!/bin/bash

# Performance testing script for Shopware 6 on Hetzner Cloud
# Usage: ./scripts/performance-test.sh [HETZNER_IP]

HETZNER_IP=${1:-"your-hetzner-ip"}
BASE_URL="http://$HETZNER_IP"

echo "🚀 Starting performance tests for Shopware 6..."
echo "🌐 Testing URL: $BASE_URL"

# Test 1: Basic connectivity
echo "📡 Test 1: Basic connectivity..."
curl -o /dev/null -s -w "Connect: %{time_connect}s, Total: %{time_total}s, HTTP: %{http_code}\n" $BASE_URL

# Test 2: Homepage load time
echo "🏠 Test 2: Homepage performance..."
curl -o /dev/null -s -w "DNS: %{time_namelookup}s, Connect: %{time_connect}s, Transfer: %{time_starttransfer}s, Total: %{time_total}s\n" $BASE_URL

# Test 3: Admin panel
echo "🔧 Test 3: Admin panel performance..."
curl -o /dev/null -s -w "Admin Total: %{time_total}s, HTTP: %{http_code}\n" $BASE_URL/admin

# Test 4: Multiple concurrent requests
echo "⚡ Test 4: Concurrent load test (10 requests)..."
for i in {1..10}; do
    curl -o /dev/null -s -w "Request $i: %{time_total}s\n" $BASE_URL &
done
wait

# Test 5: Database performance (Adminer)
echo "🗄️ Test 5: Database interface..."
curl -o /dev/null -s -w "Adminer: %{time_total}s, HTTP: %{http_code}\n" $BASE_URL/adminer.php

echo "✅ Performance tests completed!"
echo "💡 For detailed performance analysis, consider using:"
echo "   - Apache Bench: ab -n 100 -c 10 $BASE_URL"
echo "   - Siege: siege -c 10 -t 30s $BASE_URL"
echo "   - GTmetrix or PageSpeed Insights for the frontend"
