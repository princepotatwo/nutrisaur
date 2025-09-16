<?php
require_once '../who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "<h1>🔍 Testing Lookup Functions Directly</h1>";

// Test the boys lookup table function
echo "<h2>Boys Lookup Table Function Test</h2>";
$boysData = $who->getWeightForAgeBoysLookupTable();
echo "<p>Boys lookup table has " . count($boysData) . " age entries</p>";
echo "<p>Age 71 exists: " . (isset($boysData[71]) ? 'YES' : 'NO') . "</p>";

if (isset($boysData[71])) {
    echo "<h3>Boys Age 71 Data:</h3>";
    echo "<pre>" . print_r($boysData[71], true) . "</pre>";
} else {
    echo "<p>❌ Age 71 not found in boys lookup table</p>";
    $lastAge = max(array_keys($boysData));
    echo "<p>Last age in boys table: $lastAge</p>";
}

// Test the girls lookup table function
echo "<h2>Girls Lookup Table Function Test</h2>";
$girlsData = $who->getWeightForAgeGirlsLookupTable();
echo "<p>Girls lookup table has " . count($girlsData) . " age entries</p>";
echo "<p>Age 71 exists: " . (isset($girlsData[71]) ? 'YES' : 'NO') . "</p>";

if (isset($girlsData[71])) {
    echo "<h3>Girls Age 71 Data:</h3>";
    echo "<pre>" . print_r($girlsData[71], true) . "</pre>";
}

// Test findClosestAge function
echo "<h2>Find Closest Age Test</h2>";
$boysClosest = $who->findClosestAge($boysData, 71);
$girlsClosest = $who->findClosestAge($girlsData, 71);
echo "<p>Closest age for 71 in boys table: $boysClosest</p>";
echo "<p>Closest age for 71 in girls table: $girlsClosest</p>";

// Test the actual calculateWeightForAge function
echo "<h2>Calculate Weight for Age Test</h2>";
$result = $who->calculateWeightForAge(24.1, 71, 'Male');
echo "<h3>Result for Male:</h3>";
echo "<pre>" . print_r($result, true) . "</pre>";

// Check if the function is actually calling the right lookup table
echo "<h2>Function Call Debug</h2>";
echo "<p>Let's trace what happens when we call calculateWeightForAge...</p>";

// Create a test class to override the function and add debugging
class DebugWHOGrowthStandards extends WHOGrowthStandards {
    public function getWeightForAgeBoysLookupTable() {
        echo "<p>🔍 DEBUG: getWeightForAgeBoysLookupTable() called</p>";
        $data = parent::getWeightForAgeBoysLookupTable();
        echo "<p>🔍 DEBUG: Boys lookup table returned " . count($data) . " entries</p>";
        if (isset($data[71])) {
            echo "<p>🔍 DEBUG: Age 71 found in boys table: " . json_encode($data[71]) . "</p>";
        } else {
            echo "<p>🔍 DEBUG: Age 71 NOT found in boys table</p>";
        }
        return $data;
    }
    
    public function getWeightForAgeGirlsLookupTable() {
        echo "<p>🔍 DEBUG: getWeightForAgeGirlsLookupTable() called</p>";
        $data = parent::getWeightForAgeGirlsLookupTable();
        echo "<p>🔍 DEBUG: Girls lookup table returned " . count($data) . " entries</p>";
        if (isset($data[71])) {
            echo "<p>🔍 DEBUG: Age 71 found in girls table: " . json_encode($data[71]) . "</p>";
        } else {
            echo "<p>🔍 DEBUG: Age 71 NOT found in girls table</p>";
        }
        return $data;
    }
}

$debugWho = new DebugWHOGrowthStandards();
echo "<h3>Testing with Debug Class:</h3>";
$debugResult = $debugWho->calculateWeightForAge(24.1, 71, 'Male');
echo "<h3>Debug Result:</h3>";
echo "<pre>" . print_r($debugResult, true) . "</pre>";
?>
