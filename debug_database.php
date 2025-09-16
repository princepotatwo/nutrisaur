<?php
require_once 'config.php';

echo "<h2>Database Debug - Check Users</h2>";

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo "<p style='color: red;'>Database connection failed</p>";
        exit;
    }
    
    // Get all users
    $stmt = $pdo->prepare("SELECT name, sex, birthday, weight, height, screening_date FROM community_users ORDER BY screening_date DESC LIMIT 20");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total users found:</strong> " . count($users) . "</p>";
    
    // Count by sex
    $maleCount = 0;
    $femaleCount = 0;
    foreach ($users as $user) {
        if ($user['sex'] === 'Male') {
            $maleCount++;
        } elseif ($user['sex'] === 'Female') {
            $femaleCount++;
        }
    }
    
    echo "<p><strong>Male users:</strong> {$maleCount}</p>";
    echo "<p><strong>Female users:</strong> {$femaleCount}</p>";
    
    echo "<h3>Sample Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Sex</th><th>Birthday</th><th>Weight</th><th>Height</th><th>Screening Date</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['sex']) . "</td>";
        echo "<td>" . htmlspecialchars($user['birthday']) . "</td>";
        echo "<td>" . htmlspecialchars($user['weight']) . "</td>";
        echo "<td>" . htmlspecialchars($user['height']) . "</td>";
        echo "<td>" . htmlspecialchars($user['screening_date']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
