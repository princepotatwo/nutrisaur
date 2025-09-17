<?php
// Test script to verify layered filtering logic
echo "Testing Layered Filtering Logic\n";
echo "==============================\n\n";

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
echo "Test Case 1: Male + Normal\n";
echo "-------------------------\n";
$results1 = applyFilters($testData, 'Male', 'Normal');
foreach ($results1 as $row) {
    echo "- {$row['name']} ({$row['sex']}, {$row['classification']})\n";
}
echo "Expected: John Doe, Bob Brown\n";
echo "Actual count: " . count($results1) . "\n\n";

echo "Test Case 2: Female + Normal\n";
echo "---------------------------\n";
$results2 = applyFilters($testData, 'Female', 'Normal');
foreach ($results2 as $row) {
    echo "- {$row['name']} ({$row['sex']}, {$row['classification']})\n";
}
echo "Expected: Jane Doe\n";
echo "Actual count: " . count($results2) . "\n\n";

echo "Test Case 3: Male + Overweight\n";
echo "-----------------------------\n";
$results3 = applyFilters($testData, 'Male', 'Overweight');
foreach ($results3 as $row) {
    echo "- {$row['name']} ({$row['sex']}, {$row['classification']})\n";
}
echo "Expected: Mike Smith\n";
echo "Actual count: " . count($results3) . "\n\n";

echo "Test Case 4: All + Normal\n";
echo "------------------------\n";
$results4 = applyFilters($testData, '', 'Normal');
foreach ($results4 as $row) {
    echo "- {$row['name']} ({$row['sex']}, {$row['classification']})\n";
}
echo "Expected: John Doe, Jane Doe, Bob Brown\n";
echo "Actual count: " . count($results4) . "\n\n";

echo "Test Case 5: Male only (no classification filter)\n";
echo "------------------------------------------------\n";
$results5 = applyFilters($testData, 'Male', '');
foreach ($results5 as $row) {
    echo "- {$row['name']} ({$row['sex']}, {$row['classification']})\n";
}
echo "Expected: John Doe, Mike Smith, Tom Wilson, Bob Brown\n";
echo "Actual count: " . count($results5) . "\n\n";

echo "All tests completed!\n";
?>
