<?php
echo "=== Version Check ===\n";
echo "Current Git Commit: " . shell_exec('git rev-parse HEAD') . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";

// Check if the DatabaseAPI has the null pointer fix
$databaseApiContent = file_get_contents(__DIR__ . '/api/DatabaseAPI.php');
if (strpos($databaseApiContent, 'isDatabaseAvailable()') !== false) {
    echo "✅ DatabaseAPI has null pointer fix\n";
} else {
    echo "❌ DatabaseAPI missing null pointer fix\n";
}

echo "=== End Version Check ===\n";
?>
