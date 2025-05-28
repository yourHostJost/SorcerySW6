// Shopware 6 Performance Load Test
// Usage: k6 run performance/load-test.js

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
export let errorRate = new Rate('errors');

// Test configuration
export let options = {
  stages: [
    { duration: '2m', target: 10 }, // Ramp up to 10 users
    { duration: '5m', target: 10 }, // Stay at 10 users
    { duration: '2m', target: 20 }, // Ramp up to 20 users
    { duration: '5m', target: 20 }, // Stay at 20 users
    { duration: '2m', target: 0 },  // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests must complete below 2s
    http_req_failed: ['rate<0.1'],     // Error rate must be below 10%
    errors: ['rate<0.1'],              // Custom error rate must be below 10%
  },
};

const BASE_URL = 'http://91.99.27.91';

export default function () {
  // Test scenarios
  let scenarios = [
    testHomepage,
    testCategoryPage,
    testProductPage,
    testSearchFunction,
    testCartOperations,
  ];

  // Randomly select a scenario
  let scenario = scenarios[Math.floor(Math.random() * scenarios.length)];
  scenario();

  sleep(1);
}

function testHomepage() {
  let response = http.get(`${BASE_URL}/`);
  
  let success = check(response, {
    'Homepage status is 200': (r) => r.status === 200,
    'Homepage loads in <2s': (r) => r.timings.duration < 2000,
    'Homepage contains Shopware': (r) => r.body.includes('Shopware') || r.body.includes('shop'),
  });

  errorRate.add(!success);
}

function testCategoryPage() {
  // First get homepage to find category links
  let homepage = http.get(`${BASE_URL}/`);
  
  if (homepage.status === 200) {
    let response = http.get(`${BASE_URL}/navigation`);
    
    let success = check(response, {
      'Category page status is 200': (r) => r.status === 200,
      'Category page loads in <3s': (r) => r.timings.duration < 3000,
    });

    errorRate.add(!success);
  }
}

function testProductPage() {
  // Test a generic product URL pattern
  let response = http.get(`${BASE_URL}/detail`);
  
  let success = check(response, {
    'Product page responds': (r) => r.status === 200 || r.status === 404, // 404 is acceptable if no products
    'Product page loads in <3s': (r) => r.timings.duration < 3000,
  });

  errorRate.add(!success);
}

function testSearchFunction() {
  let response = http.get(`${BASE_URL}/search?search=test`);
  
  let success = check(response, {
    'Search responds': (r) => r.status === 200,
    'Search loads in <2s': (r) => r.timings.duration < 2000,
  });

  errorRate.add(!success);
}

function testCartOperations() {
  // Test cart page
  let response = http.get(`${BASE_URL}/checkout/cart`);
  
  let success = check(response, {
    'Cart page responds': (r) => r.status === 200,
    'Cart page loads in <2s': (r) => r.timings.duration < 2000,
  });

  errorRate.add(!success);
}

// Setup function - runs once before the test
export function setup() {
  console.log('🚀 Starting Shopware 6 Performance Test');
  console.log(`📍 Target: ${BASE_URL}`);
  
  // Verify the site is accessible
  let response = http.get(BASE_URL);
  if (response.status !== 200) {
    throw new Error(`Site not accessible: ${response.status}`);
  }
  
  console.log('✅ Site is accessible, starting load test...');
}

// Teardown function - runs once after the test
export function teardown() {
  console.log('🏁 Performance test completed');
}
