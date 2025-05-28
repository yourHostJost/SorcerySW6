#!/bin/bash

# Shopware 6 Performance Testing Suite
# Usage: ./performance/run-performance-tests.sh

set -e

echo "🚀 Shopware 6 Performance Testing Suite"
echo "========================================"

# Configuration
TARGET_URL="http://91.99.27.91"
RESULTS_DIR="performance/results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Create results directory
mkdir -p "$RESULTS_DIR"

echo "📍 Target: $TARGET_URL"
echo "📊 Results will be saved to: $RESULTS_DIR"

# Check if k6 is installed
if ! command -v k6 &> /dev/null; then
    echo "❌ k6 is not installed. Installing k6..."
    
    # Install k6 based on OS
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        sudo gpg -k
        sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
        echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
        sudo apt-get update
        sudo apt-get install k6
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        brew install k6
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
        # Windows
        echo "Please install k6 manually from: https://k6.io/docs/getting-started/installation/"
        echo "Or use: winget install k6"
        exit 1
    fi
fi

echo "✅ k6 is available"

# Verify target is accessible
echo "🔍 Checking if target is accessible..."
if curl -f -s "$TARGET_URL" > /dev/null; then
    echo "✅ Target is accessible"
else
    echo "❌ Target is not accessible: $TARGET_URL"
    exit 1
fi

# Run performance tests
echo "🏃 Running performance tests..."

# Basic load test
echo "📈 Running basic load test..."
k6 run \
    --out json="$RESULTS_DIR/load-test-$TIMESTAMP.json" \
    --summary-export="$RESULTS_DIR/summary-$TIMESTAMP.json" \
    performance/load-test.js

# Generate HTML report if possible
if command -v k6-reporter &> /dev/null; then
    echo "📊 Generating HTML report..."
    k6-reporter "$RESULTS_DIR/load-test-$TIMESTAMP.json" \
        --output "$RESULTS_DIR/report-$TIMESTAMP.html"
fi

echo ""
echo "🎉 Performance tests completed!"
echo "📊 Results saved to: $RESULTS_DIR"
echo ""
echo "📈 Quick Analysis:"
echo "=================="

# Basic analysis of results
if [ -f "$RESULTS_DIR/summary-$TIMESTAMP.json" ]; then
    echo "📄 Summary report: $RESULTS_DIR/summary-$TIMESTAMP.json"
    
    # Extract key metrics if jq is available
    if command -v jq &> /dev/null; then
        echo ""
        echo "🔍 Key Metrics:"
        echo "---------------"
        
        # HTTP request duration
        avg_duration=$(jq -r '.metrics.http_req_duration.avg' "$RESULTS_DIR/summary-$TIMESTAMP.json" 2>/dev/null || echo "N/A")
        p95_duration=$(jq -r '.metrics.http_req_duration.p95' "$RESULTS_DIR/summary-$TIMESTAMP.json" 2>/dev/null || echo "N/A")
        
        # HTTP request rate
        req_rate=$(jq -r '.metrics.http_reqs.rate' "$RESULTS_DIR/summary-$TIMESTAMP.json" 2>/dev/null || echo "N/A")
        
        # Error rate
        error_rate=$(jq -r '.metrics.http_req_failed.rate' "$RESULTS_DIR/summary-$TIMESTAMP.json" 2>/dev/null || echo "N/A")
        
        echo "⏱️  Average Response Time: ${avg_duration}ms"
        echo "📊 95th Percentile: ${p95_duration}ms"
        echo "🚀 Request Rate: ${req_rate} req/s"
        echo "❌ Error Rate: ${error_rate}%"
    fi
fi

echo ""
echo "💡 Next Steps:"
echo "=============="
echo "1. Review the detailed results in: $RESULTS_DIR"
echo "2. Check Shopware performance in admin panel"
echo "3. Monitor server resources during peak load"
echo "4. Optimize based on bottlenecks found"

echo ""
echo "🔗 Useful Links:"
echo "================"
echo "📊 Shopware Admin: $TARGET_URL/admin"
echo "🌐 Frontend: $TARGET_URL"
