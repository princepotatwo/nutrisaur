<?php
/**
 * Verify WHO Growth Standards Accuracy
 * Compare our implementation with exact WHO table values
 */

require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== WHO GROWTH STANDARDS ACCURACY VERIFICATION ===\n\n";

// Test cases from the official WHO tables
$testCases = [
    // Boys Weight-for-Age (from your image)
    ['type' => 'boys_weight_age', 'age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight'],
    ['type' => 'boys_weight_age', 'age' => 0, 'weight' => 2.3, 'expected' => 'Underweight'],
    ['type' => 'boys_weight_age', 'age' => 0, 'weight' => 3.5, 'expected' => 'Normal'],
    ['type' => 'boys_weight_age', 'age' => 0, 'weight' => 4.5, 'expected' => 'Overweight'],
    
    ['type' => 'boys_weight_age', 'age' => 1, 'weight' => 2.9, 'expected' => 'Severely Underweight'],
    ['type' => 'boys_weight_age', 'age' => 1, 'weight' => 3.2, 'expected' => 'Underweight'],
    ['type' => 'boys_weight_age', 'age' => 1, 'weight' => 4.5, 'expected' => 'Normal'],
    ['type' => 'boys_weight_age', 'age' => 1, 'weight' => 5.9, 'expected' => 'Overweight'],
    
    ['type' => 'boys_weight_age', 'age' => 2, 'weight' => 3.8, 'expected' => 'Severely Underweight'],
    ['type' => 'boys_weight_age', 'age' => 2, 'weight' => 4.1, 'expected' => 'Underweight'],
    ['type' => 'boys_weight_age', 'age' => 2, 'weight' => 5.5, 'expected' => 'Normal'],
    ['type' => 'boys_weight_age', 'age' => 2, 'weight' => 7.2, 'expected' => 'Overweight'],
    
    ['type' => 'boys_weight_age', 'age' => 3, 'weight' => 4.4, 'expected' => 'Severely Underweight'],
    ['type' => 'boys_weight_age', 'age' => 3, 'weight' => 4.7, 'expected' => 'Underweight'],
    ['type' => 'boys_weight_age', 'age' => 3, 'weight' => 6.5, 'expected' => 'Normal'],
    ['type' => 'boys_weight_age', 'age' => 3, 'weight' => 8.1, 'expected' => 'Overweight'],
    
    // Girls Weight-for-Age (from your image)
    ['type' => 'girls_weight_age', 'age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight'],
    ['type' => 'girls_weight_age', 'age' => 0, 'weight' => 2.2, 'expected' => 'Underweight'],
    ['type' => 'girls_weight_age', 'age' => 0, 'weight' => 3.3, 'expected' => 'Normal'],
    ['type' => 'girls_weight_age', 'age' => 0, 'weight' => 4.3, 'expected' => 'Overweight'],
    
    ['type' => 'girls_weight_age', 'age' => 12, 'weight' => 6.3, 'expected' => 'Severely Underweight'],
    ['type' => 'girls_weight_age', 'age' => 12, 'weight' => 6.7, 'expected' => 'Underweight'],
    ['type' => 'girls_weight_age', 'age' => 12, 'weight' => 9.0, 'expected' => 'Normal'],
    ['type' => 'girls_weight_age', 'age' => 12, 'weight' => 11.6, 'expected' => 'Overweight'],
    
    // Boys Weight-for-Height (from your image)
    ['type' => 'boys_weight_height', 'height' => 65, 'weight' => 5.8, 'expected' => 'Severely Wasted'],
    ['type' => 'boys_weight_height', 'height' => 65, 'weight' => 6.0, 'expected' => 'Wasted'],
    ['type' => 'boys_weight_height', 'height' => 65, 'weight' => 7.5, 'expected' => 'Normal'],
    ['type' => 'boys_weight_height', 'height' => 65, 'weight' => 9.6, 'expected' => 'Overweight'],
    ['type' => 'boys_weight_height', 'height' => 65, 'weight' => 9.7, 'expected' => 'Obese'],
    
    ['type' => 'boys_weight_height', 'height' => 70, 'weight' => 6.7, 'expected' => 'Severely Wasted'],
    ['type' => 'boys_weight_height', 'height' => 70, 'weight' => 6.9, 'expected' => 'Wasted'],
    ['type' => 'boys_weight_height', 'height' => 70, 'weight' => 8.5, 'expected' => 'Normal'],
    ['type' => 'boys_weight_height', 'height' => 70, 'weight' => 11.2, 'expected' => 'Overweight'],
    ['type' => 'boys_weight_height', 'height' => 70, 'weight' => 11.4, 'expected' => 'Obese'],
    
    // Girls Weight-for-Height (from your image)
    ['type' => 'girls_weight_height', 'height' => 65, 'weight' => 5.5, 'expected' => 'Severely Wasted'],
    ['type' => 'girls_weight_height', 'height' => 65, 'weight' => 5.7, 'expected' => 'Wasted'],
    ['type' => 'girls_weight_height', 'height' => 65, 'weight' => 7.0, 'expected' => 'Normal'],
    ['type' => 'girls_weight_height', 'height' => 65, 'weight' => 9.6, 'expected' => 'Overweight'],
    ['type' => 'girls_weight_height', 'height' => 65, 'weight' => 9.7, 'expected' => 'Obese'],
];

$correct = 0;
$total = count($testCases);

foreach ($testCases as $i => $test) {
    $birthDate = new DateTime();
    $birthDate->modify("-{$test['age']} months");
    
    if ($test['type'] === 'boys_weight_age') {
        $result = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    } elseif ($test['type'] === 'girls_weight_age') {
        $result = $who->calculateWeightForAge($test['weight'], $test['age'], 'Female');
    } elseif ($test['type'] === 'boys_weight_height') {
        $result = $who->calculateWeightForHeight($test['weight'], $test['height'], 'Male');
    } elseif ($test['type'] === 'girls_weight_height') {
        $result = $who->calculateWeightForHeight($test['weight'], $test['height'], 'Female');
    }
    
    $actual = $result['classification'] ?? 'N/A';
    $isCorrect = ($actual === $test['expected']);
    
    if ($isCorrect) {
        $correct++;
    }
    
    $status = $isCorrect ? '✅' : '❌';
    echo "Test " . ($i + 1) . ": {$status} ";
    echo "{$test['type']} - ";
    if (isset($test['age'])) echo "Age {$test['age']}mo, ";
    if (isset($test['height'])) echo "Height {$test['height']}cm, ";
    echo "Weight {$test['weight']}kg: ";
    echo "Expected '{$test['expected']}', Got '{$actual}'\n";
}

$accuracy = round(($correct / $total) * 100, 1);
echo "\n=== RESULTS ===\n";
echo "Correct: $correct/$total\n";
echo "Accuracy: $accuracy%\n";

if ($accuracy < 100) {
    echo "\n❌ ACCURACY ISSUES FOUND - Need to fix implementation\n";
} else {
    echo "\n✅ ALL TESTS PASSED - Implementation is accurate!\n";
}
?>
