<?php
// Check the boys' lookup table for data accuracy
require_once 'who_growth_standards.php';

echo "=== CHECKING BOYS' LOOKUP TABLE ACCURACY ===\n\n";

$who = new WHOGrowthStandards();
$boysTable = $who->getWeightForAgeBoysLookupTable();

echo "Total ages in boys' table: " . count($boysTable) . "\n";
echo "Available ages: " . implode(', ', array_keys($boysTable)) . "\n\n";

// Check specific problematic ages
$problemAges = [12, 24, 36, 48, 60, 71];

foreach ($problemAges as $age) {
    echo "--- AGE $age MONTHS ---\n";
    
    if (isset($boysTable[$age])) {
        $ranges = $boysTable[$age];
        echo "Severely Underweight: <= " . $ranges['severely_underweight']['max'] . " kg\n";
        echo "Underweight: <= " . $ranges['underweight']['max'] . " kg\n";
        echo "Normal: <= " . $ranges['normal']['max'] . " kg\n";
        echo "Overweight: >= " . $ranges['overweight']['min'] . " kg\n";
        
        // Check for logical consistency
        $issues = [];
        
        if ($ranges['severely_underweight']['max'] >= $ranges['underweight']['max']) {
            $issues[] = "Severely underweight max >= underweight max";
        }
        
        if ($ranges['underweight']['max'] >= $ranges['normal']['max']) {
            $issues[] = "Underweight max >= normal max";
        }
        
        if ($ranges['normal']['max'] >= $ranges['overweight']['min']) {
            $issues[] = "Normal max >= overweight min";
        }
        
        if (empty($issues)) {
            echo "✅ Data looks consistent\n";
        } else {
            echo "❌ ISSUES FOUND:\n";
            foreach ($issues as $issue) {
                echo "  - $issue\n";
            }
        }
        
    } else {
        echo "❌ Age $age not found in lookup table\n";
    }
    
    echo "\n";
}

// Test some specific weights that should be classified correctly
echo "=== TESTING SPECIFIC WEIGHTS ===\n";

$testWeights = [
    ['weight' => 24.1, 'age' => 71, 'expected' => 'Overweight'],
    ['weight' => 15.0, 'age' => 36, 'expected' => 'Normal'],
    ['weight' => 20.0, 'age' => 60, 'expected' => 'Normal'],
    ['weight' => 25.0, 'age' => 60, 'expected' => 'Overweight'],
];

foreach ($testWeights as $test) {
    echo "Weight: {$test['weight']} kg, Age: {$test['age']} months\n";
    
    if (isset($boysTable[$test['age']])) {
        $ranges = $boysTable[$test['age']];
        $weight = $test['weight'];
        
        if ($weight <= $ranges['severely_underweight']['max']) {
            $classification = 'Severely Underweight';
        } elseif ($weight <= $ranges['underweight']['max']) {
            $classification = 'Underweight';
        } elseif ($weight <= $ranges['normal']['max']) {
            $classification = 'Normal';
        } else {
            $classification = 'Overweight';
        }
        
        echo "Manual classification: $classification\n";
        echo "Expected: {$test['expected']}\n";
        
        if ($classification === $test['expected']) {
            echo "✅ CORRECT\n";
        } else {
            echo "❌ INCORRECT\n";
        }
        
    } else {
        echo "❌ Age {$test['age']} not found in lookup table\n";
    }
    
    echo "\n";
}
?>
