<?php
/**
 * Verification Script: Decision Tree vs WHO Growth Standards Images
 * 
 * This script verifies that the decision tree implementation matches
 * the WHO growth standards shown in the provided images.
 */

require_once 'who_growth_standards.php';

class DecisionTreeVerifier {
    private $who;
    
    public function __construct() {
        $this->who = new WHOGrowthStandards();
    }
    
    /**
     * Test Weight-for-Age standards for boys against image data
     * Image shows: Age 0-71 months with specific weight ranges
     */
    public function testWeightForAgeBoys() {
        echo "<h2>Testing Weight-for-Age Standards for Boys (0-71 months)</h2>\n";
        
        // Test cases from the image data
        $testCases = [
            // Age 0 months: Severely Underweight < 2.1 kg, Underweight 2.2-2.4 kg, Normal 2.5-4.4 kg, Overweight > 4.5 kg
            ['age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight', 'description' => 'Age 0 months, 2.0 kg'],
            ['age' => 0, 'weight' => 2.3, 'expected' => 'Underweight', 'description' => 'Age 0 months, 2.3 kg'],
            ['age' => 0, 'weight' => 3.5, 'expected' => 'Normal', 'description' => 'Age 0 months, 3.5 kg'],
            ['age' => 0, 'weight' => 4.6, 'expected' => 'Overweight', 'description' => 'Age 0 months, 4.6 kg'],
            
            // Age 12 months: Severely Underweight < 6.9 kg, Underweight 7.0-7.6 kg, Normal 7.7-12.0 kg, Overweight > 12.1 kg
            ['age' => 12, 'weight' => 6.8, 'expected' => 'Severely Underweight', 'description' => 'Age 12 months, 6.8 kg'],
            ['age' => 12, 'weight' => 7.3, 'expected' => 'Underweight', 'description' => 'Age 12 months, 7.3 kg'],
            ['age' => 12, 'weight' => 9.0, 'expected' => 'Normal', 'description' => 'Age 12 months, 9.0 kg'],
            ['age' => 12, 'weight' => 12.2, 'expected' => 'Overweight', 'description' => 'Age 12 months, 12.2 kg'],
            
            // Age 35 months: Severely Underweight < 9.9 kg, Underweight 10.0-11.1 kg, Normal 11.2-18.1 kg, Overweight > 18.2 kg
            ['age' => 35, 'weight' => 9.8, 'expected' => 'Severely Underweight', 'description' => 'Age 35 months, 9.8 kg'],
            ['age' => 35, 'weight' => 10.5, 'expected' => 'Underweight', 'description' => 'Age 35 months, 10.5 kg'],
            ['age' => 35, 'weight' => 15.0, 'expected' => 'Normal', 'description' => 'Age 35 months, 15.0 kg'],
            ['age' => 35, 'weight' => 18.3, 'expected' => 'Overweight', 'description' => 'Age 35 months, 18.3 kg'],
            
            // Age 71 months: Severely Underweight < 13.9 kg, Underweight 14.0-15.6 kg, Normal 15.7-18.1 kg, Overweight > 18.2 kg
            ['age' => 71, 'weight' => 13.8, 'expected' => 'Severely Underweight', 'description' => 'Age 71 months, 13.8 kg'],
            ['age' => 71, 'weight' => 15.0, 'expected' => 'Underweight', 'description' => 'Age 71 months, 15.0 kg'],
            ['age' => 71, 'weight' => 17.0, 'expected' => 'Normal', 'description' => 'Age 71 months, 17.0 kg'],
            ['age' => 71, 'weight' => 18.5, 'expected' => 'Overweight', 'description' => 'Age 71 months, 18.5 kg'],
        ];
        
        $results = [];
        foreach ($testCases as $test) {
            $result = $this->who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
            $actual = $result['classification'];
            $match = ($actual === $test['expected']);
            
            $results[] = [
                'description' => $test['description'],
                'expected' => $test['expected'],
                'actual' => $actual,
                'match' => $match,
                'z_score' => $result['z_score'],
                'median' => $result['median'],
                'sd' => $result['sd']
            ];
            
            echo "<p><strong>{$test['description']}</strong><br>";
            echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
            echo "Z-score: {$result['z_score']}, Median: {$result['median']}, SD: {$result['sd']}</p>\n";
        }
        
        return $results;
    }
    
    /**
     * Test Weight-for-Height standards for girls against image data
     * Image shows: Height 65-120 cm with specific weight ranges for girls 24-60 months
     */
    public function testWeightForHeightGirls() {
        echo "<h2>Testing Weight-for-Height Standards for Girls (24-60 months)</h2>\n";
        
        // Test cases from the image data
        $testCases = [
            // Height 65 cm: Severely Wasted < 5.5 kg, Wasted 5.6-6.0 kg, Normal 6.1-8.7 kg, Overweight 8.8-9.7 kg, Obese > 9.8 kg
            ['height' => 65, 'weight' => 5.4, 'expected' => 'Severely Underweight', 'description' => 'Height 65 cm, 5.4 kg'],
            ['height' => 65, 'weight' => 5.8, 'expected' => 'Underweight', 'description' => 'Height 65 cm, 5.8 kg'],
            ['height' => 65, 'weight' => 7.0, 'expected' => 'Normal', 'description' => 'Height 65 cm, 7.0 kg'],
            ['height' => 65, 'weight' => 9.0, 'expected' => 'Overweight', 'description' => 'Height 65 cm, 9.0 kg'],
            ['height' => 65, 'weight' => 10.0, 'expected' => 'Overweight', 'description' => 'Height 65 cm, 10.0 kg'],
            
            // Height 90 cm: Severely Wasted < 9.7 kg, Wasted 9.8-10.4 kg, Normal 10.5-14.8 kg, Overweight 14.9-16.3 kg, Obese > 16.4 kg
            ['height' => 90, 'weight' => 9.6, 'expected' => 'Severely Underweight', 'description' => 'Height 90 cm, 9.6 kg'],
            ['height' => 90, 'weight' => 10.0, 'expected' => 'Underweight', 'description' => 'Height 90 cm, 10.0 kg'],
            ['height' => 90, 'weight' => 12.0, 'expected' => 'Normal', 'description' => 'Height 90 cm, 12.0 kg'],
            ['height' => 90, 'weight' => 15.0, 'expected' => 'Overweight', 'description' => 'Height 90 cm, 15.0 kg'],
            ['height' => 90, 'weight' => 17.0, 'expected' => 'Overweight', 'description' => 'Height 90 cm, 17.0 kg'],
            
            // Height 120 cm: Severely Wasted < 17.2 kg, Wasted 17.3-18.8 kg, Normal 18.9-28.0 kg, Overweight 28.1-31.2 kg, Obese > 31.3 kg
            ['height' => 120, 'weight' => 17.0, 'expected' => 'Severely Underweight', 'description' => 'Height 120 cm, 17.0 kg'],
            ['height' => 120, 'weight' => 18.0, 'expected' => 'Underweight', 'description' => 'Height 120 cm, 18.0 kg'],
            ['height' => 120, 'weight' => 22.0, 'expected' => 'Normal', 'description' => 'Height 120 cm, 22.0 kg'],
            ['height' => 120, 'weight' => 29.0, 'expected' => 'Overweight', 'description' => 'Height 120 cm, 29.0 kg'],
            ['height' => 120, 'weight' => 32.0, 'expected' => 'Overweight', 'description' => 'Height 120 cm, 32.0 kg'],
        ];
        
        $results = [];
        foreach ($testCases as $test) {
            $result = $this->who->calculateWeightForHeight($test['weight'], $test['height'], 'Female');
            $actual = $result['classification'];
            $match = ($actual === $test['expected']);
            
            $results[] = [
                'description' => $test['description'],
                'expected' => $test['expected'],
                'actual' => $actual,
                'match' => $match,
                'z_score' => $result['z_score'],
                'median' => $result['median'],
                'sd' => $result['sd']
            ];
            
            echo "<p><strong>{$test['description']}</strong><br>";
            echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
            echo "Z-score: {$result['z_score']}, Median: {$result['median']}, SD: {$result['sd']}</p>\n";
        }
        
        return $results;
    }
    
    /**
     * Test z-score classification logic
     */
    public function testZScoreClassification() {
        echo "<h2>Testing Z-Score Classification Logic</h2>\n";
        
        $testCases = [
            ['z_score' => -3.5, 'expected' => 'Severely Underweight', 'description' => 'Z-score -3.5'],
            ['z_score' => -2.5, 'expected' => 'Underweight', 'description' => 'Z-score -2.5'],
            ['z_score' => -1.5, 'expected' => 'Normal', 'description' => 'Z-score -1.5'],
            ['z_score' => 0.0, 'expected' => 'Normal', 'description' => 'Z-score 0.0'],
            ['z_score' => 1.5, 'expected' => 'Normal', 'description' => 'Z-score 1.5'],
            ['z_score' => 2.5, 'expected' => 'Overweight', 'description' => 'Z-score 2.5'],
            ['z_score' => 3.5, 'expected' => 'Overweight', 'description' => 'Z-score 3.5'],
        ];
        
        $results = [];
        foreach ($testCases as $test) {
            $actual = $this->who->getNutritionalClassification($test['z_score']);
            $match = ($actual === $test['expected']);
            
            $results[] = [
                'description' => $test['description'],
                'expected' => $test['expected'],
                'actual' => $actual,
                'match' => $match
            ];
            
            echo "<p><strong>{$test['description']}</strong><br>";
            echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "</p>\n";
        }
        
        return $results;
    }
    
    /**
     * Test specific values from the images to verify exact matches
     */
    public function testSpecificImageValues() {
        echo "<h2>Testing Specific Values from Images</h2>\n";
        
        // Test specific weight-for-age values from the boys image
        $boysTests = [
            // Age 0 months
            ['age' => 0, 'weight' => 2.1, 'expected' => 'Underweight', 'description' => 'Boys Age 0, Weight 2.1 kg (boundary)'],
            ['age' => 0, 'weight' => 2.4, 'expected' => 'Underweight', 'description' => 'Boys Age 0, Weight 2.4 kg (boundary)'],
            ['age' => 0, 'weight' => 2.5, 'expected' => 'Normal', 'description' => 'Boys Age 0, Weight 2.5 kg (boundary)'],
            ['age' => 0, 'weight' => 4.4, 'expected' => 'Normal', 'description' => 'Boys Age 0, Weight 4.4 kg (boundary)'],
            ['age' => 0, 'weight' => 4.5, 'expected' => 'Overweight', 'description' => 'Boys Age 0, Weight 4.5 kg (boundary)'],
            
            // Age 12 months
            ['age' => 12, 'weight' => 6.9, 'expected' => 'Underweight', 'description' => 'Boys Age 12, Weight 6.9 kg (boundary)'],
            ['age' => 12, 'weight' => 7.0, 'expected' => 'Underweight', 'description' => 'Boys Age 12, Weight 7.0 kg (boundary)'],
            ['age' => 12, 'weight' => 7.6, 'expected' => 'Underweight', 'description' => 'Boys Age 12, Weight 7.6 kg (boundary)'],
            ['age' => 12, 'weight' => 7.7, 'expected' => 'Normal', 'description' => 'Boys Age 12, Weight 7.7 kg (boundary)'],
            ['age' => 12, 'weight' => 12.0, 'expected' => 'Normal', 'description' => 'Boys Age 12, Weight 12.0 kg (boundary)'],
            ['age' => 12, 'weight' => 12.1, 'expected' => 'Overweight', 'description' => 'Boys Age 12, Weight 12.1 kg (boundary)'],
        ];
        
        echo "<h3>Boys Weight-for-Age Boundary Tests</h3>\n";
        foreach ($boysTests as $test) {
            $result = $this->who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
            $actual = $result['classification'];
            $match = ($actual === $test['expected']);
            
            echo "<p><strong>{$test['description']}</strong><br>";
            echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
            echo "Z-score: {$result['z_score']}, Median: {$result['median']}, SD: {$result['sd']}</p>\n";
        }
        
        // Test specific weight-for-height values from the girls image
        $girlsTests = [
            // Height 65 cm
            ['height' => 65, 'weight' => 5.5, 'expected' => 'Underweight', 'description' => 'Girls Height 65 cm, Weight 5.5 kg (boundary)'],
            ['height' => 65, 'weight' => 6.0, 'expected' => 'Underweight', 'description' => 'Girls Height 65 cm, Weight 6.0 kg (boundary)'],
            ['height' => 65, 'weight' => 6.1, 'expected' => 'Normal', 'description' => 'Girls Height 65 cm, Weight 6.1 kg (boundary)'],
            ['height' => 65, 'weight' => 8.7, 'expected' => 'Normal', 'description' => 'Girls Height 65 cm, Weight 8.7 kg (boundary)'],
            ['height' => 65, 'weight' => 8.8, 'expected' => 'Overweight', 'description' => 'Girls Height 65 cm, Weight 8.8 kg (boundary)'],
            ['height' => 65, 'weight' => 9.7, 'expected' => 'Overweight', 'description' => 'Girls Height 65 cm, Weight 9.7 kg (boundary)'],
            ['height' => 65, 'weight' => 9.8, 'expected' => 'Overweight', 'description' => 'Girls Height 65 cm, Weight 9.8 kg (boundary)'],
            
            // Height 90 cm
            ['height' => 90, 'weight' => 9.7, 'expected' => 'Underweight', 'description' => 'Girls Height 90 cm, Weight 9.7 kg (boundary)'],
            ['height' => 90, 'weight' => 9.8, 'expected' => 'Underweight', 'description' => 'Girls Height 90 cm, Weight 9.8 kg (boundary)'],
            ['height' => 90, 'weight' => 10.4, 'expected' => 'Underweight', 'description' => 'Girls Height 90 cm, Weight 10.4 kg (boundary)'],
            ['height' => 90, 'weight' => 10.5, 'expected' => 'Normal', 'description' => 'Girls Height 90 cm, Weight 10.5 kg (boundary)'],
            ['height' => 90, 'weight' => 14.8, 'expected' => 'Normal', 'description' => 'Girls Height 90 cm, Weight 14.8 kg (boundary)'],
            ['height' => 90, 'weight' => 14.9, 'expected' => 'Overweight', 'description' => 'Girls Height 90 cm, Weight 14.9 kg (boundary)'],
            ['height' => 90, 'weight' => 16.3, 'expected' => 'Overweight', 'description' => 'Girls Height 90 cm, Weight 16.3 kg (boundary)'],
            ['height' => 90, 'weight' => 16.4, 'expected' => 'Overweight', 'description' => 'Girls Height 90 cm, Weight 16.4 kg (boundary)'],
        ];
        
        echo "<h3>Girls Weight-for-Height Boundary Tests</h3>\n";
        foreach ($girlsTests as $test) {
            $result = $this->who->calculateWeightForHeight($test['weight'], $test['height'], 'Female');
            $actual = $result['classification'];
            $match = ($actual === $test['expected']);
            
            echo "<p><strong>{$test['description']}</strong><br>";
            echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
            echo "Z-score: {$result['z_score']}, Median: {$result['median']}, SD: {$result['sd']}</p>\n";
        }
    }
    
    /**
     * Generate comprehensive verification report
     */
    public function generateVerificationReport() {
        echo "<h1>WHO Growth Standards Decision Tree Verification Report</h1>\n";
        echo "<p>This report verifies that the decision tree implementation matches the WHO growth standards shown in the provided images.</p>\n";
        
        $boysResults = $this->testWeightForAgeBoys();
        $girlsResults = $this->testWeightForHeightGirls();
        $classificationResults = $this->testZScoreClassification();
        
        $this->testSpecificImageValues();
        
        // Summary
        $totalTests = count($boysResults) + count($girlsResults) + count($classificationResults);
        $boysMatches = count(array_filter($boysResults, function($r) { return $r['match']; }));
        $girlsMatches = count(array_filter($girlsResults, function($r) { return $r['match']; }));
        $classificationMatches = count(array_filter($classificationResults, function($r) { return $r['match']; }));
        $totalMatches = $boysMatches + $girlsMatches + $classificationMatches;
        
        echo "<h2>Verification Summary</h2>\n";
        echo "<p><strong>Total Tests:</strong> {$totalTests}</p>\n";
        echo "<p><strong>Total Matches:</strong> {$totalMatches}</p>\n";
        echo "<p><strong>Success Rate:</strong> " . round(($totalMatches / $totalTests) * 100, 2) . "%</p>\n";
        echo "<p><strong>Boys Weight-for-Age:</strong> {$boysMatches}/" . count($boysResults) . " matches</p>\n";
        echo "<p><strong>Girls Weight-for-Height:</strong> {$girlsMatches}/" . count($girlsResults) . " matches</p>\n";
        echo "<p><strong>Z-Score Classification:</strong> {$classificationMatches}/" . count($classificationResults) . " matches</p>\n";
        
        if ($totalMatches === $totalTests) {
            echo "<p style='color: green; font-weight: bold;'>✅ All tests passed! The decision tree matches the WHO growth standards from the images.</p>\n";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Some tests failed. The decision tree may not fully match the WHO growth standards from the images.</p>\n";
        }
    }
}

// Run verification if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'verify_decision_tree.php') {
    $verifier = new DecisionTreeVerifier();
    $verifier->generateVerificationReport();
}
?>
