<?php
/**
 * Example: How to use the Universal DatabaseAPI
 * This shows you how to NEVER use hardcoded database connections again!
 */

// Include the helper
require_once __DIR__ . '/api/DatabaseHelper.php';

echo "<h1>Universal DatabaseAPI Examples</h1>";

echo "<h2>1. Using DatabaseHelper Class</h2>";

// Get database helper instance
$db = DatabaseHelper::getInstance();

// Example 1: Select all users
echo "<h3>Get all user preferences:</h3>";
$users = $db->select('user_preferences', '*', '', [], 'created_at DESC', '5');
if ($users['success']) {
    echo "<pre>" . print_r($users['data'], true) . "</pre>";
} else {
    echo "Error: " . $users['message'];
}

// Example 2: Insert a new record
echo "<h3>Insert new user preference:</h3>";
$newUser = [
    'user_email' => 'example@test.com',
    'name' => 'Test User',
    'age' => 25,
    'gender' => 'Male',
    'barangay' => 'Test Barangay',
    'risk_score' => 15
];
$insertResult = $db->insert('user_preferences', $newUser);
echo "<pre>" . print_r($insertResult, true) . "</pre>";

// Example 3: Update a record
echo "<h3>Update user preference:</h3>";
$updateData = ['age' => 26, 'risk_score' => 20];
$updateResult = $db->update('user_preferences', $updateData, 'user_email = ?', ['example@test.com']);
echo "<pre>" . print_r($updateResult, true) . "</pre>";

// Example 4: Count records
echo "<h3>Count high-risk users:</h3>";
$highRiskCount = $db->count('user_preferences', 'risk_score > ?', [30]);
echo "High-risk users: $highRiskCount<br>";

// Example 5: Check if record exists
echo "<h3>Check if user exists:</h3>";
$exists = $db->exists('user_preferences', 'user_email = ?', ['example@test.com']);
echo "User exists: " . ($exists ? 'Yes' : 'No') . "<br>";

// Example 6: Get first record
echo "<h3>Get first user:</h3>";
$firstUser = $db->getFirst('user_preferences', 'age > ?', [20]);
if ($firstUser) {
    echo "<pre>" . print_r($firstUser, true) . "</pre>";
}

// Example 7: Custom query
echo "<h3>Custom query - Average age by gender:</h3>";
$avgAge = $db->query('SELECT gender, AVG(age) as avg_age FROM user_preferences GROUP BY gender', []);
if ($avgAge['success']) {
    echo "<pre>" . print_r($avgAge['data'], true) . "</pre>";
}

// Example 8: Get table structure
echo "<h3>User preferences table structure:</h3>";
$structure = $db->describe('user_preferences');
if ($structure['success']) {
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($structure['columns'] as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>2. Using Global Helper Functions (Even Easier!)</h2>";

// Example using global functions
echo "<h3>Using global helper functions:</h3>";

// Get all tables
$tables = db()->listTables();
echo "<h4>All tables in database:</h4>";
if ($tables['success']) {
    echo "<ul>";
    foreach ($tables['tables'] as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
}

// Quick select
$recentUsers = dbSelect('user_preferences', 'name, age, risk_score', 'created_at > ?', [date('Y-m-d', strtotime('-30 days'))], 'created_at DESC', '10');
echo "<h4>Recent users (last 30 days):</h4>";
if ($recentUsers['success']) {
    echo "<pre>" . print_r($recentUsers['data'], true) . "</pre>";
}

// Quick count
$totalUsers = dbCount('user_preferences');
echo "<h4>Total users: $totalUsers</h4>";

// Cleanup - delete test record
echo "<h3>Cleanup - Delete test record:</h3>";
$deleteResult = dbDelete('user_preferences', 'user_email = ?', ['example@test.com']);
echo "<pre>" . print_r($deleteResult, true) . "</pre>";

echo "<h2>3. Using AJAX API Endpoints</h2>";
echo "<p>You can also use these operations via AJAX:</p>";
echo "<pre>
// JavaScript example:
fetch('/api/DatabaseAPI.php?action=select', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        table: 'user_preferences',
        columns: 'name, age, risk_score',
        where: 'age > ?',
        params: [18],
        order_by: 'risk_score DESC',
        limit: '10'
    })
})
.then(response => response.json())
.then(data => console.log(data));

// Insert via AJAX:
fetch('/api/DatabaseAPI.php?action=insert', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        table: 'user_preferences',
        data: {
            user_email: 'new@example.com',
            name: 'New User',
            age: 30,
            gender: 'Female'
        }
    })
})
.then(response => response.json())
.then(data => console.log(data));

// Update via AJAX:
fetch('/api/DatabaseAPI.php?action=update', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        table: 'user_preferences',
        data: { risk_score: 25 },
        where: 'user_email = ?',
        params: ['new@example.com']
    })
})
.then(response => response.json())
.then(data => console.log(data));

// Delete via AJAX:
fetch('/api/DatabaseAPI.php?action=delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        table: 'user_preferences',
        where: 'user_email = ?',
        params: ['new@example.com']
    })
})
.then(response => response.json())
.then(data => console.log(data));
</pre>";

echo "<h2>✅ Benefits of Universal DatabaseAPI</h2>";
echo "<ul>";
echo "<li>🚫 <strong>No more hardcoded database connections!</strong></li>";
echo "<li>🔄 <strong>One API for all database operations</strong></li>";
echo "<li>🛡️ <strong>Built-in SQL injection protection</strong></li>";
echo "<li>📊 <strong>Consistent error handling</strong></li>";
echo "<li>🔧 <strong>Easy to use from any PHP file</strong></li>";
echo "<li>📱 <strong>Works with AJAX from frontend</strong></li>";
echo "<li>⚡ <strong>Centralized connection management</strong></li>";
echo "<li>🔍 <strong>Built-in debugging (shows SQL queries)</strong></li>";
echo "</ul>";

echo "<h2>🎯 How to Use in Your New Pages</h2>";
echo "<ol>";
echo "<li><strong>Include the helper:</strong> <code>require_once __DIR__ . '/api/DatabaseHelper.php';</code></li>";
echo "<li><strong>Use simple functions:</strong> <code>dbSelect('table_name', ...)</code></li>";
echo "<li><strong>Or use the class:</strong> <code>DatabaseHelper::getInstance()->select(...)</code></li>";
echo "<li><strong>For AJAX:</strong> Send requests to <code>/api/DatabaseAPI.php?action=select</code></li>";
echo "</ol>";

?>
