<?php
// Test script to verify layered filtering logic
echo "<h2>Testing Layered Filtering Logic</h2>";

// Simulate table data
$testData = [
    ['name' => 'John Doe', 'sex' => 'Male', 'age' => '2 years', 'weight' => '12.5 kg', 'classification' => 'Normal'],
    ['name' => 'Mike Smith', 'sex' => 'Male', 'age' => '3 years', 'weight' => '18.2 kg', 'classification' => 'Overweight'],
    ['name' => 'Jane Doe', 'sex' => 'Female', 'age' => '2 years', 'weight' => '11.8 kg', 'classification' => 'Normal'],
    ['name' => 'Tom Wilson', 'sex' => 'Male', 'age' => '1 year', 'weight' => '8.5 kg', 'classification' => 'Underweight'],
    ['name' => 'Sarah Johnson', 'sex' => 'Female', 'age' => '3 years', 'weight' => '17.8 kg', 'classification' => 'Overweight'],
    ['name' => 'Bob Brown', 'sex' => 'Male', 'age' => '4 years', 'weight' => '16.2 kg', 'classification' => 'Normal'],
];

function applyFilters($data, $sexFilter = '', $classificationFilter = '') {
    $results = [];
    
    foreach ($data as $row) {
        $showRow = true;
        
        // Sex filter
        if ($sexFilter && $showRow) {
            if ($row['sex'] !== $sexFilter) {
                $showRow = false;
            }
        }
        
        // Classification filter
        if ($classificationFilter && $showRow) {
            if ($row['classification'] !== $classificationFilter) {
                $showRow = false;
            }
        }
        
        if ($showRow) {
            $results[] = $row;
        }
    }
    
    return $results;
}

// Test cases
echo "<h3>Test Case 1: Male + Normal</h3>";
$results1 = applyFilters($testData, 'Male', 'Normal');
echo "<p><strong>Results:</strong></p><ul>";
foreach ($results1 as $row) {
    echo "<li>{$row['name']} ({$row['sex']}, {$row['classification']})</li>";
}
echo "</ul>";
echo "<p><strong>Expected:</strong> John Doe, Bob Brown</p>";
echo "<p><strong>Actual count:</strong> " . count($results1) . "</p>";

echo "<h3>Test Case 2: Female + Normal</h3>";
$results2 = applyFilters($testData, 'Female', 'Normal');
echo "<p><strong>Results:</strong></p><ul>";
foreach ($results2 as $row) {
    echo "<li>{$row['name']} ({$row['sex']}, {$row['classification']})</li>";
}
echo "</ul>";
echo "<p><strong>Expected:</strong> Jane Doe</p>";
echo "<p><strong>Actual count:</strong> " . count($results2) . "</p>";

echo "<h3>Test Case 3: Male + Overweight</h3>";
$results3 = applyFilters($testData, 'Male', 'Overweight');
echo "<p><strong>Results:</strong></p><ul>";
foreach ($results3 as $row) {
    echo "<li>{$row['name']} ({$row['sex']}, {$row['classification']})</li>";
}
echo "</ul>";
echo "<p><strong>Expected:</strong> Mike Smith</p>";
echo "<p><strong>Actual count:</strong> " . count($results3) . "</p>";

echo "<h3>Test Case 4: All + Normal</h3>";
$results4 = applyFilters($testData, '', 'Normal');
echo "<p><strong>Results:</strong></p><ul>";
foreach ($results4 as $row) {
    echo "<li>{$row['name']} ({$row['sex']}, {$row['classification']})</li>";
}
echo "</ul>";
echo "<p><strong>Expected:</strong> John Doe, Jane Doe, Bob Brown</p>";
echo "<p><strong>Actual count:</strong> " . count($results4) . "</p>";

echo "<h3>Test Case 5: Male only (no classification filter)</h3>";
$results5 = applyFilters($testData, 'Male', '');
echo "<p><strong>Results:</strong></p><ul>";
foreach ($results5 as $row) {
    echo "<li>{$row['name']} ({$row['sex']}, {$row['classification']})</li>";
}
echo "</ul>";
echo "<p><strong>Expected:</strong> John Doe, Mike Smith, Tom Wilson, Bob Brown</p>";
echo "<p><strong>Actual count:</strong> " . count($results5) . "</p>";

echo "<p><strong>All tests completed!</strong></p>";
?>
