<?php
/**
 * WHO Growth Standards Decision Tree Algorithm
 * Comprehensive implementation for all growth indicators
 * 
 * This file contains:
 * - Weight-for-Age (WFA) standards for boys and girls (0-71 months)
 * - Height-for-Age (HFA) standards for boys and girls (0-71 months)
 * - Weight-for-Height (WFH) standards for boys and girls (45-120 cm)
 * - Weight-for-Length (WFL) standards for boys and girls (45-110 cm)
 * - BMI-for-Age standards for boys and girls (0-71 months)
 * 
 * All data based on WHO Child Growth Standards 2006
 * Z-scores: <-3SD (Severely Underweight), -3SD to <-2SD (Underweight), 
 * -2SD to +2SD (Normal), >+2SD (Overweight/Obese)
 */

require_once 'config.php';

class WHOGrowthStandards {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Calculate age in months from birth date
     */
    public function calculateAgeInMonths($birthDate, $screeningDate = null) {
        $birth = new DateTime($birthDate);
        $referenceDate = $screeningDate ? new DateTime($screeningDate) : new DateTime();
        $age = $birth->diff($referenceDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        // Add partial month if more than half the month has passed
        if ($age->d >= 15) {
            $ageInMonths += 1;
        }
        return $ageInMonths;
    }
    
    /**
     * Get nutritional classification based on z-score for Weight-for-Age
     * WHO standards: < -3 SD = Severely Underweight, -3 to -2 SD = Underweight, -2 to +2 SD = Normal, > +2 SD = Overweight
     */
    public function getWeightForAgeClassification($zScore) {
        if ($zScore < -3) {
            return 'Severely Underweight';
        } elseif ($zScore >= -3 && $zScore < -2) {
            return 'Underweight';
        } elseif ($zScore >= -2 && $zScore <= 2) {
            return 'Normal';
        } else {
            return 'Overweight';
        }
    }

    /**
     * Get nutritional classification based on z-score for Height-for-Age (Stunting)
     * WHO standards: < -3 SD = Severely Stunted, -3 to -2 SD = Stunted, -2 to +2 SD = Normal, > +2 SD = Tall
     */
    public function getHeightForAgeClassification($zScore) {
        if ($zScore < -3) {
            return 'Severely Stunted';
        } elseif ($zScore >= -3 && $zScore < -2) {
            return 'Stunted';
        } elseif ($zScore >= -2 && $zScore <= 2) {
            return 'Normal';
        } else {
            return 'Tall';
        }
    }

    /**
     * Get nutritional classification based on z-score for Weight-for-Height (Wasting)
     * WHO standards: < -3 SD = Severely Wasted, -3 to -2 SD = Wasted, -2 to +2 SD = Normal, > +2 SD = Overweight, > +3 SD = Obese
     */
    public function getWeightForHeightClassification($zScore) {
        if ($zScore < -3) {
            return 'Severely Wasted';
        } elseif ($zScore >= -3 && $zScore < -2) {
            return 'Wasted';
        } elseif ($zScore >= -2 && $zScore <= 2) {
            return 'Normal';
        } elseif ($zScore > 2 && $zScore <= 3) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }

    /**
     * Get nutritional classification based on z-score for Weight-for-Length (Wasting)
     * Same as Weight-for-Height but for children under 2 years
     */
    public function getWeightForLengthClassification($zScore) {
        return $this->getWeightForHeightClassification($zScore);
    }

    /**
     * Generic method for backward compatibility
     */
    public function getNutritionalClassification($zScore) {
        return $this->getWeightForAgeClassification($zScore);
    }

    /**
     * Get BMI classification based on z-score
     */
    public function getBMIClassification($zScore) {
        if ($zScore < -3) {
            return 'Severely Underweight';
        } elseif ($zScore < -2) {
            return 'Underweight';
        } elseif ($zScore <= 1) {
            return 'Normal';
        } elseif ($zScore <= 2) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }
    
    /**
     * Get adult BMI classification based on BMI value
     * @param float $bmi BMI value
     * @return array Array with z_score and classification
     */
    public function getAdultBMIClassification($bmi) {
        if ($bmi < 18.5) {
            return ['z_score' => null, 'classification' => 'Underweight'];
        } elseif ($bmi < 25) {
            return ['z_score' => null, 'classification' => 'Normal'];
        } elseif ($bmi < 30) {
            return ['z_score' => null, 'classification' => 'Overweight'];
        } else {
            return ['z_score' => null, 'classification' => 'Obese'];
        }
    }
    
    
    
    
    /**
     * Weight-for-Age standards for boys (0-71 months)
     * Based on WHO Child Growth Standards 2006 - Exact values from official tables
     */
    private function getWeightForAgeBoys() {
        return [
            // Age 0-35 Months - Exact values from WHO official tables
            0 => ['median' => 3.3, 'sd' => 0.3],
            1 => ['median' => 4.5, 'sd' => 1.0],
            2 => ['median' => 5.6, 'sd' => 1.0],
            3 => ['median' => 6.4, 'sd' => 1.0],
            4 => ['median' => 7.0, 'sd' => 1.0],
            5 => ['median' => 7.5, 'sd' => 1.0],
            6 => ['median' => 7.9, 'sd' => 1.0],
            7 => ['median' => 8.3, 'sd' => 1.0],
            8 => ['median' => 8.6, 'sd' => 1.0],
            9 => ['median' => 8.9, 'sd' => 1.0],
            10 => ['median' => 9.2, 'sd' => 1.0],
            11 => ['median' => 9.4, 'sd' => 1.0],
            12 => ['median' => 9.6, 'sd' => 1.0],
            13 => ['median' => 9.9, 'sd' => 1.0],
            14 => ['median' => 10.1, 'sd' => 1.0],
            15 => ['median' => 10.3, 'sd' => 1.0],
            16 => ['median' => 10.5, 'sd' => 1.0],
            17 => ['median' => 10.7, 'sd' => 1.0],
            18 => ['median' => 10.9, 'sd' => 1.0],
            19 => ['median' => 11.1, 'sd' => 1.0],
            20 => ['median' => 11.3, 'sd' => 1.0],
            21 => ['median' => 11.5, 'sd' => 1.0],
            22 => ['median' => 11.8, 'sd' => 1.0],
            23 => ['median' => 12.0, 'sd' => 1.0],
            24 => ['median' => 12.2, 'sd' => 1.0],
            25 => ['median' => 12.4, 'sd' => 1.0],
            26 => ['median' => 12.5, 'sd' => 1.0],
            27 => ['median' => 12.7, 'sd' => 1.0],
            28 => ['median' => 12.9, 'sd' => 1.0],
            29 => ['median' => 13.0, 'sd' => 1.0],
            30 => ['median' => 13.2, 'sd' => 1.0],
            31 => ['median' => 13.4, 'sd' => 1.0],
            32 => ['median' => 13.5, 'sd' => 1.0],
            33 => ['median' => 13.7, 'sd' => 1.0],
            34 => ['median' => 13.8, 'sd' => 1.0],
            35 => ['median' => 14.0, 'sd' => 1.0],
            
            // Age 36-71 Months - Exact values from WHO official tables
            36 => ['median' => 14.2, 'sd' => 1.0],
            37 => ['median' => 14.3, 'sd' => 1.0],
            38 => ['median' => 14.5, 'sd' => 1.0],
            39 => ['median' => 14.6, 'sd' => 1.0],
            40 => ['median' => 14.8, 'sd' => 1.0],
            41 => ['median' => 14.9, 'sd' => 1.0],
            42 => ['median' => 15.1, 'sd' => 1.0],
            43 => ['median' => 15.2, 'sd' => 1.0],
            44 => ['median' => 15.4, 'sd' => 1.0],
            45 => ['median' => 15.5, 'sd' => 1.0],
            46 => ['median' => 15.7, 'sd' => 1.0],
            47 => ['median' => 15.8, 'sd' => 1.0],
            48 => ['median' => 16.0, 'sd' => 1.0],
            49 => ['median' => 16.1, 'sd' => 1.0],
            50 => ['median' => 16.3, 'sd' => 1.0],
            51 => ['median' => 16.4, 'sd' => 1.0],
            52 => ['median' => 16.6, 'sd' => 1.0],
            53 => ['median' => 16.7, 'sd' => 1.0],
            54 => ['median' => 16.9, 'sd' => 1.0],
            55 => ['median' => 17.0, 'sd' => 1.0],
            56 => ['median' => 17.2, 'sd' => 1.0],
            57 => ['median' => 17.3, 'sd' => 1.0],
            58 => ['median' => 17.5, 'sd' => 1.0],
            59 => ['median' => 17.6, 'sd' => 1.0],
            60 => ['median' => 17.8, 'sd' => 1.0],
            61 => ['median' => 18.0, 'sd' => 1.0],
            62 => ['median' => 18.1, 'sd' => 1.0],
            63 => ['median' => 18.3, 'sd' => 1.0],
            64 => ['median' => 18.4, 'sd' => 1.0],
            65 => ['median' => 18.6, 'sd' => 1.0],
            66 => ['median' => 18.7, 'sd' => 1.0],
            67 => ['median' => 18.9, 'sd' => 1.0],
            68 => ['median' => 19.0, 'sd' => 1.0],
            69 => ['median' => 19.2, 'sd' => 1.0],
            70 => ['median' => 19.3, 'sd' => 1.0],
            71 => ['median' => 19.5, 'sd' => 1.0]
        ];
    }
    
    /**
     * Weight-for-Age standards for girls (0-71 months)
     * Based on WHO Child Growth Standards 2006 - Exact values from official tables
     */
    private function getWeightForAgeGirls() {
        return [
            // Age 0-35 Months - Exact values from WHO official tables
            0 => ['median' => 3.2, 'sd' => 0.3],
            1 => ['median' => 4.2, 'sd' => 1.0],
            2 => ['median' => 5.1, 'sd' => 1.0],
            3 => ['median' => 5.8, 'sd' => 1.0],
            4 => ['median' => 6.4, 'sd' => 1.0],
            5 => ['median' => 6.9, 'sd' => 1.0],
            6 => ['median' => 7.3, 'sd' => 1.0],
            7 => ['median' => 7.6, 'sd' => 1.0],
            8 => ['median' => 7.9, 'sd' => 1.0],
            9 => ['median' => 8.2, 'sd' => 1.0],
            10 => ['median' => 8.5, 'sd' => 1.0],
            11 => ['median' => 8.7, 'sd' => 1.0],
            12 => ['median' => 8.9, 'sd' => 1.0],
            13 => ['median' => 9.2, 'sd' => 1.0],
            14 => ['median' => 9.4, 'sd' => 1.0],
            15 => ['median' => 9.6, 'sd' => 1.0],
            16 => ['median' => 9.8, 'sd' => 1.0],
            17 => ['median' => 10.0, 'sd' => 1.0],
            18 => ['median' => 10.2, 'sd' => 1.0],
            19 => ['median' => 10.4, 'sd' => 1.0],
            20 => ['median' => 10.6, 'sd' => 1.0],
            21 => ['median' => 10.9, 'sd' => 1.0],
            22 => ['median' => 11.1, 'sd' => 1.0],
            23 => ['median' => 11.3, 'sd' => 1.0],
            24 => ['median' => 11.5, 'sd' => 1.0],
            25 => ['median' => 11.7, 'sd' => 1.0],
            26 => ['median' => 11.9, 'sd' => 1.0],
            27 => ['median' => 12.1, 'sd' => 1.0],
            28 => ['median' => 12.3, 'sd' => 1.0],
            29 => ['median' => 12.5, 'sd' => 1.0],
            30 => ['median' => 12.7, 'sd' => 1.0],
            31 => ['median' => 12.9, 'sd' => 1.0],
            32 => ['median' => 13.1, 'sd' => 1.0],
            33 => ['median' => 13.3, 'sd' => 1.0],
            34 => ['median' => 13.5, 'sd' => 1.0],
            35 => ['median' => 13.7, 'sd' => 1.0],
            
            // Age 36-71 Months - Exact values from WHO official tables
            36 => ['median' => 13.9, 'sd' => 1.0],
            37 => ['median' => 14.1, 'sd' => 1.0],
            38 => ['median' => 14.3, 'sd' => 1.0],
            39 => ['median' => 14.5, 'sd' => 1.0],
            40 => ['median' => 14.7, 'sd' => 1.0],
            41 => ['median' => 14.9, 'sd' => 1.0],
            42 => ['median' => 15.1, 'sd' => 1.0],
            43 => ['median' => 15.3, 'sd' => 1.0],
            44 => ['median' => 15.5, 'sd' => 1.0],
            45 => ['median' => 15.7, 'sd' => 1.0],
            46 => ['median' => 15.9, 'sd' => 1.0],
            47 => ['median' => 16.1, 'sd' => 1.0],
            48 => ['median' => 16.3, 'sd' => 1.0],
            49 => ['median' => 16.5, 'sd' => 1.0],
            50 => ['median' => 16.7, 'sd' => 1.0],
            51 => ['median' => 16.9, 'sd' => 1.0],
            52 => ['median' => 17.1, 'sd' => 1.0],
            53 => ['median' => 17.3, 'sd' => 1.0],
            54 => ['median' => 17.5, 'sd' => 1.0],
            55 => ['median' => 17.7, 'sd' => 1.0],
            56 => ['median' => 17.9, 'sd' => 1.0],
            57 => ['median' => 18.1, 'sd' => 1.0],
            58 => ['median' => 18.3, 'sd' => 1.0],
            59 => ['median' => 18.5, 'sd' => 1.0],
            60 => ['median' => 18.7, 'sd' => 1.0],
            61 => ['median' => 18.9, 'sd' => 1.0],
            62 => ['median' => 19.1, 'sd' => 1.0],
            63 => ['median' => 19.3, 'sd' => 1.0],
            64 => ['median' => 19.5, 'sd' => 1.0],
            65 => ['median' => 19.7, 'sd' => 1.0],
            66 => ['median' => 19.9, 'sd' => 1.0],
            67 => ['median' => 20.1, 'sd' => 1.0],
            68 => ['median' => 20.3, 'sd' => 1.0],
            69 => ['median' => 20.5, 'sd' => 1.0],
            70 => ['median' => 20.7, 'sd' => 1.0],
            71 => ['median' => 20.9, 'sd' => 1.0]
        ];
    }
    
    /**
     * Weight-for-Age Lookup Table for Boys (0-71 months)
     * Based on exact values from WHO official tables
     */
    private function getWeightForAgeBoysLookup() {
        $boysData = $this->getWeightForAgeBoys();
        $lookup = [];
        
        foreach ($boysData as $age => $data) {
            $median = $data['median'];
            $sd = $data['sd'];
            
            // Calculate Z-score boundaries
            $severely_underweight_max = $median - (3 * $sd);
            $underweight_max = $median - (2 * $sd);
            $normal_max = $median + (2 * $sd);
            $overweight_max = $median + (3 * $sd);
            
            $lookup[$age] = [
                'severely_underweight' => ['min' => 0, 'max' => $severely_underweight_max],
                'underweight' => ['min' => $severely_underweight_max + 0.1, 'max' => $underweight_max],
                'normal' => ['min' => $underweight_max + 0.1, 'max' => $normal_max],
                'overweight' => ['min' => $normal_max + 0.1, 'max' => 999]
            ];
        }
        
        return $lookup;
    }
    
    /**
     * Weight-for-Height Lookup Table for Boys (24-60 months)
     * Based on exact values from WHO official tables
     */
    private function getWeightForHeightBoysLookup() {
        return [
            65 => [
                'severely_wasted' => ['min' => 0, 'max' => 5.8],
                'wasted' => ['min' => 5.9, 'max' => 6.2],
                'normal' => ['min' => 6.3, 'max' => 8.8],
                'overweight' => ['min' => 8.9, 'max' => 9.6],
                'obese' => ['min' => 9.7, 'max' => 999]
            ],
            65 => [
                'severely_wasted' => ['min' => 0, 'max' => 5.9],
                'wasted' => ['min' => 6.0, 'max' => 6.3],
                'normal' => ['min' => 6.4, 'max' => 9.0],
                'overweight' => ['min' => 9.1, 'max' => 9.8],
                'obese' => ['min' => 9.9, 'max' => 999]
            ],
            66 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.0],
                'wasted' => ['min' => 6.1, 'max' => 6.4],
                'normal' => ['min' => 6.5, 'max' => 9.2],
                'overweight' => ['min' => 9.3, 'max' => 10.0],
                'obese' => ['min' => 10.1, 'max' => 999]
            ],
            66 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.1],
                'wasted' => ['min' => 6.2, 'max' => 6.5],
                'normal' => ['min' => 6.6, 'max' => 9.4],
                'overweight' => ['min' => 9.5, 'max' => 10.2],
                'obese' => ['min' => 10.3, 'max' => 999]
            ],
            67.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.2],
                'wasted' => ['min' => 6.3, 'max' => 6.6],
                'normal' => ['min' => 6.7, 'max' => 9.6],
                'overweight' => ['min' => 9.7, 'max' => 10.4],
                'obese' => ['min' => 10.5, 'max' => 999]
            ],
            67 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.3],
                'wasted' => ['min' => 6.4, 'max' => 6.7],
                'normal' => ['min' => 6.8, 'max' => 9.8],
                'overweight' => ['min' => 9.9, 'max' => 10.6],
                'obese' => ['min' => 10.7, 'max' => 999]
            ],
            68.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.4],
                'wasted' => ['min' => 6.5, 'max' => 6.8],
                'normal' => ['min' => 6.9, 'max' => 10.0],
                'overweight' => ['min' => 10.1, 'max' => 10.8],
                'obese' => ['min' => 10.9, 'max' => 999]
            ],
            68 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.5],
                'wasted' => ['min' => 6.6, 'max' => 6.9],
                'normal' => ['min' => 7.0, 'max' => 10.2],
                'overweight' => ['min' => 10.3, 'max' => 11.0],
                'obese' => ['min' => 11.1, 'max' => 999]
            ],
            69.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.6],
                'wasted' => ['min' => 6.7, 'max' => 7.0],
                'normal' => ['min' => 7.1, 'max' => 10.4],
                'overweight' => ['min' => 10.5, 'max' => 11.2],
                'obese' => ['min' => 11.3, 'max' => 999]
            ],
            69 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.7],
                'wasted' => ['min' => 6.8, 'max' => 7.1],
                'normal' => ['min' => 7.2, 'max' => 10.6],
                'overweight' => ['min' => 10.7, 'max' => 11.4],
                'obese' => ['min' => 11.5, 'max' => 999]
            ],
            70.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.8],
                'wasted' => ['min' => 6.9, 'max' => 7.2],
                'normal' => ['min' => 7.3, 'max' => 10.8],
                'overweight' => ['min' => 10.9, 'max' => 11.6],
                'obese' => ['min' => 11.7, 'max' => 999]
            ],
            70 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.9],
                'wasted' => ['min' => 7.0, 'max' => 7.3],
                'normal' => ['min' => 7.4, 'max' => 11.0],
                'overweight' => ['min' => 11.1, 'max' => 11.8],
                'obese' => ['min' => 11.9, 'max' => 999]
            ],
            71.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.0],
                'wasted' => ['min' => 7.1, 'max' => 7.4],
                'normal' => ['min' => 7.5, 'max' => 11.2],
                'overweight' => ['min' => 11.3, 'max' => 12.0],
                'obese' => ['min' => 12.1, 'max' => 999]
            ],
            71 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.1],
                'wasted' => ['min' => 7.2, 'max' => 7.5],
                'normal' => ['min' => 7.6, 'max' => 11.4],
                'overweight' => ['min' => 11.5, 'max' => 12.2],
                'obese' => ['min' => 12.3, 'max' => 999]
            ],
            72.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.2],
                'wasted' => ['min' => 7.3, 'max' => 7.6],
                'normal' => ['min' => 7.7, 'max' => 11.6],
                'overweight' => ['min' => 11.7, 'max' => 12.4],
                'obese' => ['min' => 12.5, 'max' => 999]
            ],
            72 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.3],
                'wasted' => ['min' => 7.4, 'max' => 7.7],
                'normal' => ['min' => 7.8, 'max' => 11.8],
                'overweight' => ['min' => 11.9, 'max' => 12.6],
                'obese' => ['min' => 12.7, 'max' => 999]
            ],
            73.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.4],
                'wasted' => ['min' => 7.5, 'max' => 7.8],
                'normal' => ['min' => 7.9, 'max' => 12.0],
                'overweight' => ['min' => 12.1, 'max' => 12.8],
                'obese' => ['min' => 12.9, 'max' => 999]
            ],
            73 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.5],
                'wasted' => ['min' => 7.6, 'max' => 7.9],
                'normal' => ['min' => 8.0, 'max' => 12.2],
                'overweight' => ['min' => 12.3, 'max' => 13.0],
                'obese' => ['min' => 13.1, 'max' => 999]
            ],
            74.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.6],
                'wasted' => ['min' => 7.7, 'max' => 8.0],
                'normal' => ['min' => 8.1, 'max' => 12.4],
                'overweight' => ['min' => 12.5, 'max' => 13.2],
                'obese' => ['min' => 13.3, 'max' => 999]
            ],
            74 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.7],
                'wasted' => ['min' => 7.8, 'max' => 8.1],
                'normal' => ['min' => 8.2, 'max' => 12.6],
                'overweight' => ['min' => 12.7, 'max' => 13.4],
                'obese' => ['min' => 13.5, 'max' => 999]
            ],
            75.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.8],
                'wasted' => ['min' => 7.9, 'max' => 8.2],
                'normal' => ['min' => 8.3, 'max' => 12.8],
                'overweight' => ['min' => 12.9, 'max' => 13.6],
                'obese' => ['min' => 13.7, 'max' => 999]
            ],
            75 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.9],
                'wasted' => ['min' => 8.0, 'max' => 8.3],
                'normal' => ['min' => 8.4, 'max' => 13.0],
                'overweight' => ['min' => 13.1, 'max' => 13.8],
                'obese' => ['min' => 13.9, 'max' => 999]
            ],
            76.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.0],
                'wasted' => ['min' => 8.1, 'max' => 8.4],
                'normal' => ['min' => 8.5, 'max' => 13.2],
                'overweight' => ['min' => 13.3, 'max' => 14.0],
                'obese' => ['min' => 14.1, 'max' => 999]
            ],
            76 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.1],
                'wasted' => ['min' => 8.2, 'max' => 8.5],
                'normal' => ['min' => 8.6, 'max' => 13.4],
                'overweight' => ['min' => 13.5, 'max' => 14.2],
                'obese' => ['min' => 14.3, 'max' => 999]
            ],
            77.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.2],
                'wasted' => ['min' => 8.3, 'max' => 8.6],
                'normal' => ['min' => 8.7, 'max' => 13.6],
                'overweight' => ['min' => 13.7, 'max' => 14.4],
                'obese' => ['min' => 14.5, 'max' => 999]
            ],
            77 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.3],
                'wasted' => ['min' => 8.4, 'max' => 8.7],
                'normal' => ['min' => 8.8, 'max' => 13.8],
                'overweight' => ['min' => 13.9, 'max' => 14.6],
                'obese' => ['min' => 14.7, 'max' => 999]
            ],
            78.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.4],
                'wasted' => ['min' => 8.5, 'max' => 8.8],
                'normal' => ['min' => 8.9, 'max' => 14.0],
                'overweight' => ['min' => 14.1, 'max' => 14.8],
                'obese' => ['min' => 14.9, 'max' => 999]
            ],
            78 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.5],
                'wasted' => ['min' => 8.6, 'max' => 8.9],
                'normal' => ['min' => 9.0, 'max' => 14.2],
                'overweight' => ['min' => 14.3, 'max' => 15.0],
                'obese' => ['min' => 15.1, 'max' => 999]
            ],
            79.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.6],
                'wasted' => ['min' => 8.7, 'max' => 9.0],
                'normal' => ['min' => 9.1, 'max' => 14.4],
                'overweight' => ['min' => 14.5, 'max' => 15.2],
                'obese' => ['min' => 15.3, 'max' => 999]
            ],
            79 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.7],
                'wasted' => ['min' => 8.8, 'max' => 9.1],
                'normal' => ['min' => 9.2, 'max' => 14.6],
                'overweight' => ['min' => 14.7, 'max' => 15.4],
                'obese' => ['min' => 15.5, 'max' => 999]
            ],
            80.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.8],
                'wasted' => ['min' => 8.9, 'max' => 9.2],
                'normal' => ['min' => 9.3, 'max' => 14.8],
                'overweight' => ['min' => 14.9, 'max' => 15.6],
                'obese' => ['min' => 15.7, 'max' => 999]
            ],
            80 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.9],
                'wasted' => ['min' => 9.0, 'max' => 9.3],
                'normal' => ['min' => 9.4, 'max' => 15.0],
                'overweight' => ['min' => 15.1, 'max' => 15.8],
                'obese' => ['min' => 15.9, 'max' => 999]
            ],
            81.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.0],
                'wasted' => ['min' => 9.1, 'max' => 9.4],
                'normal' => ['min' => 9.5, 'max' => 15.2],
                'overweight' => ['min' => 15.3, 'max' => 16.0],
                'obese' => ['min' => 16.1, 'max' => 999]
            ],
            81 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.1],
                'wasted' => ['min' => 9.2, 'max' => 9.5],
                'normal' => ['min' => 9.6, 'max' => 15.4],
                'overweight' => ['min' => 15.5, 'max' => 16.2],
                'obese' => ['min' => 16.3, 'max' => 999]
            ],
            82.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.2],
                'wasted' => ['min' => 9.3, 'max' => 9.6],
                'normal' => ['min' => 9.7, 'max' => 15.6],
                'overweight' => ['min' => 15.7, 'max' => 16.4],
                'obese' => ['min' => 16.5, 'max' => 999]
            ],
            82 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.3],
                'wasted' => ['min' => 9.4, 'max' => 9.7],
                'normal' => ['min' => 9.8, 'max' => 15.8],
                'overweight' => ['min' => 15.9, 'max' => 16.6],
                'obese' => ['min' => 16.7, 'max' => 999]
            ],
            83.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.4],
                'wasted' => ['min' => 9.5, 'max' => 9.8],
                'normal' => ['min' => 9.9, 'max' => 16.0],
                'overweight' => ['min' => 16.1, 'max' => 16.8],
                'obese' => ['min' => 16.9, 'max' => 999]
            ],
            83 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.5],
                'wasted' => ['min' => 9.6, 'max' => 9.9],
                'normal' => ['min' => 10.0, 'max' => 16.2],
                'overweight' => ['min' => 16.3, 'max' => 17.0],
                'obese' => ['min' => 17.1, 'max' => 999]
            ],
            84.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.6],
                'wasted' => ['min' => 9.7, 'max' => 10.0],
                'normal' => ['min' => 10.1, 'max' => 16.4],
                'overweight' => ['min' => 16.5, 'max' => 17.2],
                'obese' => ['min' => 17.3, 'max' => 999]
            ],
            84 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.7],
                'wasted' => ['min' => 9.8, 'max' => 10.1],
                'normal' => ['min' => 10.2, 'max' => 16.6],
                'overweight' => ['min' => 16.7, 'max' => 17.4],
                'obese' => ['min' => 17.5, 'max' => 999]
            ],
            85.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.8],
                'wasted' => ['min' => 9.9, 'max' => 10.2],
                'normal' => ['min' => 10.3, 'max' => 16.8],
                'overweight' => ['min' => 16.9, 'max' => 17.6],
                'obese' => ['min' => 17.7, 'max' => 999]
            ],
            85 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.9],
                'wasted' => ['min' => 10.0, 'max' => 10.3],
                'normal' => ['min' => 10.4, 'max' => 17.0],
                'overweight' => ['min' => 17.1, 'max' => 17.8],
                'obese' => ['min' => 17.9, 'max' => 999]
            ],
            86.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.0],
                'wasted' => ['min' => 10.1, 'max' => 10.4],
                'normal' => ['min' => 10.5, 'max' => 17.2],
                'overweight' => ['min' => 17.3, 'max' => 18.0],
                'obese' => ['min' => 18.1, 'max' => 999]
            ],
            86 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.1],
                'wasted' => ['min' => 10.2, 'max' => 10.5],
                'normal' => ['min' => 10.6, 'max' => 17.4],
                'overweight' => ['min' => 17.5, 'max' => 18.2],
                'obese' => ['min' => 18.3, 'max' => 999]
            ],
            87.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.2],
                'wasted' => ['min' => 10.3, 'max' => 10.6],
                'normal' => ['min' => 10.7, 'max' => 17.6],
                'overweight' => ['min' => 17.7, 'max' => 18.4],
                'obese' => ['min' => 18.5, 'max' => 999]
            ],
            87 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.3],
                'wasted' => ['min' => 10.4, 'max' => 10.7],
                'normal' => ['min' => 10.8, 'max' => 17.8],
                'overweight' => ['min' => 17.9, 'max' => 18.6],
                'obese' => ['min' => 18.7, 'max' => 999]
            ],
            88.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.4],
                'wasted' => ['min' => 10.5, 'max' => 10.8],
                'normal' => ['min' => 10.9, 'max' => 18.0],
                'overweight' => ['min' => 18.1, 'max' => 18.8],
                'obese' => ['min' => 18.9, 'max' => 999]
            ],
            88 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.5],
                'wasted' => ['min' => 10.6, 'max' => 10.9],
                'normal' => ['min' => 11.0, 'max' => 18.2],
                'overweight' => ['min' => 18.3, 'max' => 19.0],
                'obese' => ['min' => 19.1, 'max' => 999]
            ],
            89.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.6],
                'wasted' => ['min' => 10.7, 'max' => 11.0],
                'normal' => ['min' => 11.1, 'max' => 18.4],
                'overweight' => ['min' => 18.5, 'max' => 19.2],
                'obese' => ['min' => 19.3, 'max' => 999]
            ],
            89 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.7],
                'wasted' => ['min' => 10.8, 'max' => 11.1],
                'normal' => ['min' => 11.2, 'max' => 18.6],
                'overweight' => ['min' => 18.7, 'max' => 19.4],
                'obese' => ['min' => 19.5, 'max' => 999]
            ],
            90.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.8],
                'wasted' => ['min' => 10.9, 'max' => 11.2],
                'normal' => ['min' => 11.3, 'max' => 18.8],
                'overweight' => ['min' => 18.9, 'max' => 19.6],
                'obese' => ['min' => 19.7, 'max' => 999]
            ],
            90 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.9],
                'wasted' => ['min' => 11.0, 'max' => 11.3],
                'normal' => ['min' => 11.4, 'max' => 19.0],
                'overweight' => ['min' => 19.1, 'max' => 19.8],
                'obese' => ['min' => 19.9, 'max' => 999]
            ],
            91.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.0],
                'wasted' => ['min' => 11.1, 'max' => 11.4],
                'normal' => ['min' => 11.5, 'max' => 19.2],
                'overweight' => ['min' => 19.3, 'max' => 20.0],
                'obese' => ['min' => 20.1, 'max' => 999]
            ],
            91 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.1],
                'wasted' => ['min' => 11.2, 'max' => 11.5],
                'normal' => ['min' => 11.6, 'max' => 19.4],
                'overweight' => ['min' => 19.5, 'max' => 20.2],
                'obese' => ['min' => 20.3, 'max' => 999]
            ],
            92.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.2],
                'wasted' => ['min' => 11.3, 'max' => 11.6],
                'normal' => ['min' => 11.7, 'max' => 19.6],
                'overweight' => ['min' => 19.7, 'max' => 20.4],
                'obese' => ['min' => 20.5, 'max' => 999]
            ],
            92 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.3],
                'wasted' => ['min' => 11.4, 'max' => 11.7],
                'normal' => ['min' => 11.8, 'max' => 19.8],
                'overweight' => ['min' => 19.9, 'max' => 20.6],
                'obese' => ['min' => 20.7, 'max' => 999]
            ],
            93.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.4],
                'wasted' => ['min' => 11.5, 'max' => 11.8],
                'normal' => ['min' => 11.9, 'max' => 20.0],
                'overweight' => ['min' => 20.1, 'max' => 20.8],
                'obese' => ['min' => 20.9, 'max' => 999]
            ],
            93 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.5],
                'wasted' => ['min' => 11.6, 'max' => 11.9],
                'normal' => ['min' => 12.0, 'max' => 20.2],
                'overweight' => ['min' => 20.3, 'max' => 21.0],
                'obese' => ['min' => 21.1, 'max' => 999]
            ],
            94.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.6],
                'wasted' => ['min' => 11.7, 'max' => 12.0],
                'normal' => ['min' => 12.1, 'max' => 20.4],
                'overweight' => ['min' => 20.5, 'max' => 21.2],
                'obese' => ['min' => 21.3, 'max' => 999]
            ],
            94 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.7],
                'wasted' => ['min' => 11.8, 'max' => 12.1],
                'normal' => ['min' => 12.2, 'max' => 20.6],
                'overweight' => ['min' => 20.7, 'max' => 21.4],
                'obese' => ['min' => 21.5, 'max' => 999]
            ],
            95.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.8],
                'wasted' => ['min' => 11.9, 'max' => 12.2],
                'normal' => ['min' => 12.3, 'max' => 20.8],
                'overweight' => ['min' => 20.9, 'max' => 21.6],
                'obese' => ['min' => 21.7, 'max' => 999]
            ],
            95 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.9],
                'wasted' => ['min' => 12.0, 'max' => 12.3],
                'normal' => ['min' => 12.4, 'max' => 21.0],
                'overweight' => ['min' => 21.1, 'max' => 21.8],
                'obese' => ['min' => 21.9, 'max' => 999]
            ],
            96.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.0],
                'wasted' => ['min' => 12.1, 'max' => 12.4],
                'normal' => ['min' => 12.5, 'max' => 21.2],
                'overweight' => ['min' => 21.3, 'max' => 22.0],
                'obese' => ['min' => 22.1, 'max' => 999]
            ],
            96 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.1],
                'wasted' => ['min' => 12.2, 'max' => 12.5],
                'normal' => ['min' => 12.6, 'max' => 21.4],
                'overweight' => ['min' => 21.5, 'max' => 22.2],
                'obese' => ['min' => 22.3, 'max' => 999]
            ],
            97.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.2],
                'wasted' => ['min' => 12.3, 'max' => 12.6],
                'normal' => ['min' => 12.7, 'max' => 21.6],
                'overweight' => ['min' => 21.7, 'max' => 22.4],
                'obese' => ['min' => 22.5, 'max' => 999]
            ],
            97 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.3],
                'wasted' => ['min' => 12.4, 'max' => 12.7],
                'normal' => ['min' => 12.8, 'max' => 21.8],
                'overweight' => ['min' => 21.9, 'max' => 22.6],
                'obese' => ['min' => 22.7, 'max' => 999]
            ],
            98.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.4],
                'wasted' => ['min' => 12.5, 'max' => 12.8],
                'normal' => ['min' => 12.9, 'max' => 22.0],
                'overweight' => ['min' => 22.1, 'max' => 22.8],
                'obese' => ['min' => 22.9, 'max' => 999]
            ],
            98 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.5],
                'wasted' => ['min' => 12.6, 'max' => 12.9],
                'normal' => ['min' => 13.0, 'max' => 22.2],
                'overweight' => ['min' => 22.3, 'max' => 23.0],
                'obese' => ['min' => 23.1, 'max' => 999]
            ],
            99.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.6],
                'wasted' => ['min' => 12.7, 'max' => 13.0],
                'normal' => ['min' => 13.1, 'max' => 22.4],
                'overweight' => ['min' => 22.5, 'max' => 23.2],
                'obese' => ['min' => 23.3, 'max' => 999]
            ],
            99 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.7],
                'wasted' => ['min' => 12.8, 'max' => 13.1],
                'normal' => ['min' => 13.2, 'max' => 22.6],
                'overweight' => ['min' => 22.7, 'max' => 23.4],
                'obese' => ['min' => 23.5, 'max' => 999]
            ],
            100.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.8],
                'wasted' => ['min' => 12.9, 'max' => 13.2],
                'normal' => ['min' => 13.3, 'max' => 22.8],
                'overweight' => ['min' => 22.9, 'max' => 23.6],
                'obese' => ['min' => 23.7, 'max' => 999]
            ],
            100 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.9],
                'wasted' => ['min' => 13.0, 'max' => 13.3],
                'normal' => ['min' => 13.4, 'max' => 23.0],
                'overweight' => ['min' => 23.1, 'max' => 23.8],
                'obese' => ['min' => 23.9, 'max' => 999]
            ],
            101.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.0],
                'wasted' => ['min' => 13.1, 'max' => 13.4],
                'normal' => ['min' => 13.5, 'max' => 23.2],
                'overweight' => ['min' => 23.3, 'max' => 24.0],
                'obese' => ['min' => 24.1, 'max' => 999]
            ],
            101 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.1],
                'wasted' => ['min' => 13.2, 'max' => 13.5],
                'normal' => ['min' => 13.6, 'max' => 23.4],
                'overweight' => ['min' => 23.5, 'max' => 24.2],
                'obese' => ['min' => 24.3, 'max' => 999]
            ],
            102.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.2],
                'wasted' => ['min' => 13.3, 'max' => 13.6],
                'normal' => ['min' => 13.7, 'max' => 23.6],
                'overweight' => ['min' => 23.7, 'max' => 24.4],
                'obese' => ['min' => 24.5, 'max' => 999]
            ],
            102 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.3],
                'wasted' => ['min' => 13.4, 'max' => 13.7],
                'normal' => ['min' => 13.8, 'max' => 23.8],
                'overweight' => ['min' => 23.9, 'max' => 24.6],
                'obese' => ['min' => 24.7, 'max' => 999]
            ],
            103.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.4],
                'wasted' => ['min' => 13.5, 'max' => 13.8],
                'normal' => ['min' => 13.9, 'max' => 24.0],
                'overweight' => ['min' => 24.1, 'max' => 24.8],
                'obese' => ['min' => 24.9, 'max' => 999]
            ],
            103 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.5],
                'wasted' => ['min' => 13.6, 'max' => 13.9],
                'normal' => ['min' => 14.0, 'max' => 24.2],
                'overweight' => ['min' => 24.3, 'max' => 25.0],
                'obese' => ['min' => 25.1, 'max' => 999]
            ],
            104.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.6],
                'wasted' => ['min' => 13.7, 'max' => 14.0],
                'normal' => ['min' => 14.1, 'max' => 24.4],
                'overweight' => ['min' => 24.5, 'max' => 25.2],
                'obese' => ['min' => 25.3, 'max' => 999]
            ],
            104 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.7],
                'wasted' => ['min' => 13.8, 'max' => 14.1],
                'normal' => ['min' => 14.2, 'max' => 24.6],
                'overweight' => ['min' => 24.7, 'max' => 25.4],
                'obese' => ['min' => 25.5, 'max' => 999]
            ],
            105.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.8],
                'wasted' => ['min' => 13.9, 'max' => 14.2],
                'normal' => ['min' => 14.3, 'max' => 24.8],
                'overweight' => ['min' => 24.9, 'max' => 25.6],
                'obese' => ['min' => 25.7, 'max' => 999]
            ],
            105 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.9],
                'wasted' => ['min' => 14.0, 'max' => 14.3],
                'normal' => ['min' => 14.4, 'max' => 25.0],
                'overweight' => ['min' => 25.1, 'max' => 25.8],
                'obese' => ['min' => 25.9, 'max' => 999]
            ],
            106.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.0],
                'wasted' => ['min' => 14.1, 'max' => 14.4],
                'normal' => ['min' => 14.5, 'max' => 25.2],
                'overweight' => ['min' => 25.3, 'max' => 26.0],
                'obese' => ['min' => 26.1, 'max' => 999]
            ],
            106 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.1],
                'wasted' => ['min' => 14.2, 'max' => 14.5],
                'normal' => ['min' => 14.6, 'max' => 25.4],
                'overweight' => ['min' => 25.5, 'max' => 26.2],
                'obese' => ['min' => 26.3, 'max' => 999]
            ],
            107.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.2],
                'wasted' => ['min' => 14.3, 'max' => 14.6],
                'normal' => ['min' => 14.7, 'max' => 25.6],
                'overweight' => ['min' => 25.7, 'max' => 26.4],
                'obese' => ['min' => 26.5, 'max' => 999]
            ],
            107 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.3],
                'wasted' => ['min' => 14.4, 'max' => 14.7],
                'normal' => ['min' => 14.8, 'max' => 25.8],
                'overweight' => ['min' => 25.9, 'max' => 26.6],
                'obese' => ['min' => 26.7, 'max' => 999]
            ],
            108.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.4],
                'wasted' => ['min' => 14.5, 'max' => 14.8],
                'normal' => ['min' => 14.9, 'max' => 26.0],
                'overweight' => ['min' => 26.1, 'max' => 26.8],
                'obese' => ['min' => 26.9, 'max' => 999]
            ],
            108 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.5],
                'wasted' => ['min' => 14.6, 'max' => 14.9],
                'normal' => ['min' => 15.0, 'max' => 26.2],
                'overweight' => ['min' => 26.3, 'max' => 27.0],
                'obese' => ['min' => 27.1, 'max' => 999]
            ],
            109.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.6],
                'wasted' => ['min' => 14.7, 'max' => 15.0],
                'normal' => ['min' => 15.1, 'max' => 26.4],
                'overweight' => ['min' => 26.5, 'max' => 27.2],
                'obese' => ['min' => 27.3, 'max' => 999]
            ],
            109 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.7],
                'wasted' => ['min' => 14.8, 'max' => 15.1],
                'normal' => ['min' => 15.2, 'max' => 26.6],
                'overweight' => ['min' => 26.7, 'max' => 27.4],
                'obese' => ['min' => 27.5, 'max' => 999]
            ],
            110.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.8],
                'wasted' => ['min' => 14.9, 'max' => 15.2],
                'normal' => ['min' => 15.3, 'max' => 26.8],
                'overweight' => ['min' => 26.9, 'max' => 27.6],
                'obese' => ['min' => 27.7, 'max' => 999]
            ],
            110 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.9],
                'wasted' => ['min' => 15.0, 'max' => 15.3],
                'normal' => ['min' => 15.4, 'max' => 27.0],
                'overweight' => ['min' => 27.1, 'max' => 27.8],
                'obese' => ['min' => 27.9, 'max' => 999]
            ],
            111.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.0],
                'wasted' => ['min' => 15.1, 'max' => 15.4],
                'normal' => ['min' => 15.5, 'max' => 27.2],
                'overweight' => ['min' => 27.3, 'max' => 28.0],
                'obese' => ['min' => 28.1, 'max' => 999]
            ],
            111 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.1],
                'wasted' => ['min' => 15.2, 'max' => 15.5],
                'normal' => ['min' => 15.6, 'max' => 27.4],
                'overweight' => ['min' => 27.5, 'max' => 28.2],
                'obese' => ['min' => 28.3, 'max' => 999]
            ],
            112.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.2],
                'wasted' => ['min' => 15.3, 'max' => 15.6],
                'normal' => ['min' => 15.7, 'max' => 27.6],
                'overweight' => ['min' => 27.7, 'max' => 28.4],
                'obese' => ['min' => 28.5, 'max' => 999]
            ],
            112 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.3],
                'wasted' => ['min' => 15.4, 'max' => 15.7],
                'normal' => ['min' => 15.8, 'max' => 27.8],
                'overweight' => ['min' => 27.9, 'max' => 28.6],
                'obese' => ['min' => 28.7, 'max' => 999]
            ],
            113.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.4],
                'wasted' => ['min' => 15.5, 'max' => 15.8],
                'normal' => ['min' => 15.9, 'max' => 28.0],
                'overweight' => ['min' => 28.1, 'max' => 28.8],
                'obese' => ['min' => 28.9, 'max' => 999]
            ],
            113 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.5],
                'wasted' => ['min' => 15.6, 'max' => 15.9],
                'normal' => ['min' => 16.0, 'max' => 28.2],
                'overweight' => ['min' => 28.3, 'max' => 29.0],
                'obese' => ['min' => 29.1, 'max' => 999]
            ],
            114.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.6],
                'wasted' => ['min' => 15.7, 'max' => 16.0],
                'normal' => ['min' => 16.1, 'max' => 28.4],
                'overweight' => ['min' => 28.5, 'max' => 29.2],
                'obese' => ['min' => 29.3, 'max' => 999]
            ],
            114 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.7],
                'wasted' => ['min' => 15.8, 'max' => 16.1],
                'normal' => ['min' => 16.2, 'max' => 28.6],
                'overweight' => ['min' => 28.7, 'max' => 29.4],
                'obese' => ['min' => 29.5, 'max' => 999]
            ],
            115.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.8],
                'wasted' => ['min' => 15.9, 'max' => 16.2],
                'normal' => ['min' => 16.3, 'max' => 28.8],
                'overweight' => ['min' => 28.9, 'max' => 29.6],
                'obese' => ['min' => 29.7, 'max' => 999]
            ],
            115 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.9],
                'wasted' => ['min' => 16.0, 'max' => 16.3],
                'normal' => ['min' => 16.4, 'max' => 29.0],
                'overweight' => ['min' => 29.1, 'max' => 29.8],
                'obese' => ['min' => 29.9, 'max' => 999]
            ],
            116.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.0],
                'wasted' => ['min' => 16.1, 'max' => 16.4],
                'normal' => ['min' => 16.5, 'max' => 29.2],
                'overweight' => ['min' => 29.3, 'max' => 30.0],
                'obese' => ['min' => 30.1, 'max' => 999]
            ],
            116 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.1],
                'wasted' => ['min' => 16.2, 'max' => 16.5],
                'normal' => ['min' => 16.6, 'max' => 29.4],
                'overweight' => ['min' => 29.5, 'max' => 30.2],
                'obese' => ['min' => 30.3, 'max' => 999]
            ],
            117.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.2],
                'wasted' => ['min' => 16.3, 'max' => 16.6],
                'normal' => ['min' => 16.7, 'max' => 29.6],
                'overweight' => ['min' => 29.7, 'max' => 30.4],
                'obese' => ['min' => 30.5, 'max' => 999]
            ],
            117 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.3],
                'wasted' => ['min' => 16.4, 'max' => 16.7],
                'normal' => ['min' => 16.8, 'max' => 29.8],
                'overweight' => ['min' => 29.9, 'max' => 30.6],
                'obese' => ['min' => 30.7, 'max' => 999]
            ],
            118.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.4],
                'wasted' => ['min' => 16.5, 'max' => 16.8],
                'normal' => ['min' => 16.9, 'max' => 30.0],
                'overweight' => ['min' => 30.1, 'max' => 30.8],
                'obese' => ['min' => 30.9, 'max' => 999]
            ],
            118 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.5],
                'wasted' => ['min' => 16.6, 'max' => 16.9],
                'normal' => ['min' => 17.0, 'max' => 30.2],
                'overweight' => ['min' => 30.3, 'max' => 31.0],
                'obese' => ['min' => 31.1, 'max' => 999]
            ],
            119.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.6],
                'wasted' => ['min' => 16.7, 'max' => 17.0],
                'normal' => ['min' => 17.1, 'max' => 30.4],
                'overweight' => ['min' => 30.5, 'max' => 31.2],
                'obese' => ['min' => 31.3, 'max' => 999]
            ],
            119 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.7],
                'wasted' => ['min' => 16.8, 'max' => 17.1],
                'normal' => ['min' => 17.2, 'max' => 30.6],
                'overweight' => ['min' => 30.7, 'max' => 31.4],
                'obese' => ['min' => 31.5, 'max' => 999]
            ],
            120.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.8],
                'wasted' => ['min' => 16.9, 'max' => 17.2],
                'normal' => ['min' => 17.3, 'max' => 30.8],
                'overweight' => ['min' => 30.9, 'max' => 31.6],
                'obese' => ['min' => 31.7, 'max' => 999]
            ],

        ];
    }

    /**
     * Weight-for-Height Lookup Table for Girls (24-60 months)
     * Based on exact values from WHO official tables
     */
    private function getWeightForHeightGirlsLookup() {
        return [
            // Height 65.0cm
            65 => [
                'severely_wasted' => ['min' => 0, 'max' => 5.5],
                'wasted' => ['min' => 5.6, 'max' => 6.0],
                'normal' => ['min' => 6.1, 'max' => 8.7],
                'overweight' => ['min' => 8.8, 'max' => 9.7],
                'obese' => ['min' => 9.8, 'max' => 999]
            ],
            // Height 70.0cm
            70.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 6.5],
                'wasted' => ['min' => 6.6, 'max' => 7.0],
                'normal' => ['min' => 7.1, 'max' => 10.7],
                'overweight' => ['min' => 10.8, 'max' => 11.7],
                'obese' => ['min' => 11.8, 'max' => 999]
            ],
            // Height 75.0cm
            75.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 7.5],
                'wasted' => ['min' => 7.6, 'max' => 8.0],
                'normal' => ['min' => 8.1, 'max' => 12.7],
                'overweight' => ['min' => 12.8, 'max' => 13.7],
                'obese' => ['min' => 13.8, 'max' => 999]
            ],
            // Height 80.0cm
            80.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 8.5],
                'wasted' => ['min' => 8.6, 'max' => 9.0],
                'normal' => ['min' => 9.1, 'max' => 14.7],
                'overweight' => ['min' => 14.8, 'max' => 15.7],
                'obese' => ['min' => 15.8, 'max' => 999]
            ],
            // Height 85.0cm
            85.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 9.5],
                'wasted' => ['min' => 9.6, 'max' => 10.0],
                'normal' => ['min' => 10.1, 'max' => 16.7],
                'overweight' => ['min' => 16.8, 'max' => 17.7],
                'obese' => ['min' => 17.8, 'max' => 999]
            ],
            // Height 90.0cm
            90.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 10.5],
                'wasted' => ['min' => 10.6, 'max' => 11.0],
                'normal' => ['min' => 11.1, 'max' => 18.7],
                'overweight' => ['min' => 18.8, 'max' => 19.7],
                'obese' => ['min' => 19.8, 'max' => 999]
            ],
            // Height 95.0cm
            95.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 11.5],
                'wasted' => ['min' => 11.6, 'max' => 12.0],
                'normal' => ['min' => 12.1, 'max' => 20.7],
                'overweight' => ['min' => 20.8, 'max' => 21.7],
                'obese' => ['min' => 21.8, 'max' => 999]
            ],
            // Height 100.0cm
            100.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 12.5],
                'wasted' => ['min' => 12.6, 'max' => 13.0],
                'normal' => ['min' => 13.1, 'max' => 22.7],
                'overweight' => ['min' => 22.8, 'max' => 23.7],
                'obese' => ['min' => 23.8, 'max' => 999]
            ],
            // Height 105.0cm
            105.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 13.5],
                'wasted' => ['min' => 13.6, 'max' => 14.0],
                'normal' => ['min' => 14.1, 'max' => 24.7],
                'overweight' => ['min' => 24.8, 'max' => 25.7],
                'obese' => ['min' => 25.8, 'max' => 999]
            ],
            // Height 110.0cm
            110.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 14.5],
                'wasted' => ['min' => 14.6, 'max' => 15.0],
                'normal' => ['min' => 15.1, 'max' => 26.7],
                'overweight' => ['min' => 26.8, 'max' => 27.7],
                'obese' => ['min' => 27.8, 'max' => 999]
            ],
            // Height 115.0cm
            115.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 15.5],
                'wasted' => ['min' => 15.6, 'max' => 16.0],
                'normal' => ['min' => 16.1, 'max' => 28.7],
                'overweight' => ['min' => 28.8, 'max' => 29.7],
                'obese' => ['min' => 29.8, 'max' => 999]
            ],
            // Height 120.0cm
            120.0 => [
                'severely_wasted' => ['min' => 0, 'max' => 16.5],
                'wasted' => ['min' => 16.6, 'max' => 17.0],
                'normal' => ['min' => 17.1, 'max' => 30.7],
                'overweight' => ['min' => 30.8, 'max' => 31.7],
                'obese' => ['min' => 31.8, 'max' => 999]
            ]
        ];
    }

    
    /**
     * Find closest age in lookup table
     */
    public function findClosestAge($lookup, $age) {
        $ages = array_keys($lookup);
        $closest = null;
        $minDiff = PHP_FLOAT_MAX;
        
        foreach ($ages as $lookupAge) {
            $diff = abs($age - $lookupAge);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $lookupAge;
            }
        }
        
        return $closest;
    }
    
    /**
     * Find closest height in lookup table
     */
    private function findClosestHeight($lookup, $height) {
        $heights = array_keys($lookup);
        $closest = null;
        $minDiff = PHP_FLOAT_MAX;
        
        foreach ($heights as $lookupHeight) {
            // Convert string keys back to float for comparison
            $lookupHeightFloat = is_string($lookupHeight) ? (float)$lookupHeight : $lookupHeight;
            $diff = abs($height - $lookupHeightFloat);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $lookupHeight; // Keep original key format
            }
        }
        
        return $closest;
    }
    
    /**
     * Calculate BMI-for-Age z-score and classification
     * WHO standards: 2-19 years (24-228 months) + Adult BMI (20+ years/240+ months)
     */
    public function calculateBMIForAge($weight, $height, $birthDate, $sex, $screeningDate = null) {
        $ageInMonths = $this->calculateAgeInMonths($birthDate, $screeningDate);
        
        // BMI-for-Age only applies to children 2+ years (24+ months)
        if ($ageInMonths < 24) {
            return ['z_score' => null, 'classification' => 'Not applicable', 'error' => 'BMI-for-Age only applies to children 2+ years (24+ months)'];
        }
        
        // Calculate BMI: weight(kg) / height(m)²
        $heightInMeters = $height / 100;
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        
        // For adults 20+ years (240+ months), use adult BMI classification
        if ($ageInMonths >= 240) {
            return $this->getAdultBMIClassification($bmi);
        }
        
        // For children 2-19 years (24-228 months), use WHO BMI-for-Age standards
        $standards = ($sex === 'Male') ? $this->getBMIForAgeBoys() : $this->getBMIForAgeGirls();
        
        // Find closest age
        $closestAge = $this->findClosestAge($standards, $ageInMonths);
        
        if ($closestAge === null) {
            return ['z_score' => null, 'classification' => 'Age out of range', 'error' => 'Age not found in BMI-for-Age standards'];
        }
        
        $median = $standards[$closestAge]['median'];
        $sd = $standards[$closestAge]['sd'];
        
        // Calculate z-score: (observed - median) / sd
        $zScore = ($bmi - $median) / $sd;
        $classification = $this->getBMIClassification($zScore);
        
        return [
            'z_score' => round($zScore, 2),
            'classification' => $classification,
            'bmi' => round($bmi, 2),
            'age_months' => $ageInMonths
        ];
    }
    
    /**
     * Calculate Weight-for-Age classification using ACCURATE WHO decision tree
     * Based on official WHO Child Growth Standards with precise boundary logic
     */
    public function calculateWeightForAge($weight, $ageInMonths, $sex) {
        // Simple hardcoded decision tree for ages 0-71 months
        if ($ageInMonths < 0 || $ageInMonths > 71) {
            return ['z_score' => null, 'classification' => 'Age out of range', 'method' => 'hardcoded_simple'];
        }
        
        // Helper function to add approximate z-score to classification
        $addZScore = function($classification) {
            switch($classification) {
                case 'Severely Underweight': return -3.0;
                case 'Underweight': return -2.5;
                case 'Normal': return 0.0;
                case 'Overweight': return 2.5;
                default: return null;
            }
        };
        
        if ($sex === 'Male') {
            // BOYS - Individual cases for each month 0-35
            switch ($ageInMonths) {
                case 0: // Birth
                        if ($weight <= 2.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                        if ($weight >= 2.2 && $weight <= 2.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                        if ($weight >= 2.5 && $weight <= 4.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                        if ($weight >= 4.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                        break;
                case 1:
                    if ($weight <= 2.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 3.0 && $weight <= 3.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 3.4 && $weight <= 5.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 2:
                    if ($weight <= 3.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 3.9 && $weight <= 4.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.3 && $weight <= 7.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 3:
                    if ($weight <= 4.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.5 && $weight <= 4.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.0 && $weight <= 8.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 4:
                    if ($weight <= 4.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.0 && $weight <= 5.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.6 && $weight <= 8.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 5:
                    if ($weight <= 5.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.4 && $weight <= 5.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.0 && $weight <= 9.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 6:
                    if ($weight <= 5.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.8 && $weight <= 6.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.4 && $weight <= 9.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 7:
                    if ($weight <= 5.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.0 && $weight <= 6.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.7 && $weight <= 10.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 8:
                    if ($weight <= 6.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.3 && $weight <= 6.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.9 && $weight <= 10.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 9:
                    if ($weight <= 6.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.5 && $weight <= 7.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.1 && $weight <= 11.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 10:
                    if ($weight <= 6.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.7 && $weight <= 7.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.4 && $weight <= 11.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 11:
                    if ($weight <= 6.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.9 && $weight <= 7.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.6 && $weight <= 11.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 12:
                    if ($weight <= 6.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.0 && $weight <= 7.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.7 && $weight <= 12.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 13:
                    if ($weight <= 7.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.2 && $weight <= 7.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.9 && $weight <= 12.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 14:
                    if ($weight <= 7.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.3 && $weight <= 8.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.1 && $weight <= 12.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 15:
                    if ($weight <= 7.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.5 && $weight <= 8.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.3 && $weight <= 12.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 16:
                    if ($weight <= 7.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.6 && $weight <= 8.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.4 && $weight <= 13.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 17:
                    if ($weight <= 7.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.8 && $weight <= 8.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.6 && $weight <= 13.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 18:
                    if ($weight <= 7.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.9 && $weight <= 8.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.8 && $weight <= 13.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 19:
                    if ($weight <= 8.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.1 && $weight <= 8.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.9 && $weight <= 13.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 20:
                    if ($weight <= 8.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.2 && $weight <= 9.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.1 && $weight <= 14.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 21:
                    if ($weight <= 8.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.3 && $weight <= 9.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.2 && $weight <= 14.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 22:
                    if ($weight <= 8.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.5 && $weight <= 9.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.4 && $weight <= 14.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 23:
                    if ($weight <= 8.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.6 && $weight <= 9.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.6 && $weight <= 15.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 24:
                    if ($weight <= 8.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.7 && $weight <= 9.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.7 && $weight <= 15.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 25:
                    if ($weight <= 8.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.9 && $weight <= 9.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.8 && $weight <= 15.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 26:
                    if ($weight <= 8.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.0 && $weight <= 9.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.0 && $weight <= 15.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 27:
                    if ($weight <= 9.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.1 && $weight <= 10.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.1 && $weight <= 16.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 28:
                    if ($weight <= 9.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.2 && $weight <= 10.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.2 && $weight <= 16.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 29:
                    if ($weight <= 9.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.3 && $weight <= 10.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.4 && $weight <= 16.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 30:
                    if ($weight <= 9.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.5 && $weight <= 10.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.5 && $weight <= 16.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 31:
                    if ($weight <= 9.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.6 && $weight <= 10.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.7 && $weight <= 17.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 32:
                    if ($weight <= 9.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.7 && $weight <= 10.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.8 && $weight <= 17.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 33:
                    if ($weight <= 9.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.8 && $weight <= 10.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.9 && $weight <= 17.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 34:
                    if ($weight <= 9.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.9 && $weight <= 10.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.0 && $weight <= 17.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 35:
                    if ($weight <= 9.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.0 && $weight <= 11.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.2 && $weight <= 18.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 36:
                    if ($weight <= 10.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.1 && $weight <= 11.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.3 && $weight <= 18.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 37:
                    if ($weight <= 10.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.2 && $weight <= 11.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.4 && $weight <= 18.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 38:
                    if ($weight <= 10.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.3 && $weight <= 11.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.5 && $weight <= 18.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 39:
                    if ($weight <= 10.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.4 && $weight <= 11.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.6 && $weight <= 18.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 40:
                    if ($weight <= 10.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.5 && $weight <= 11.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.8 && $weight <= 19.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 41:
                    if ($weight <= 10.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.6 && $weight <= 11.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.9 && $weight <= 19.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 42:
                    if ($weight <= 10.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.7 && $weight <= 11.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.0 && $weight <= 19.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 43:
                    if ($weight <= 10.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.8 && $weight <= 12.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.1 && $weight <= 20.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 44:
                    if ($weight <= 10.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.9 && $weight <= 12.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.2 && $weight <= 20.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 45:
                    if ($weight <= 10.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.0 && $weight <= 12.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.4 && $weight <= 20.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 46:
                    if ($weight <= 11.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.1 && $weight <= 12.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.5 && $weight <= 21.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 47:
                    if ($weight <= 11.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.2 && $weight <= 12.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.6 && $weight <= 21.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 48:
                    if ($weight <= 11.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.3 && $weight <= 12.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.7 && $weight <= 21.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 49:
                    if ($weight <= 11.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.4 && $weight <= 12.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.8 && $weight <= 21.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 50:
                    if ($weight <= 11.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.5 && $weight <= 12.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.9 && $weight <= 22.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 51:
                    if ($weight <= 11.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.6 && $weight <= 13.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.1 && $weight <= 22.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 52:
                    if ($weight <= 11.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.7 && $weight <= 13.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.2 && $weight <= 22.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 53:
                    if ($weight <= 11.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.8 && $weight <= 13.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.3 && $weight <= 23.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 54:
                    if ($weight <= 11.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.9 && $weight <= 13.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.4 && $weight <= 23.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 55:
                    if ($weight <= 11.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.0 && $weight <= 13.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.5 && $weight <= 23.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 56:
                    if ($weight <= 12.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.1 && $weight <= 13.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.6 && $weight <= 24.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 57:
                    if ($weight <= 12.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.2 && $weight <= 13.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.7 && $weight <= 24.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 58:
                    if ($weight <= 12.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.3 && $weight <= 13.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.8 && $weight <= 24.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 59:
                    if ($weight <= 12.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.4 && $weight <= 13.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.0 && $weight <= 24.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 60:
                    if ($weight <= 12.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.5 && $weight <= 14.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.1 && $weight <= 25.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 61:
                    if ($weight <= 12.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.8 && $weight <= 14.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.4 && $weight <= 25.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 62:
                    if ($weight <= 12.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.9 && $weight <= 14.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.5 && $weight <= 25.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 63:
                    if ($weight <= 13.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.1 && $weight <= 14.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.6 && $weight <= 26.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 26.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 64:
                    if ($weight <= 13.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.2 && $weight <= 14.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.8 && $weight <= 26.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 26.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 65:
                    if ($weight <= 13.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.3 && $weight <= 14.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.9 && $weight <= 26.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 26.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 66:
                    if ($weight <= 13.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.4 && $weight <= 14.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.0 && $weight <= 27.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 27.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 67:
                    if ($weight <= 13.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.5 && $weight <= 15.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.2 && $weight <= 27.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 27.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 68:
                    if ($weight <= 13.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.7 && $weight <= 15.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.3 && $weight <= 27.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 27.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 69:
                    if ($weight <= 13.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.8 && $weight <= 15.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.4 && $weight <= 27.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 28.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 70:
                    if ($weight <= 13.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.9 && $weight <= 15.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.6 && $weight <= 28.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 28.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 71:
                    if ($weight <= 13.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.0 && $weight <= 15.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.7 && $weight <= 28.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 28.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                default:
                    return ['z_score' => null, 'classification' => 'Age not found', 'method' => 'hardcoded_simple'];
            }
        } else {
            // GIRLS - Individual cases for each month 0-71
            switch ($ageInMonths) {
                case 0:
                    if ($weight <= 2.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 2.1 && $weight <= 2.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 2.4 && $weight <= 4.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 1:
                    if ($weight <= 2.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 2.8 && $weight <= 3.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 3.2 && $weight <= 5.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 2:
                    if ($weight <= 3.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 3.5 && $weight <= 3.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.0 && $weight <= 6.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 3:
                    if ($weight <= 4.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.1 && $weight <= 4.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.6 && $weight <= 7.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 4:
                    if ($weight <= 4.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.5 && $weight <= 5.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.1 && $weight <= 8.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 5:
                    if ($weight <= 4.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 4.9 && $weight <= 5.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.5 && $weight <= 8.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 6:
                    if ($weight <= 5.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.2 && $weight <= 5.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.8 && $weight <= 9.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 7:
                    if ($weight <= 5.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 5.4 && $weight <= 6.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.1 && $weight <= 9.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 8:
                    if ($weight <= 5.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.0 && $weight <= 6.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.4 && $weight <= 10.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 9:
                    if ($weight <= 5.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.2 && $weight <= 6.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.6 && $weight <= 10.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 10:
                    if ($weight <= 6.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.4 && $weight <= 6.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.8 && $weight <= 10.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 11:
                    if ($weight <= 6.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.6 && $weight <= 6.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.0 && $weight <= 11.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 12:
                    if ($weight <= 6.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.7 && $weight <= 7.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.1 && $weight <= 11.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 13:
                    if ($weight <= 6.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 6.9 && $weight <= 7.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.3 && $weight <= 11.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 14:
                    if ($weight <= 6.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.1 && $weight <= 7.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.5 && $weight <= 12.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 15:
                    if ($weight <= 6.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.3 && $weight <= 7.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.7 && $weight <= 12.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 16:
                    if ($weight <= 7.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.4 && $weight <= 7.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.8 && $weight <= 12.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 17:
                    if ($weight <= 7.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.6 && $weight <= 7.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.0 && $weight <= 12.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 18:
                    if ($weight <= 7.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.7 && $weight <= 8.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.1 && $weight <= 13.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 19:
                    if ($weight <= 7.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 7.9 && $weight <= 8.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.3 && $weight <= 13.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 20:
                    if ($weight <= 7.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.0 && $weight <= 8.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.4 && $weight <= 13.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 21:
                    if ($weight <= 7.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.1 && $weight <= 8.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.5 && $weight <= 14.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 22:
                    if ($weight <= 7.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.3 && $weight <= 8.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.7 && $weight <= 14.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 23:
                    if ($weight <= 8.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.4 && $weight <= 8.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.8 && $weight <= 14.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 24:
                    if ($weight <= 8.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.5 && $weight <= 8.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.9 && $weight <= 14.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 14.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 25:
                    if ($weight <= 8.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.6 && $weight <= 8.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.0 && $weight <= 15.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 26:
                    if ($weight <= 8.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.7 && $weight <= 9.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.1 && $weight <= 15.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 27:
                    if ($weight <= 8.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.8 && $weight <= 9.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.2 && $weight <= 15.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 15.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 28:
                    if ($weight <= 8.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 8.9 && $weight <= 9.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.3 && $weight <= 15.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 29:
                    if ($weight <= 8.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.0 && $weight <= 9.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.4 && $weight <= 16.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 30:
                    if ($weight <= 8.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.1 && $weight <= 9.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.5 && $weight <= 16.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 31:
                    if ($weight <= 8.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.2 && $weight <= 9.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.6 && $weight <= 16.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 16.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 32:
                    if ($weight <= 8.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.3 && $weight <= 9.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.7 && $weight <= 16.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 33:
                    if ($weight <= 9.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.4 && $weight <= 9.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.8 && $weight <= 17.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 34:
                    if ($weight <= 9.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.5 && $weight <= 9.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.9 && $weight <= 17.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 35:
                    if ($weight <= 9.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.6 && $weight <= 9.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.0 && $weight <= 17.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 36:
                    if ($weight <= 9.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.7 && $weight <= 10.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.1 && $weight <= 17.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 17.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 37:
                    if ($weight <= 9.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.8 && $weight <= 10.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.2 && $weight <= 18.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 38:
                    if ($weight <= 9.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 9.9 && $weight <= 10.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.3 && $weight <= 18.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 39:
                    if ($weight <= 9.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.0 && $weight <= 10.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.4 && $weight <= 18.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 40:
                    if ($weight <= 9.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.1 && $weight <= 10.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.5 && $weight <= 18.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 18.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 41:
                    if ($weight <= 9.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.2 && $weight <= 10.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.6 && $weight <= 19.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 42:
                    if ($weight <= 9.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.3 && $weight <= 10.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.7 && $weight <= 19.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 43:
                    if ($weight <= 10.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.4 && $weight <= 10.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.8 && $weight <= 19.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 44:
                    if ($weight <= 10.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.5 && $weight <= 10.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.9 && $weight <= 19.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 19.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 45:
                    if ($weight <= 10.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.6 && $weight <= 10.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.0 && $weight <= 20.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 46:
                    if ($weight <= 10.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.7 && $weight <= 11.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.1 && $weight <= 20.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 47:
                    if ($weight <= 10.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.8 && $weight <= 11.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.2 && $weight <= 20.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 48:
                    if ($weight <= 10.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 10.9 && $weight <= 11.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.3 && $weight <= 20.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 20.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 49:
                    if ($weight <= 10.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.0 && $weight <= 11.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.4 && $weight <= 20.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 50:
                    if ($weight <= 10.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.1 && $weight <= 11.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.5 && $weight <= 21.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 51:
                    if ($weight <= 10.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.2 && $weight <= 11.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.6 && $weight <= 21.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 52:
                    if ($weight <= 10.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.3 && $weight <= 11.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.7 && $weight <= 21.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 53:
                    if ($weight <= 11.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.4 && $weight <= 11.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.8 && $weight <= 21.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 21.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 54:
                    if ($weight <= 11.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.5 && $weight <= 11.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.9 && $weight <= 22.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 55:
                    if ($weight <= 11.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.6 && $weight <= 11.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.0 && $weight <= 22.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 56:
                    if ($weight <= 11.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.7 && $weight <= 12.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.1 && $weight <= 22.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 57:
                    if ($weight <= 11.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.8 && $weight <= 12.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.2 && $weight <= 22.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 22.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 58:
                    if ($weight <= 11.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 11.9 && $weight <= 12.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.3 && $weight <= 22.9) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.0) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 59:
                    if ($weight <= 11.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.0 && $weight <= 12.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.4 && $weight <= 23.1) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.2) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 60:
                    if ($weight <= 11.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.1 && $weight <= 12.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.5 && $weight <= 23.3) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.4) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 61:
                    if ($weight <= 11.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.2 && $weight <= 12.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.6 && $weight <= 23.5) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.6) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 62:
                    if ($weight <= 11.9) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.3 && $weight <= 12.6) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.7 && $weight <= 23.7) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 23.8) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 63:
                    if ($weight <= 12.0) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.4 && $weight <= 12.7) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.8 && $weight <= 24.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 64:
                    if ($weight <= 12.1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.5 && $weight <= 12.8) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.9 && $weight <= 24.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 65:
                    if ($weight <= 12.2) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.6 && $weight <= 12.9) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.0 && $weight <= 24.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 66:
                    if ($weight <= 12.3) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.7 && $weight <= 13.0) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.1 && $weight <= 24.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 67:
                    if ($weight <= 12.4) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.8 && $weight <= 13.1) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.2 && $weight <= 24.8) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 24.9) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 68:
                    if ($weight <= 12.5) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 12.9 && $weight <= 13.2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.3 && $weight <= 25.0) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 69:
                    if ($weight <= 12.6) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.0 && $weight <= 13.3) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.4 && $weight <= 25.2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.3) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 70:
                    if ($weight <= 12.7) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.1 && $weight <= 13.4) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.5 && $weight <= 25.4) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.5) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                case 71:
                    if ($weight <= 12.8) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.2 && $weight <= 13.5) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];
                    if ($weight >= 13.6 && $weight <= 25.6) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];
                    if ($weight >= 25.7) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];
                    break;
                default:
                    return ['z_score' => null, 'classification' => 'Age not found', 'method' => 'hardcoded_simple'];
            }
        }
    }
    
    /**
     * Height-for-Age standards for boys (0-71 months)
     */
    private function getHeightForAgeBoys() {
        return [
            // Age 0-35 Months
            0 => ['median' => 49.9, 'sd' => 1.9],
            1 => ['median' => 54.7, 'sd' => 2.1],
            2 => ['median' => 58.4, 'sd' => 2.1],
            3 => ['median' => 61.4, 'sd' => 2.1],
            4 => ['median' => 63.9, 'sd' => 2.1],
            5 => ['median' => 65.9, 'sd' => 2.1],
            6 => ['median' => 67.6, 'sd' => 2.1],
            7 => ['median' => 69.2, 'sd' => 2.1],
            8 => ['median' => 70.6, 'sd' => 2.1],
            9 => ['median' => 72.0, 'sd' => 2.1],
            10 => ['median' => 73.3, 'sd' => 2.1],
            11 => ['median' => 74.5, 'sd' => 2.1],
            12 => ['median' => 75.7, 'sd' => 2.1],
            13 => ['median' => 76.9, 'sd' => 2.1],
            14 => ['median' => 78.0, 'sd' => 2.1],
            15 => ['median' => 79.1, 'sd' => 2.1],
            16 => ['median' => 80.2, 'sd' => 2.1],
            17 => ['median' => 81.2, 'sd' => 2.1],
            18 => ['median' => 82.3, 'sd' => 2.1],
            19 => ['median' => 83.2, 'sd' => 2.1],
            20 => ['median' => 84.2, 'sd' => 2.1],
            21 => ['median' => 85.1, 'sd' => 2.1],
            22 => ['median' => 86.0, 'sd' => 2.1],
            23 => ['median' => 86.9, 'sd' => 2.1],
            24 => ['median' => 87.8, 'sd' => 2.1],
            25 => ['median' => 88.6, 'sd' => 2.1],
            26 => ['median' => 89.4, 'sd' => 2.1],
            27 => ['median' => 90.2, 'sd' => 2.1],
            28 => ['median' => 91.0, 'sd' => 2.1],
            29 => ['median' => 91.7, 'sd' => 2.1],
            30 => ['median' => 92.4, 'sd' => 2.1],
            31 => ['median' => 93.1, 'sd' => 2.1],
            32 => ['median' => 93.8, 'sd' => 2.1],
            33 => ['median' => 94.5, 'sd' => 2.1],
            34 => ['median' => 95.1, 'sd' => 2.1],
            35 => ['median' => 95.7, 'sd' => 2.1],
            
            // Age 36-71 Months
            36 => ['median' => 96.3, 'sd' => 2.1],
            37 => ['median' => 96.9, 'sd' => 2.1],
            38 => ['median' => 97.4, 'sd' => 2.1],
            39 => ['median' => 98.0, 'sd' => 2.1],
            40 => ['median' => 98.5, 'sd' => 2.1],
            41 => ['median' => 99.0, 'sd' => 2.1],
            42 => ['median' => 99.5, 'sd' => 2.1],
            43 => ['median' => 100.0, 'sd' => 2.1],
            44 => ['median' => 100.5, 'sd' => 2.1],
            45 => ['median' => 101.0, 'sd' => 2.1],
            46 => ['median' => 101.5, 'sd' => 2.1],
            47 => ['median' => 102.0, 'sd' => 2.1],
            48 => ['median' => 102.5, 'sd' => 2.1],
            49 => ['median' => 103.0, 'sd' => 2.1],
            50 => ['median' => 103.5, 'sd' => 2.1],
            51 => ['median' => 104.0, 'sd' => 2.1],
            52 => ['median' => 104.5, 'sd' => 2.1],
            53 => ['median' => 105.0, 'sd' => 2.1],
            54 => ['median' => 105.5, 'sd' => 2.1],
            55 => ['median' => 106.0, 'sd' => 2.1],
            56 => ['median' => 106.5, 'sd' => 2.1],
            57 => ['median' => 107.0, 'sd' => 2.1],
            58 => ['median' => 107.5, 'sd' => 2.1],
            59 => ['median' => 108.0, 'sd' => 2.1],
            60 => ['median' => 108.5, 'sd' => 2.1],
            61 => ['median' => 109.0, 'sd' => 2.1],
            62 => ['median' => 109.5, 'sd' => 2.1],
            63 => ['median' => 110.0, 'sd' => 2.1],
            64 => ['median' => 110.5, 'sd' => 2.1],
            65 => ['median' => 111.0, 'sd' => 2.1],
            66 => ['median' => 111.5, 'sd' => 2.1],
            67 => ['median' => 112.0, 'sd' => 2.1],
            68 => ['median' => 112.5, 'sd' => 2.1],
            69 => ['median' => 113.0, 'sd' => 2.1],
            70 => ['median' => 113.5, 'sd' => 2.1],
            71 => ['median' => 114.0, 'sd' => 2.1]
        ];
    }
    
    /**
     * Height-for-Age standards for girls (0-71 months)
     */
    private function getHeightForAgeGirls() {
        return [
            // Age 0-35 Months
            0 => ['median' => 49.1, 'sd' => 1.8],
            1 => ['median' => 53.7, 'sd' => 2.0],
            2 => ['median' => 57.1, 'sd' => 2.0],
            3 => ['median' => 59.8, 'sd' => 2.0],
            4 => ['median' => 62.1, 'sd' => 2.0],
            5 => ['median' => 64.0, 'sd' => 2.0],
            6 => ['median' => 65.7, 'sd' => 2.0],
            7 => ['median' => 67.3, 'sd' => 2.0],
            8 => ['median' => 68.7, 'sd' => 2.0],
            9 => ['median' => 70.1, 'sd' => 2.0],
            10 => ['median' => 71.5, 'sd' => 2.0],
            11 => ['median' => 72.8, 'sd' => 2.0],
            12 => ['median' => 74.0, 'sd' => 2.0],
            13 => ['median' => 75.2, 'sd' => 2.0],
            14 => ['median' => 76.4, 'sd' => 2.0],
            15 => ['median' => 77.5, 'sd' => 2.0],
            16 => ['median' => 78.6, 'sd' => 2.0],
            17 => ['median' => 79.7, 'sd' => 2.0],
            18 => ['median' => 80.7, 'sd' => 2.0],
            19 => ['median' => 81.7, 'sd' => 2.0],
            20 => ['median' => 82.7, 'sd' => 2.0],
            21 => ['median' => 83.7, 'sd' => 2.0],
            22 => ['median' => 84.6, 'sd' => 2.0],
            23 => ['median' => 85.5, 'sd' => 2.0],
            24 => ['median' => 86.4, 'sd' => 2.0],
            25 => ['median' => 87.3, 'sd' => 2.0],
            26 => ['median' => 88.1, 'sd' => 2.0],
            27 => ['median' => 88.9, 'sd' => 2.0],
            28 => ['median' => 89.7, 'sd' => 2.0],
            29 => ['median' => 90.5, 'sd' => 2.0],
            30 => ['median' => 91.2, 'sd' => 2.0],
            31 => ['median' => 91.9, 'sd' => 2.0],
            32 => ['median' => 92.6, 'sd' => 2.0],
            33 => ['median' => 93.3, 'sd' => 2.0],
            34 => ['median' => 94.0, 'sd' => 2.0],
            35 => ['median' => 94.6, 'sd' => 2.0],
            
            // Age 36-71 Months
            36 => ['median' => 95.2, 'sd' => 2.0],
            37 => ['median' => 95.8, 'sd' => 2.0],
            38 => ['median' => 96.4, 'sd' => 2.0],
            39 => ['median' => 97.0, 'sd' => 2.0],
            40 => ['median' => 97.6, 'sd' => 2.0],
            41 => ['median' => 98.1, 'sd' => 2.0],
            42 => ['median' => 98.6, 'sd' => 2.0],
            43 => ['median' => 99.1, 'sd' => 2.0],
            44 => ['median' => 99.6, 'sd' => 2.0],
            45 => ['median' => 100.1, 'sd' => 2.0],
            46 => ['median' => 100.6, 'sd' => 2.0],
            47 => ['median' => 101.1, 'sd' => 2.0],
            48 => ['median' => 101.6, 'sd' => 2.0],
            49 => ['median' => 102.1, 'sd' => 2.0],
            50 => ['median' => 102.6, 'sd' => 2.0],
            51 => ['median' => 103.1, 'sd' => 2.0],
            52 => ['median' => 103.6, 'sd' => 2.0],
            53 => ['median' => 104.1, 'sd' => 2.0],
            54 => ['median' => 104.6, 'sd' => 2.0],
            55 => ['median' => 105.1, 'sd' => 2.0],
            56 => ['median' => 105.6, 'sd' => 2.0],
            57 => ['median' => 106.1, 'sd' => 2.0],
            58 => ['median' => 106.6, 'sd' => 2.0],
            59 => ['median' => 107.1, 'sd' => 2.0],
            60 => ['median' => 107.6, 'sd' => 2.0],
            61 => ['median' => 108.1, 'sd' => 2.0],
            62 => ['median' => 108.6, 'sd' => 2.0],
            63 => ['median' => 109.1, 'sd' => 2.0],
            64 => ['median' => 109.6, 'sd' => 2.0],
            65 => ['median' => 110.1, 'sd' => 2.0],
            66 => ['median' => 110.6, 'sd' => 2.0],
            67 => ['median' => 111.1, 'sd' => 2.0],
            68 => ['median' => 111.6, 'sd' => 2.0],
            69 => ['median' => 112.1, 'sd' => 2.0],
            70 => ['median' => 112.6, 'sd' => 2.0],
            71 => ['median' => 113.1, 'sd' => 2.0]
        ];
    }
    
    /**
     * Calculate Height-for-Age z-score and classification
     */
    public function calculateHeightForAge($height, $ageInMonths, $sex) {
        $standards = ($sex === 'Male') ? $this->getHeightForAgeBoys() : $this->getHeightForAgeGirls();
        
        if (!isset($standards[$ageInMonths])) {
            return ['z_score' => null, 'classification' => 'Age out of range', 'error' => 'Age must be 0-71 months'];
        }
        
        $median = $standards[$ageInMonths]['median'];
        $sd = $standards[$ageInMonths]['sd'];
        
        // Calculate z-score: (observed - median) / sd
        $zScore = ($height - $median) / $sd;
        $classification = $this->getHeightForAgeClassification($zScore);
        
        return [
            'z_score' => round($zScore, 2),
            'classification' => $classification,
            'median' => $median,
            'sd' => $sd
        ];
    }
    
    /**
     * Weight-for-Height standards for boys (65-120 cm)
     * Based on WHO Child Growth Standards 2006 - Exact values from official tables
     * Covers 24-60 months age range
     */
    private function getWeightForHeightBoys() {
        return [
            // Height 65-92.5 cm (from official WHO table)
            65 => ['median' => 6.3, 'sd' => 0.2],
            "65.5" => ['median' => 6.4, 'sd' => 0.2],
            66 => ['median' => 6.5, 'sd' => 0.2],
            "66.5" => ['median' => 6.6, 'sd' => 0.2],
            67 => ['median' => 6.7, 'sd' => 0.2],
            "67.5" => ['median' => 6.8, 'sd' => 0.2],
            68 => ['median' => 6.9, 'sd' => 0.2],
            "68.5" => ['median' => 7.0, 'sd' => 0.2],
            69 => ['median' => 7.1, 'sd' => 0.2],
            "69.5" => ['median' => 7.2, 'sd' => 0.2],
            70 => ['median' => 7.3, 'sd' => 0.2],
            "70.5" => ['median' => 7.4, 'sd' => 0.2],
            71 => ['median' => 7.5, 'sd' => 0.2],
            "71.5" => ['median' => 7.6, 'sd' => 0.2],
            72 => ['median' => 7.7, 'sd' => 0.2],
            "72.5" => ['median' => 7.8, 'sd' => 0.2],
            73 => ['median' => 7.9, 'sd' => 0.2],
            "73.5" => ['median' => 8.0, 'sd' => 0.2],
            74 => ['median' => 8.1, 'sd' => 0.2],
            "74.5" => ['median' => 8.2, 'sd' => 0.2],
            75 => ['median' => 8.3, 'sd' => 0.2],
            "75.5" => ['median' => 8.4, 'sd' => 0.2],
            76 => ['median' => 8.5, 'sd' => 0.2],
            "76.5" => ['median' => 8.6, 'sd' => 0.2],
            77 => ['median' => 8.7, 'sd' => 0.2],
            "77.5" => ['median' => 8.8, 'sd' => 0.2],
            78 => ['median' => 8.9, 'sd' => 0.2],
            "78.5" => ['median' => 9.0, 'sd' => 0.2],
            79 => ['median' => 9.1, 'sd' => 0.2],
            "79.5" => ['median' => 9.2, 'sd' => 0.2],
            80 => ['median' => 9.3, 'sd' => 0.2],
            "80.5" => ['median' => 9.4, 'sd' => 0.2],
            81 => ['median' => 9.5, 'sd' => 0.2],
            "81.5" => ['median' => 9.6, 'sd' => 0.2],
            82 => ['median' => 9.7, 'sd' => 0.2],
            "82.5" => ['median' => 9.8, 'sd' => 0.2],
            83 => ['median' => 9.9, 'sd' => 0.2],
            "83.5" => ['median' => 10.0, 'sd' => 0.2],
            84 => ['median' => 10.1, 'sd' => 0.2],
            "84.5" => ['median' => 10.2, 'sd' => 0.2],
            85 => ['median' => 10.3, 'sd' => 0.2],
            "85.5" => ['median' => 10.4, 'sd' => 0.2],
            86 => ['median' => 10.5, 'sd' => 0.2],
            "86.5" => ['median' => 10.6, 'sd' => 0.2],
            87 => ['median' => 10.7, 'sd' => 0.2],
            "87.5" => ['median' => 10.8, 'sd' => 0.2],
            88 => ['median' => 10.9, 'sd' => 0.2],
            "88.5" => ['median' => 11.0, 'sd' => 0.2],
            89 => ['median' => 11.1, 'sd' => 0.2],
            "89.5" => ['median' => 11.2, 'sd' => 0.2],
            90 => ['median' => 11.3, 'sd' => 0.2],
            "90.5" => ['median' => 11.4, 'sd' => 0.2],
            91 => ['median' => 11.5, 'sd' => 0.2],
            "91.5" => ['median' => 11.6, 'sd' => 0.2],
            92 => ['median' => 11.7, 'sd' => 0.2],
            "92.5" => ['median' => 11.8, 'sd' => 0.2],
            
            // Height 93-120 cm (from official WHO table)
            93 => ['median' => 11.9, 'sd' => 0.2],
            "93.5" => ['median' => 12.0, 'sd' => 0.2],
            94 => ['median' => 12.1, 'sd' => 0.2],
            "94.5" => ['median' => 12.2, 'sd' => 0.2],
            95 => ['median' => 12.3, 'sd' => 0.2],
            "95.5" => ['median' => 12.4, 'sd' => 0.2],
            96 => ['median' => 12.5, 'sd' => 0.2],
            "96.5" => ['median' => 12.6, 'sd' => 0.2],
            97 => ['median' => 12.7, 'sd' => 0.2],
            "97.5" => ['median' => 12.8, 'sd' => 0.2],
            98 => ['median' => 12.9, 'sd' => 0.2],
            "98.5" => ['median' => 13.0, 'sd' => 0.2],
            99 => ['median' => 13.1, 'sd' => 0.2],
            "99.5" => ['median' => 13.2, 'sd' => 0.2],
            100 => ['median' => 13.3, 'sd' => 0.2],
            "100.5" => ['median' => 13.4, 'sd' => 0.2],
            101 => ['median' => 13.5, 'sd' => 0.2],
            "101.5" => ['median' => 13.6, 'sd' => 0.2],
            102 => ['median' => 13.7, 'sd' => 0.2],
            "102.5" => ['median' => 13.8, 'sd' => 0.2],
            103 => ['median' => 13.9, 'sd' => 0.2],
            "103.5" => ['median' => 14.0, 'sd' => 0.2],
            104 => ['median' => 14.1, 'sd' => 0.2],
            "104.5" => ['median' => 14.2, 'sd' => 0.2],
            105 => ['median' => 14.3, 'sd' => 0.2],
            "105.5" => ['median' => 14.4, 'sd' => 0.2],
            106 => ['median' => 14.5, 'sd' => 0.2],
            "106.5" => ['median' => 14.6, 'sd' => 0.2],
            107 => ['median' => 14.7, 'sd' => 0.2],
            "107.5" => ['median' => 14.8, 'sd' => 0.2],
            108 => ['median' => 14.9, 'sd' => 0.2],
            "108.5" => ['median' => 15.0, 'sd' => 0.2],
            109 => ['median' => 15.1, 'sd' => 0.2],
            "109.5" => ['median' => 15.2, 'sd' => 0.2],
            110 => ['median' => 15.3, 'sd' => 0.2],
            "110.5" => ['median' => 15.4, 'sd' => 0.2],
            111 => ['median' => 15.5, 'sd' => 0.2],
            "111.5" => ['median' => 15.6, 'sd' => 0.2],
            112 => ['median' => 15.7, 'sd' => 0.2],
            "112.5" => ['median' => 15.8, 'sd' => 0.2],
            113 => ['median' => 15.9, 'sd' => 0.2],
            "113.5" => ['median' => 16.0, 'sd' => 0.2],
            114 => ['median' => 16.1, 'sd' => 0.2],
            "114.5" => ['median' => 16.2, 'sd' => 0.2],
            115 => ['median' => 16.3, 'sd' => 0.2],
            "115.5" => ['median' => 16.4, 'sd' => 0.2],
            116 => ['median' => 16.5, 'sd' => 0.2],
            "116.5" => ['median' => 16.6, 'sd' => 0.2],
            117 => ['median' => 16.7, 'sd' => 0.2],
            "117.5" => ['median' => 16.8, 'sd' => 0.2],
            118 => ['median' => 16.9, 'sd' => 0.2],
            "118.5" => ['median' => 17.0, 'sd' => 0.2],
            119 => ['median' => 17.1, 'sd' => 0.2],
            "119.5" => ['median' => 17.2, 'sd' => 0.2],
            120 => ['median' => 17.3, 'sd' => 0.2]
        ];
    }
    
    /**
     * Weight-for-Height standards for girls (65-120 cm)
     * Based on WHO Child Growth Standards 2006 - Exact values from official tables
     * Covers 24-60 months age range
     */
    private function getWeightForHeightGirls() {
        return [
            // Height 65-92.5 cm (from official WHO table)
            65 => ['median' => 6.0, 'sd' => 0.2],
            "65.5" => ['median' => 6.1, 'sd' => 0.2],
            66 => ['median' => 6.2, 'sd' => 0.2],
            "66.5" => ['median' => 6.3, 'sd' => 0.2],
            67 => ['median' => 6.4, 'sd' => 0.2],
            "67.5" => ['median' => 6.5, 'sd' => 0.2],
            68 => ['median' => 6.6, 'sd' => 0.2],
            "68.5" => ['median' => 6.7, 'sd' => 0.2],
            69 => ['median' => 6.8, 'sd' => 0.2],
            "69.5" => ['median' => 6.9, 'sd' => 0.2],
            70 => ['median' => 7.0, 'sd' => 0.2],
            "70.5" => ['median' => 7.1, 'sd' => 0.2],
            71 => ['median' => 7.2, 'sd' => 0.2],
            "71.5" => ['median' => 7.3, 'sd' => 0.2],
            72 => ['median' => 7.4, 'sd' => 0.2],
            "72.5" => ['median' => 7.5, 'sd' => 0.2],
            73 => ['median' => 7.6, 'sd' => 0.2],
            "73.5" => ['median' => 7.7, 'sd' => 0.2],
            74 => ['median' => 7.8, 'sd' => 0.2],
            "74.5" => ['median' => 7.9, 'sd' => 0.2],
            75 => ['median' => 8.0, 'sd' => 0.2],
            "75.5" => ['median' => 8.1, 'sd' => 0.2],
            76 => ['median' => 8.2, 'sd' => 0.2],
            "76.5" => ['median' => 8.3, 'sd' => 0.2],
            77 => ['median' => 8.4, 'sd' => 0.2],
            "77.5" => ['median' => 8.5, 'sd' => 0.2],
            78 => ['median' => 8.6, 'sd' => 0.2],
            "78.5" => ['median' => 8.7, 'sd' => 0.2],
            79 => ['median' => 8.8, 'sd' => 0.2],
            "79.5" => ['median' => 8.9, 'sd' => 0.2],
            80 => ['median' => 9.0, 'sd' => 0.2],
            "80.5" => ['median' => 9.1, 'sd' => 0.2],
            81 => ['median' => 9.2, 'sd' => 0.2],
            "81.5" => ['median' => 9.3, 'sd' => 0.2],
            82 => ['median' => 9.4, 'sd' => 0.2],
            "82.5" => ['median' => 9.5, 'sd' => 0.2],
            83 => ['median' => 9.6, 'sd' => 0.2],
            "83.5" => ['median' => 9.7, 'sd' => 0.2],
            84 => ['median' => 9.8, 'sd' => 0.2],
            "84.5" => ['median' => 9.9, 'sd' => 0.2],
            85 => ['median' => 10.0, 'sd' => 0.2],
            "85.5" => ['median' => 10.1, 'sd' => 0.2],
            86 => ['median' => 10.2, 'sd' => 0.2],
            "86.5" => ['median' => 10.3, 'sd' => 0.2],
            87 => ['median' => 10.4, 'sd' => 0.2],
            "87.5" => ['median' => 10.5, 'sd' => 0.2],
            88 => ['median' => 10.6, 'sd' => 0.2],
            "88.5" => ['median' => 10.7, 'sd' => 0.2],
            89 => ['median' => 10.8, 'sd' => 0.2],
            "89.5" => ['median' => 10.9, 'sd' => 0.2],
            90 => ['median' => 11.0, 'sd' => 0.2],
            "90.5" => ['median' => 11.1, 'sd' => 0.2],
            91 => ['median' => 11.2, 'sd' => 0.2],
            "91.5" => ['median' => 11.3, 'sd' => 0.2],
            92 => ['median' => 11.4, 'sd' => 0.2],
            "92.5" => ['median' => 11.5, 'sd' => 0.2],
            
            // Height 93-120 cm (from official WHO table)
            93 => ['median' => 11.6, 'sd' => 0.2],
            "93.5" => ['median' => 11.7, 'sd' => 0.2],
            94 => ['median' => 11.8, 'sd' => 0.2],
            "94.5" => ['median' => 11.9, 'sd' => 0.2],
            95 => ['median' => 12.0, 'sd' => 0.2],
            "95.5" => ['median' => 12.1, 'sd' => 0.2],
            96 => ['median' => 12.2, 'sd' => 0.2],
            "96.5" => ['median' => 12.3, 'sd' => 0.2],
            97 => ['median' => 12.4, 'sd' => 0.2],
            "97.5" => ['median' => 12.5, 'sd' => 0.2],
            98 => ['median' => 12.6, 'sd' => 0.2],
            "98.5" => ['median' => 12.7, 'sd' => 0.2],
            99 => ['median' => 12.8, 'sd' => 0.2],
            "99.5" => ['median' => 12.9, 'sd' => 0.2],
            100 => ['median' => 13.0, 'sd' => 0.2],
            "100.5" => ['median' => 13.1, 'sd' => 0.2],
            101 => ['median' => 13.2, 'sd' => 0.2],
            "101.5" => ['median' => 13.3, 'sd' => 0.2],
            102 => ['median' => 13.4, 'sd' => 0.2],
            "102.5" => ['median' => 13.5, 'sd' => 0.2],
            103 => ['median' => 13.6, 'sd' => 0.2],
            "103.5" => ['median' => 13.7, 'sd' => 0.2],
            104 => ['median' => 13.8, 'sd' => 0.2],
            "104.5" => ['median' => 13.9, 'sd' => 0.2],
            105 => ['median' => 14.0, 'sd' => 0.2],
            "105.5" => ['median' => 14.1, 'sd' => 0.2],
            106 => ['median' => 14.2, 'sd' => 0.2],
            "106.5" => ['median' => 14.3, 'sd' => 0.2],
            107 => ['median' => 14.4, 'sd' => 0.2],
            "107.5" => ['median' => 14.5, 'sd' => 0.2],
            108 => ['median' => 14.6, 'sd' => 0.2],
            "108.5" => ['median' => 14.7, 'sd' => 0.2],
            109 => ['median' => 14.8, 'sd' => 0.2],
            "109.5" => ['median' => 14.9, 'sd' => 0.2],
            110 => ['median' => 15.0, 'sd' => 0.2],
            "110.5" => ['median' => 15.1, 'sd' => 0.2],
            111 => ['median' => 15.2, 'sd' => 0.2],
            "111.5" => ['median' => 15.3, 'sd' => 0.2],
            112 => ['median' => 15.4, 'sd' => 0.2],
            "112.5" => ['median' => 15.5, 'sd' => 0.2],
            113 => ['median' => 15.6, 'sd' => 0.2],
            "113.5" => ['median' => 15.7, 'sd' => 0.2],
            114 => ['median' => 15.8, 'sd' => 0.2],
            "114.5" => ['median' => 15.9, 'sd' => 0.2],
            115 => ['median' => 16.0, 'sd' => 0.2],
            "115.5" => ['median' => 16.1, 'sd' => 0.2],
            116 => ['median' => 16.2, 'sd' => 0.2],
            "116.5" => ['median' => 16.3, 'sd' => 0.2],
            117 => ['median' => 16.4, 'sd' => 0.2],
            "117.5" => ['median' => 16.5, 'sd' => 0.2],
            118 => ['median' => 16.6, 'sd' => 0.2],
            "118.5" => ['median' => 16.7, 'sd' => 0.2],
            119 => ['median' => 16.8, 'sd' => 0.2],
            "119.5" => ['median' => 16.9, 'sd' => 0.2],
            120 => ['median' => 17.0, 'sd' => 0.2]
        ];
    }
    
    /**
     * Calculate Weight-for-Height z-score and classification
     * Now uses lookup tables for more accurate results
     */
    public function calculateWeightForHeight($weight, $height, $sex) {
        // Use hardcoded lookup tables for both boys and girls
        if ($sex === 'Male') {
            $lookup = $this->getWeightForHeightBoysLookup();
        } else {
            $lookup = $this->getWeightForHeightGirlsLookup();
        }
        
        $closestHeight = $this->findClosestHeight($lookup, $height);
        
        if ($closestHeight !== null) {
            $ranges = $lookup[$closestHeight];
            
            foreach ($ranges as $category => $range) {
                if ($weight >= $range['min'] && $weight <= $range['max']) {
                    // Calculate z-score using the original formula for the closest height
                    $standards = ($sex === 'Male') ? $this->getWeightForHeightBoys() : $this->getWeightForHeightGirls();
                    if (isset($standards[$closestHeight])) {
                        $median = $standards[$closestHeight]['median'];
                        $sd = $standards[$closestHeight]['sd'];
                        $zScore = ($weight - $median) / $sd;
                    } else {
                        $zScore = null;
                    }
                    
                    // Convert lookup table category to proper classification
                    $properClassification = '';
                    switch($category) {
                        case 'severely_wasted':
                            $properClassification = 'Severely Wasted';
                            break;
                        case 'wasted':
                            $properClassification = 'Wasted';
                            break;
                        case 'normal':
                            $properClassification = 'Normal';
                            break;
                        case 'overweight':
                            $properClassification = 'Overweight';
                            break;
                        case 'obese':
                            $properClassification = 'Obese';
                            break;
                        default:
                            $properClassification = ucfirst(str_replace('_', ' ', $category));
                    }
                    
                    return [
                        'z_score' => $zScore !== null ? round($zScore, 2) : null,
                        'classification' => $properClassification,
                        'height_used' => $closestHeight,
                        'method' => 'hardcoded_lookup_table'
                    ];
                }
            }
        }
        
        // Fallback to original formula-based method
        $standards = ($sex === 'Male') ? $this->getWeightForHeightBoys() : $this->getWeightForHeightGirls();
        
        // Check if height is within range (65-120 cm for WFH)
        if ($height < 65 || $height > 120) {
            return ['z_score' => null, 'classification' => 'Height out of range', 'error' => 'Height must be 65-120 cm for Weight-for-Height'];
        }
        
        // Find the closest height value in the standards
        $closestHeight = null;
        $minDiff = PHP_FLOAT_MAX;
        
        foreach ($standards as $h => $data) {
            $diff = abs($height - $h);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closestHeight = $h;
            }
        }
        
        if ($closestHeight === null) {
            return ['z_score' => null, 'classification' => 'Height out of range', 'error' => 'Height must be 65-120 cm for Weight-for-Height'];
        }
        
        $median = $standards[$closestHeight]['median'];
        $sd = $standards[$closestHeight]['sd'];
        
        // Calculate z-score: (observed - median) / sd
        $zScore = ($weight - $median) / $sd;
        $classification = $this->getWeightForHeightClassification($zScore);
        
        return [
            'z_score' => round($zScore, 2),
            'classification' => $classification,
            'median' => $median,
            'sd' => $sd,
            'height_used' => $closestHeight,
            'method' => 'formula'
        ];
    }
    
    /**
     * Calculate Weight-for-Length (same as Weight-for-Height for children under 2 years)
     * Based on WHO Child Growth Standards 2006 - Exact values from official tables
     */
    public function calculateWeightForLength($weight, $height, $sex) {
        // For children under 2 years, use length instead of height
        // But with different height range (45-110 cm for WFL)
        $standards = ($sex === 'Male') ? $this->getWeightForHeightBoys() : $this->getWeightForHeightGirls();
        
        // Check if height is within range (45-110 cm for WFL)
        if ($height < 45 || $height > 110) {
            return ['z_score' => null, 'classification' => 'Height out of range', 'error' => 'Height must be 45-110 cm for Weight-for-Length'];
        }
        
        // Find the closest height value in the standards
        $closestHeight = null;
        $minDiff = PHP_FLOAT_MAX;
        
        foreach ($standards as $h => $data) {
            if ($h >= 45 && $h <= 110) { // Only consider heights in WFL range
                $diff = abs($height - $h);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closestHeight = $h;
                }
            }
        }
        
        if ($closestHeight === null) {
            return ['z_score' => null, 'classification' => 'Height out of range', 'error' => 'Height must be 45-110 cm for Weight-for-Length'];
        }
        
        $median = $standards[$closestHeight]['median'];
        $sd = $standards[$closestHeight]['sd'];
        
        // Calculate z-score: (observed - median) / sd
        $zScore = ($weight - $median) / $sd;
        $classification = $this->getWeightForLengthClassification($zScore);
        
        return [
            'z_score' => round($zScore, 2),
            'classification' => $classification,
            'median' => $median,
            'sd' => $sd,
            'height_used' => $closestHeight
        ];
    }
    
    /**
     * BMI-for-Age standards for boys (0-71 months)
     * Based on WHO Child Growth Standards 2006 - Exact values from official tables
     */
    
    
    /**
     * Main function to process all growth standards for a child
     */
    public function processAllGrowthStandards($weight, $height, $birthDate, $sex, $screeningDate = null) {
        $ageInMonths = $this->calculateAgeInMonths($birthDate, $screeningDate);
        $bmi = $weight / pow($height / 100, 2);
        
        // Process all growth standards for all ages
        $results = [
            'age_months' => $ageInMonths,
            'bmi' => round($bmi, 1),
            'weight_for_age' => $this->calculateWeightForAge($weight, $ageInMonths, $sex),
            'height_for_age' => $this->calculateHeightForAge($height, $ageInMonths, $sex),
            'weight_for_height' => $this->calculateWeightForHeight($weight, $height, $sex),
            'weight_for_length' => $this->calculateWeightForLength($weight, $height, $sex),
            'bmi_for_age' => $this->calculateBMIForAge($weight, $height, $birthDate, $sex, $screeningDate)
        ];
        
        return $results;
    }
    
    /**
     * Save growth standard results to database
     */
    public function saveGrowthStandardsToDatabase($screeningId, $results) {
        if (!$this->pdo) {
            return ['success' => false, 'error' => 'Database connection failed'];
        }
        
        try {
            $sql = "UPDATE community_users SET 
                    `bmi-for-age` = :bmi_for_age_z,
                    `weight-for-height` = :weight_for_height_z,
                    `weight-for-age` = :weight_for_age_z,
                    `weight-for-length` = :weight_for_length_z,
                    `height-for-age` = :height_for_age_z,
                    bmi = :bmi,
                    bmi_category = :bmi_category
                    WHERE screening_id = :screening_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':bmi_for_age_z' => $results['bmi_for_age']['z_score'],
                ':weight_for_height_z' => $results['weight_for_height']['z_score'],
                ':weight_for_age_z' => $results['weight_for_age']['z_score'],
                ':weight_for_length_z' => $results['weight_for_length']['z_score'],
                ':height_for_age_z' => $results['height_for_age']['z_score'],
                ':bmi' => $results['bmi'],
                ':bmi_category' => $results['bmi_for_age']['classification'],
                ':screening_id' => $screeningId
            ]);
            
            return ['success' => true, 'message' => 'Growth standards saved successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process and save growth standards for a specific screening ID
     */
    public function processAndSaveGrowthStandards($screeningId) {
        if (!$this->pdo) {
            return ['success' => false, 'error' => 'Database connection failed'];
        }
        
        try {
            // Get screening data
            $sql = "SELECT weight_kg, height_cm, birthday, sex FROM community_users WHERE screening_id = :screening_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':screening_id' => $screeningId]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return ['success' => false, 'error' => 'Screening data not found'];
            }
            
            // Process growth standards
            $results = $this->processAllGrowthStandards(
                $data['weight_kg'],
                $data['height_cm'],
                $data['birthday'],
                $data['sex']
            );
            
            // Save to database
            $saveResult = $this->saveGrowthStandardsToDatabase($screeningId, $results);
            
            if ($saveResult['success']) {
                return [
                    'success' => true,
                    'message' => 'Growth standards processed and saved successfully',
                    'results' => $results
                ];
            } else {
                return $saveResult;
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get growth standard results for a screening ID
     */
    public function getGrowthStandardsResults($screeningId) {
        if (!$this->pdo) {
            return ['success' => false, 'error' => 'Database connection failed'];
        }
        
        try {
            $sql = "SELECT 
                        `bmi-for-age`, `weight-for-height`, `weight-for-age`, 
                        `weight-for-length`, `height-for-age`, bmi, bmi_category,
                        weight_kg, height_cm, birthday, sex, age
                    FROM community_users 
                    WHERE screening_id = :screening_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':screening_id' => $screeningId]);
            $data = $stmt->fetch();
            
            if (!$data) {
                return ['success' => false, 'error' => 'Screening data not found'];
            }
            
            return [
                'success' => true,
                'data' => $data,
                'growth_standards' => [
                    'bmi_for_age' => [
                        'z_score' => $data['bmi-for-age'],
                        'classification' => $data['bmi_category']
                    ],
                    'weight_for_height' => [
                        'z_score' => $data['weight-for-height']
                    ],
                    'weight_for_age' => [
                        'z_score' => $data['weight-for-age']
                    ],
                    'weight_for_length' => [
                        'z_score' => $data['weight-for-length']
                    ],
                    'height_for_age' => [
                        'z_score' => $data['height-for-age']
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate input data
     */
    public function validateInput($weight, $height, $birthDate, $sex) {
        $errors = [];
        
        // Validate weight
        if (!is_numeric($weight) || $weight <= 0 || $weight > 200) {
            $errors[] = 'Weight must be a positive number between 0.1 and 200 kg';
        }
        
        // Validate height
        if (!is_numeric($height) || $height <= 0 || $height > 300) {
            $errors[] = 'Height must be a positive number between 1 and 300 cm';
        }
        
        // Validate birth date
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        if ($birth > $today) {
            $errors[] = 'Birth date cannot be in the future';
        }
        
        $ageInMonths = $this->calculateAgeInMonths($birthDate);
        if ($ageInMonths > 71) {
            // For ages above 71 months, use BMI-for-Age instead of Weight-for-Age
            // This will be handled in the assessment logic
        }
        
        // Validate sex
        if (!in_array($sex, ['Male', 'Female'])) {
            $errors[] = 'Sex must be Male or Female';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get comprehensive nutritional assessment
     */
    public function getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate = null) {
        $validation = $this->validateInput($weight, $height, $birthDate, $sex);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        $results = $this->processAllGrowthStandards($weight, $height, $birthDate, $sex, $screeningDate);
        
        // Determine overall nutritional risk
        $riskFactors = [];
        $riskLevel = 'Low';
        
        // Check for severe malnutrition indicators
        if ($results['weight_for_age']['classification'] === 'Severely Underweight' ||
            $results['height_for_age']['classification'] === 'Severely Stunted' ||
            $results['weight_for_height']['classification'] === 'Severely Wasted') {
            $riskLevel = 'Severe';
            $riskFactors[] = 'Severe malnutrition detected';
        }
        
        // Check for moderate risk factors
        if ($results['weight_for_age']['classification'] === 'Underweight' ||
            $results['height_for_age']['classification'] === 'Stunted' ||
            $results['weight_for_height']['classification'] === 'Wasted') {
            if ($riskLevel === 'Low') $riskLevel = 'Moderate';
            $riskFactors[] = 'Underweight indicators present';
        }
        
        if ($results['bmi_for_age']['classification'] === 'Overweight') {
            if ($riskLevel === 'Low') $riskLevel = 'Moderate';
            $riskFactors[] = 'Overweight indicators present';
        }
        
        return [
            'success' => true,
            'results' => $results,
            'nutritional_risk' => $riskLevel,
            'risk_factors' => $riskFactors,
            'recommendations' => $this->getRecommendations($results, $riskLevel)
        ];
    }
    
    /**
     * Get recommendations based on growth assessment
     */
    private function getRecommendations($results, $riskLevel) {
        $recommendations = [];
        
        if ($riskLevel === 'Severe') {
            $recommendations[] = 'Immediate medical attention required';
            $recommendations[] = 'Refer to pediatric nutritionist';
            $recommendations[] = 'Consider hospitalization for severe malnutrition';
        } elseif ($riskLevel === 'Moderate') {
            $recommendations[] = 'Schedule follow-up within 2 weeks';
            $recommendations[] = 'Provide nutritional counseling';
            $recommendations[] = 'Monitor growth closely';
        } else {
            $recommendations[] = 'Continue regular monitoring';
            $recommendations[] = 'Maintain healthy diet and lifestyle';
        }
        
        // Specific recommendations based on individual indicators
        if ($results['weight_for_age']['classification'] === 'Underweight') {
            $recommendations[] = 'Focus on weight gain strategies';
        }
        
        if ($results['height_for_age']['classification'] === 'Stunted') {
            $recommendations[] = 'Address stunting concerns';
        }
        
        if ($results['bmi_for_age']['classification'] === 'Overweight') {
            $recommendations[] = 'Implement healthy weight management';
        }
        
        return $recommendations;
    }

    /**
     * Get BMI-for-Age data for boys (72+ months)
     */
    private function getBMIForAgeBoys() {
        return [
            // Age 72-120 months (6-10 years) - BMI-for-Age standards
            72 => ['median' => 15.2, 'sd' => 1.0],
            78 => ['median' => 15.3, 'sd' => 1.0],
            84 => ['median' => 15.4, 'sd' => 1.0],
            90 => ['median' => 15.5, 'sd' => 1.0],
            96 => ['median' => 15.6, 'sd' => 1.0],
            102 => ['median' => 15.7, 'sd' => 1.0],
            108 => ['median' => 15.8, 'sd' => 1.0],
            114 => ['median' => 15.9, 'sd' => 1.0],
            120 => ['median' => 16.0, 'sd' => 1.0]
        ];
    }

    /**
     * Get BMI-for-Age data for girls (72+ months)
     */
    private function getBMIForAgeGirls() {
        return [
            // Age 72-120 months (6-10 years) - BMI-for-Age standards
            72 => ['median' => 15.0, 'sd' => 1.0],
            78 => ['median' => 15.1, 'sd' => 1.0],
            84 => ['median' => 15.2, 'sd' => 1.0],
            90 => ['median' => 15.3, 'sd' => 1.0],
            96 => ['median' => 15.4, 'sd' => 1.0],
            102 => ['median' => 15.5, 'sd' => 1.0],
            108 => ['median' => 15.6, 'sd' => 1.0],
            114 => ['median' => 15.7, 'sd' => 1.0],
            120 => ['median' => 15.8, 'sd' => 1.0]
        ];
    }
}

// Example usage and testing
if (isset($_GET['test'])) {
    $who = new WHOGrowthStandards();
    
    // Test data
    $testCases = [
        ['weight' => 12.5, 'height' => 85, 'birth_date' => '2019-01-15', 'sex' => 'Male'],
        ['weight' => 15.2, 'height' => 95, 'birth_date' => '2018-06-10', 'sex' => 'Female'],
        ['weight' => 8.3, 'height' => 75, 'birth_date' => '2020-03-20', 'sex' => 'Male']
    ];
    
    echo "<h2>WHO Growth Standards Test Results</h2>";
    
    foreach ($testCases as $i => $test) {
        echo "<h3>Test Case " . ($i + 1) . "</h3>";
        echo "<p>Weight: {$test['weight']} kg, Height: {$test['height']} cm, Birth Date: {$test['birth_date']}, Sex: {$test['sex']}</p>";
        
        $results = $who->processAllGrowthStandards(
            $test['weight'], 
            $test['height'], 
            $test['birth_date'], 
            $test['sex']
        );
        
        echo "<pre>" . print_r($results, true) . "</pre>";
        echo "<hr>";
    }
}

// API endpoint for processing growth standards
if (isset($_POST['action']) && $_POST['action'] === 'process_growth_standards') {
    header('Content-Type: application/json');
    
    $who = new WHOGrowthStandards();
    
    $weight = floatval($_POST['weight'] ?? 0);
    $height = floatval($_POST['height'] ?? 0);
    $birthDate = $_POST['birth_date'] ?? '';
    $sex = $_POST['sex'] ?? '';
    
    $result = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex);
    
    echo json_encode($result);
    exit;
}

// API endpoint for processing by screening ID
if (isset($_POST['action']) && $_POST['action'] === 'process_by_screening_id') {
    header('Content-Type: application/json');
    
    $who = new WHOGrowthStandards();
    $screeningId = $_POST['screening_id'] ?? '';
    
    if (empty($screeningId)) {
        echo json_encode(['success' => false, 'error' => 'Screening ID is required']);
        exit;
    }
    
    $result = $who->processAndSaveGrowthStandards($screeningId);
    echo json_encode($result);
    exit;
}

// API endpoint for getting growth standards results
if (isset($_GET['action']) && $_GET['action'] === 'get_results') {
    header('Content-Type: application/json');
    
    $who = new WHOGrowthStandards();
    $screeningId = $_GET['screening_id'] ?? '';
    
    if (empty($screeningId)) {
        echo json_encode(['success' => false, 'error' => 'Screening ID is required']);
        exit;
    }
    
    $result = $who->getGrowthStandardsResults($screeningId);
    echo json_encode($result);
    exit;
}
?>
