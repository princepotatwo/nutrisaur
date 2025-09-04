<?php
require_once __DIR__ . "/api/DatabaseAPI.php";

try {
    $db = new DatabaseAPI();
    $pdo = $db->getPDO();
    
    echo "<h1>Debug Critical Alerts</h1>";
    
    // Check the raw critical alerts query
    echo "<h2>Raw Critical Alerts Query Results:</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            up.*, u.username, u.email,
            CASE 
                WHEN up.risk_score >= 80 THEN 'Severe Risk'
                WHEN up.risk_score >= 50 THEN 'High Risk'
                ELSE 'Moderate Risk'
            END as alert_level
        FROM user_preferences up
        LEFT JOIN users u ON up.user_email = u.email
        WHERE up.risk_score >= 30
        ORDER BY up.risk_score DESC, up.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User Email</th><th>Username</th><th>Risk Score</th><th>Alert Level</th><th>Barangay</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['user_email'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['username'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['risk_score'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['alert_level'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['barangay'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check users table
    echo "<h2>Users Table Sample:</h2>";
    $stmt = $pdo->prepare("SELECT email, username, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Email</th><th>Username</th><th>Created At</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['email'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($user['username'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($user['created_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check user_preferences table
    echo "<h2>User Preferences Sample (High Risk):</h2>";
    $stmt = $pdo->prepare("SELECT user_email, barangay, risk_score, created_at FROM user_preferences WHERE risk_score >= 30 ORDER BY risk_score DESC LIMIT 10");
    $stmt->execute();
    $prefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User Email</th><th>Barangay</th><th>Risk Score</th><th>Created At</th></tr>";
    foreach ($prefs as $pref) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pref['user_email'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($pref['barangay'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($pref['risk_score'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($pref['created_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for mismatched emails
    echo "<h2>Mismatched Emails Analysis:</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            up.user_email as pref_email,
            u.email as user_email,
            u.username,
            up.risk_score
        FROM user_preferences up
        LEFT JOIN users u ON up.user_email = u.email
        WHERE up.risk_score >= 30
        ORDER BY up.risk_score DESC
        LIMIT 10
    ");
    $stmt->execute();
    $mismatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Pref Email</th><th>User Email</th><th>Username</th><th>Risk Score</th><th>Match?</th></tr>";
    foreach ($mismatches as $match) {
        $isMatch = ($match['pref_email'] === $match['user_email']);
        echo "<tr>";
        echo "<td>" . htmlspecialchars($match['pref_email'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($match['user_email'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($match['username'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($match['risk_score'] ?? 'NULL') . "</td>";
        echo "<td style='color: " . ($isMatch ? 'green' : 'red') . "'>" . ($isMatch ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count total users and preferences
    echo "<h2>Database Statistics:</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_preferences");
    $stmt->execute();
    $prefCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_preferences WHERE risk_score >= 30");
    $stmt->execute();
    $criticalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Total Users:</strong> $userCount</p>";
    echo "<p><strong>Total User Preferences:</strong> $prefCount</p>";
    echo "<p><strong>Critical Alerts (Risk >= 30):</strong> $criticalCount</p>";
    
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
