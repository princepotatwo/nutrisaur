<?php
require_once __DIR__ . "/api/DatabaseAPI.php";

try {
    $db = new DatabaseAPI();
    $pdo = $db->getPDO();
    
    echo "<h1>Database Table Structure Check</h1>";
    
    // Check users table structure
    echo "<h2>Users Table Structure:</h2>";
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($userColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check user_preferences table structure
    echo "<h2>User Preferences Table Structure:</h2>";
    $stmt = $pdo->prepare("DESCRIBE user_preferences");
    $stmt->execute();
    $prefColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($prefColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check sample data from users table
    echo "<h2>Sample Users Data:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($users[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in the table.</p>";
    }
    
    // Check sample data from user_preferences table
    echo "<h2>Sample User Preferences Data:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM user_preferences LIMIT 5");
    $stmt->execute();
    $prefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($prefs)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($prefs[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        foreach ($prefs as $pref) {
            echo "<tr>";
            foreach ($pref as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No user preferences found in the table.</p>";
    }
    
    // Check primary keys
    echo "<h2>Primary Keys:</h2>";
    $stmt = $pdo->prepare("SHOW KEYS FROM users WHERE Key_name = 'PRIMARY'");
    $stmt->execute();
    $userKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Users table primary key:</strong></p>";
    foreach ($userKeys as $key) {
        echo "<p>- Column: " . htmlspecialchars($key['Column_name']) . "</p>";
    }
    
    $stmt = $pdo->prepare("SHOW KEYS FROM user_preferences WHERE Key_name = 'PRIMARY'");
    $stmt->execute();
    $prefKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>User Preferences table primary key:</strong></p>";
    foreach ($prefKeys as $key) {
        echo "<p>- Column: " . htmlspecialchars($key['Column_name']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #2c5530; }
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
