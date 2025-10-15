<?php
/**
 * Check BMI Adult data in screening_history table
 */

require_once __DIR__ . "/../config.php";

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    echo "<h2>🔍 Current BMI Adult Data</h2>";
    
    // Get all BMI Adult records
    $stmt = $pdo->prepare("SELECT user_email, screening_date, bmi, classification FROM screening_history WHERE classification_type = 'bmi-adult' ORDER BY bmi ASC");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($records) . " BMI Adult records</p>";
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>User Email</th><th>Date</th><th>BMI</th><th>Current Classification</th><th>Should Be</th></tr>";
    
    foreach ($records as $record) {
        $bmi = floatval($record['bmi']);
        $currentClassification = $record['classification'];
        
        // Calculate what it should be
        $shouldBe = '';
        if ($bmi < 16.0) {
            $shouldBe = 'Severely Underweight';
        } else if ($bmi < 18.5) {
            $shouldBe = 'Underweight';
        } else if ($bmi < 25) {
            $shouldBe = 'Normal';
        } else if ($bmi < 30) {
            $shouldBe = 'Overweight';
        } else {
            $shouldBe = 'Obese';
        }
        
        $rowColor = ($currentClassification === $shouldBe) ? "background-color: #d4edda;" : "background-color: #f8d7da;";
        
        echo "<tr style='$rowColor'>";
        echo "<td>{$record['user_email']}</td>";
        echo "<td>{$record['screening_date']}</td>";
        echo "<td>$bmi</td>";
        echo "<td>$currentClassification</td>";
        echo "<td>$shouldBe</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
