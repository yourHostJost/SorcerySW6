<?php

// Debug script to test image mapping
$cardName = "Apprentice Wizard";
$edition = "Alpha";

echo "Testing image mapping for: {$cardName} in {$edition}\n";

// Normalize card name
function normalizeCardName(string $cardName): string
{
    // Convert to lowercase and replace spaces with underscores
    $normalized = strtolower($cardName);
    $normalized = str_replace([' ', "'", '"', '/', '\\', ':', '?', '*', '!', '@', '#', '$', '%', '^', '&', '(', ')', '+', '=', '[', ']', '{', '}', '|', ';', ',', '.', '<', '>'], '_', $normalized);
    // Remove multiple underscores
    $normalized = preg_replace('/_+/', '_', $normalized);
    // Remove leading/trailing underscores
    $normalized = trim($normalized, '_');
    
    return $normalized;
}

$normalizedName = normalizeCardName($cardName);
echo "Normalized name: {$normalizedName}\n";

// Test finish codes
$finishCodes = ['b_f', 'b_s', 'd_s'];

foreach ($finishCodes as $finishCode) {
    $imageName = $normalizedName . '_' . $finishCode . '.png';
    $imagePath = "card_images/{$edition}/{$finishCode}/{$imageName}";
    $fullPath = __DIR__ . '/' . $imagePath;
    
    echo "Testing: {$imagePath}\n";
    echo "Full path: {$fullPath}\n";
    echo "Exists: " . (file_exists($fullPath) ? "YES" : "NO") . "\n";
    echo "---\n";
}

// List actual files
echo "\nActual files in Alpha/b_f containing 'apprentice':\n";
$files = glob(__DIR__ . '/card_images/Alpha/b_f/*apprentice*.png');
foreach ($files as $file) {
    echo basename($file) . "\n";
}
