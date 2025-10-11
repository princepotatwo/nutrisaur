<?php
// Test syntax of DatabaseAPI.php
$file = 'public/api/DatabaseAPI.php';
$content = file_get_contents($file);

// Check for syntax errors
$syntax_check = shell_exec("php -l $file 2>&1");
echo "Syntax check result:\n";
echo $syntax_check;

// Check if the file ends properly
echo "\nFile ends with: " . substr($content, -50);
?>
