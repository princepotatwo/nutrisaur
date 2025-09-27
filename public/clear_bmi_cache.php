<?php
/**
 * Clear BMI classification cache and force recalculation
 */

// Clear any PHP opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ PHP opcache cleared\n";
}

// Clear any file-based cache
$cacheFiles = [
    __DIR__ . '/../who_growth_standards.php',
    __DIR__ . '/../public/api/who_growth_standards.php',
    __DIR__ . '/../public/WHO_DECISION_TREE_COMPLETE.php'
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        // Touch the file to update its modification time
        touch($file);
        echo "✅ Updated modification time for: " . basename($file) . "\n";
    }
}

echo "🔄 BMI classification cache cleared. The system should now use the new percentile-based logic.\n";
echo "Please refresh your browser to see the updated classifications.\n";
?>
