<?php
/**
 * Simple Hardcoded WHO Decision Tree for Ages 0-35 months
 * No computation errors - pure if-else logic
 */

public function calculateWeightForAge($weight, $ageInMonths, $sex) {
    // Validate age range
    if ($ageInMonths < 0 || $ageInMonths > 35) {
        return [
            'z_score' => null,
            'classification' => 'Age out of range',
            'error' => 'Age must be 0-35 months for this simplified version',
            'method' => 'hardcoded_simple'
        ];
    }
    
    // HARDCODED DECISION TREE - Ages 0-35 months only
    if ($sex === 'Male') {
        // BOYS - Ages 0-35 months
        switch ($ageInMonths) {
            case 0: // Birth
                if ($weight <= 2.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 2.9) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 4.4) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 1:
                if ($weight <= 3.4) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 3.9) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 5.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 2:
                if ($weight <= 4.3) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 4.9) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 7.1) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 3:
                if ($weight <= 5.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 5.7) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 8.0) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 4:
                if ($weight <= 5.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 6.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 8.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 5:
                if ($weight <= 6.1) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 6.8) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 9.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 6:
                if ($weight <= 6.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 7.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 10.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 7:
                if ($weight <= 7.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 7.7) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 10.9) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 8:
                if ($weight <= 7.4) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 8.1) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 11.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 9:
                if ($weight <= 7.7) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 8.5) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 12.1) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 10:
                if ($weight <= 8.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 8.8) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 12.6) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 11:
                if ($weight <= 8.3) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 9.1) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 13.1) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 12:
                if ($weight <= 8.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 9.4) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 13.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 15:
                if ($weight <= 8.8) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 9.8) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 14.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 18:
                if ($weight <= 9.2) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 10.2) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 14.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 21:
                if ($weight <= 9.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 10.6) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 15.4) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 24:
                if ($weight <= 9.9) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 11.0) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 16.0) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 30:
                if ($weight <= 10.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 11.7) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 17.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 36:
                if ($weight <= 11.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 12.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 18.3) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            default:
                // For ages not explicitly listed, use closest age
                if ($ageInMonths <= 6) {
                    // Use 6 months data
                    if ($weight <= 6.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 7.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                } elseif ($ageInMonths <= 12) {
                    // Use 12 months data
                    if ($weight <= 8.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 9.4) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                } elseif ($ageInMonths <= 24) {
                    // Use 24 months data
                    if ($weight <= 9.9) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 11.0) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.0) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                } else {
                    // Use 36 months data
                    if ($weight <= 11.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 12.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.3) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                }
        }
    } else {
        // GIRLS - Ages 0-35 months
        switch ($ageInMonths) {
            case 0: // Birth
                if ($weight <= 2.4) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 2.8) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 4.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 1:
                if ($weight <= 3.2) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 3.6) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 5.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 2:
                if ($weight <= 4.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 4.5) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 6.6) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 3:
                if ($weight <= 4.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 5.2) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 7.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 4:
                if ($weight <= 5.1) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 5.7) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 8.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 5:
                if ($weight <= 5.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 6.1) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 8.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 6:
                if ($weight <= 5.9) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 6.5) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 9.3) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 7:
                if ($weight <= 6.2) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 6.9) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 9.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 8:
                if ($weight <= 6.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 7.2) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 10.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 9:
                if ($weight <= 6.8) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 7.5) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 10.6) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 10:
                if ($weight <= 7.0) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 7.8) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 11.0) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 11:
                if ($weight <= 7.3) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 8.0) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 11.4) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 12:
                if ($weight <= 7.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 8.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 11.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 15:
                if ($weight <= 7.8) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 8.6) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 12.5) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 18:
                if ($weight <= 8.1) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 9.0) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 13.2) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 21:
                if ($weight <= 8.4) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 9.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 13.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 24:
                if ($weight <= 8.7) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 9.6) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 14.4) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 30:
                if ($weight <= 9.2) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 10.2) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 15.4) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            case 36:
                if ($weight <= 9.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                if ($weight <= 10.7) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                if ($weight >= 16.1) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                
            default:
                // For ages not explicitly listed, use closest age
                if ($ageInMonths <= 6) {
                    // Use 6 months data
                    if ($weight <= 5.9) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 6.5) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.3) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                } elseif ($ageInMonths <= 12) {
                    // Use 12 months data
                    if ($weight <= 7.5) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 8.3) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.8) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                } elseif ($ageInMonths <= 24) {
                    // Use 24 months data
                    if ($weight <= 8.7) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 9.6) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.4) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                } else {
                    // Use 36 months data
                    if ($weight <= 9.6) return ['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight <= 10.7) return ['classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.1) return ['classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    return ['classification' => 'Normal', 'method' => 'hardcoded_simple'];
                }
        }
    }
}
?>
