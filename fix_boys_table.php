<?php
// Generate correct boys' lookup table based on official WHO standards
$boysTable = [];

// Ages 0-35 months (from the image)
$ages_0_35 = [
    0 => ['severely' => 2.1, 'underweight' => [2.2, 2.4], 'normal' => [2.5, 4.4], 'overweight' => 4.5],
    1 => ['severely' => 2.9, 'underweight' => [3.0, 3.3], 'normal' => [3.4, 5.8], 'overweight' => 5.9],
    2 => ['severely' => 3.8, 'underweight' => [3.9, 4.2], 'normal' => [4.3, 7.1], 'overweight' => 7.2],
    3 => ['severely' => 4.4, 'underweight' => [4.5, 4.9], 'normal' => [5.0, 8.0], 'overweight' => 8.1],
    4 => ['severely' => 4.9, 'underweight' => [5.0, 5.5], 'normal' => [5.6, 8.7], 'overweight' => 8.8],
    5 => ['severely' => 5.3, 'underweight' => [5.4, 5.9], 'normal' => [6.0, 9.3], 'overweight' => 9.4],
    6 => ['severely' => 5.7, 'underweight' => [5.8, 6.3], 'normal' => [6.4, 9.8], 'overweight' => 9.9],
    7 => ['severely' => 5.9, 'underweight' => [6.0, 6.6], 'normal' => [6.7, 10.3], 'overweight' => 10.4],
    8 => ['severely' => 6.2, 'underweight' => [6.3, 6.8], 'normal' => [6.9, 10.7], 'overweight' => 10.8],
    9 => ['severely' => 6.4, 'underweight' => [6.5, 7.0], 'normal' => [7.1, 11.0], 'overweight' => 11.1],
    10 => ['severely' => 6.6, 'underweight' => [6.7, 7.3], 'normal' => [7.4, 11.4], 'overweight' => 11.5],
    11 => ['severely' => 6.8, 'underweight' => [6.9, 7.5], 'normal' => [7.6, 11.7], 'overweight' => 11.8],
    12 => ['severely' => 6.9, 'underweight' => [7.0, 7.6], 'normal' => [7.7, 12.0], 'overweight' => 12.1],
    13 => ['severely' => 7.1, 'underweight' => [7.2, 7.8], 'normal' => [7.9, 12.3], 'overweight' => 12.4],
    14 => ['severely' => 7.2, 'underweight' => [7.3, 8.0], 'normal' => [8.1, 12.6], 'overweight' => 12.7],
    15 => ['severely' => 7.4, 'underweight' => [7.5, 8.2], 'normal' => [8.3, 12.8], 'overweight' => 12.9],
    16 => ['severely' => 7.5, 'underweight' => [7.6, 8.3], 'normal' => [8.4, 13.1], 'overweight' => 13.2],
    17 => ['severely' => 7.7, 'underweight' => [7.8, 8.5], 'normal' => [8.6, 13.4], 'overweight' => 13.5],
    18 => ['severely' => 7.8, 'underweight' => [7.9, 8.7], 'normal' => [8.8, 13.7], 'overweight' => 13.8],
    19 => ['severely' => 8.0, 'underweight' => [8.1, 8.8], 'normal' => [8.9, 13.9], 'overweight' => 14.0],
    20 => ['severely' => 8.1, 'underweight' => [8.2, 9.0], 'normal' => [9.1, 14.2], 'overweight' => 14.3],
    21 => ['severely' => 8.2, 'underweight' => [8.3, 9.1], 'normal' => [9.2, 14.5], 'overweight' => 14.6],
    22 => ['severely' => 8.4, 'underweight' => [8.5, 9.3], 'normal' => [9.4, 14.7], 'overweight' => 14.8],
    23 => ['severely' => 8.5, 'underweight' => [8.6, 9.4], 'normal' => [9.5, 15.0], 'overweight' => 15.1],
    24 => ['severely' => 8.6, 'underweight' => [8.7, 9.6], 'normal' => [9.7, 15.3], 'overweight' => 15.4],
    25 => ['severely' => 8.8, 'underweight' => [8.9, 9.7], 'normal' => [9.8, 15.5], 'overweight' => 15.6],
    26 => ['severely' => 8.9, 'underweight' => [9.0, 9.9], 'normal' => [10.0, 15.8], 'overweight' => 15.9],
    27 => ['severely' => 9.0, 'underweight' => [9.1, 10.0], 'normal' => [10.1, 16.1], 'overweight' => 16.2],
    28 => ['severely' => 9.1, 'underweight' => [9.2, 10.1], 'normal' => [10.2, 16.3], 'overweight' => 16.4],
    29 => ['severely' => 9.2, 'underweight' => [9.3, 10.3], 'normal' => [10.4, 16.6], 'overweight' => 16.7],
    30 => ['severely' => 9.4, 'underweight' => [9.5, 10.4], 'normal' => [10.5, 16.9], 'overweight' => 17.0],
    31 => ['severely' => 9.5, 'underweight' => [9.6, 10.6], 'normal' => [10.7, 17.1], 'overweight' => 17.2],
    32 => ['severely' => 9.6, 'underweight' => [9.7, 10.7], 'normal' => [10.8, 17.4], 'overweight' => 17.5],
    33 => ['severely' => 9.7, 'underweight' => [9.8, 10.8], 'normal' => [10.9, 17.6], 'overweight' => 17.7],
    34 => ['severely' => 9.8, 'underweight' => [9.9, 10.9], 'normal' => [11.0, 17.8], 'overweight' => 17.9],
    35 => ['severely' => 9.9, 'underweight' => [10.0, 11.1], 'normal' => [11.2, 18.1], 'overweight' => 18.2]
];

// Ages 36-71 months (from the image)
$ages_36_71 = [
    36 => ['severely' => 10.0, 'underweight' => [10.1, 11.2], 'normal' => [11.3, 18.4], 'overweight' => 18.5],
    37 => ['severely' => 10.1, 'underweight' => [10.2, 11.3], 'normal' => [11.4, 18.6], 'overweight' => 18.7],
    38 => ['severely' => 10.2, 'underweight' => [10.3, 11.4], 'normal' => [11.5, 18.8], 'overweight' => 18.9],
    39 => ['severely' => 10.3, 'underweight' => [10.4, 11.5], 'normal' => [11.6, 19.0], 'overweight' => 19.1],
    40 => ['severely' => 10.4, 'underweight' => [10.5, 11.7], 'normal' => [11.8, 19.2], 'overweight' => 19.3],
    41 => ['severely' => 10.5, 'underweight' => [10.6, 11.8], 'normal' => [11.9, 19.4], 'overweight' => 19.5],
    42 => ['severely' => 10.6, 'underweight' => [10.7, 11.9], 'normal' => [12.0, 19.6], 'overweight' => 19.7],
    43 => ['severely' => 10.7, 'underweight' => [10.8, 12.0], 'normal' => [12.1, 19.8], 'overweight' => 19.9],
    44 => ['severely' => 10.8, 'underweight' => [10.9, 12.1], 'normal' => [12.2, 20.0], 'overweight' => 20.1],
    45 => ['severely' => 10.9, 'underweight' => [11.0, 12.3], 'normal' => [12.4, 20.2], 'overweight' => 20.3],
    46 => ['severely' => 11.0, 'underweight' => [11.1, 12.4], 'normal' => [12.5, 20.4], 'overweight' => 20.5],
    47 => ['severely' => 11.1, 'underweight' => [11.2, 12.5], 'normal' => [12.6, 20.6], 'overweight' => 20.7],
    48 => ['severely' => 11.2, 'underweight' => [11.3, 12.6], 'normal' => [12.7, 20.8], 'overweight' => 20.9],
    49 => ['severely' => 11.3, 'underweight' => [11.4, 12.7], 'normal' => [12.8, 21.0], 'overweight' => 21.1],
    50 => ['severely' => 11.4, 'underweight' => [11.5, 12.8], 'normal' => [12.9, 21.2], 'overweight' => 21.3],
    51 => ['severely' => 11.5, 'underweight' => [11.6, 13.0], 'normal' => [13.1, 21.4], 'overweight' => 21.5],
    52 => ['severely' => 11.6, 'underweight' => [11.7, 13.1], 'normal' => [13.2, 21.6], 'overweight' => 21.7],
    53 => ['severely' => 11.7, 'underweight' => [11.8, 13.2], 'normal' => [13.3, 21.8], 'overweight' => 21.9],
    54 => ['severely' => 11.8, 'underweight' => [11.9, 13.3], 'normal' => [13.4, 22.0], 'overweight' => 22.1],
    55 => ['severely' => 11.9, 'underweight' => [12.0, 13.4], 'normal' => [13.5, 22.2], 'overweight' => 22.3],
    56 => ['severely' => 12.0, 'underweight' => [12.1, 13.5], 'normal' => [13.6, 22.4], 'overweight' => 22.5],
    57 => ['severely' => 12.1, 'underweight' => [12.2, 13.6], 'normal' => [13.7, 22.6], 'overweight' => 22.7],
    58 => ['severely' => 12.2, 'underweight' => [12.3, 13.7], 'normal' => [13.8, 22.8], 'overweight' => 22.9],
    59 => ['severely' => 12.3, 'underweight' => [12.4, 13.9], 'normal' => [14.0, 23.0], 'overweight' => 23.1],
    60 => ['severely' => 12.4, 'underweight' => [12.5, 14.0], 'normal' => [14.1, 23.2], 'overweight' => 23.3],
    61 => ['severely' => 12.7, 'underweight' => [12.8, 14.3], 'normal' => [14.4, 23.5], 'overweight' => 23.6],
    62 => ['severely' => 12.8, 'underweight' => [12.9, 14.4], 'normal' => [14.5, 23.7], 'overweight' => 23.8],
    63 => ['severely' => 13.0, 'underweight' => [13.1, 14.5], 'normal' => [14.6, 23.9], 'overweight' => 24.0],
    64 => ['severely' => 13.1, 'underweight' => [13.2, 14.7], 'normal' => [14.8, 24.1], 'overweight' => 24.2],
    65 => ['severely' => 13.2, 'underweight' => [13.3, 14.8], 'normal' => [14.9, 24.3], 'overweight' => 24.4],
    66 => ['severely' => 13.3, 'underweight' => [13.4, 14.9], 'normal' => [15.0, 24.5], 'overweight' => 24.6],
    67 => ['severely' => 13.4, 'underweight' => [13.5, 15.1], 'normal' => [15.2, 24.7], 'overweight' => 24.8],
    68 => ['severely' => 13.6, 'underweight' => [13.7, 15.2], 'normal' => [15.3, 24.9], 'overweight' => 25.0],
    69 => ['severely' => 13.7, 'underweight' => [13.8, 15.3], 'normal' => [15.4, 25.1], 'overweight' => 25.2],
    70 => ['severely' => 13.8, 'underweight' => [13.9, 15.5], 'normal' => [15.6, 25.3], 'overweight' => 25.4],
    71 => ['severely' => 13.9, 'underweight' => [14.0, 15.6], 'normal' => [15.7, 25.5], 'overweight' => 25.6]
];

// Combine all ages
$allAges = array_merge($ages_0_35, $ages_36_71);

// Generate PHP code
echo "    public function getWeightForAgeBoysLookupTable() {\n";
echo "        return [\n";

foreach ($allAges as $age => $data) {
    echo "            // Age $age months\n";
    echo "            $age => [\n";
    echo "                'severely_underweight' => ['min' => 0, 'max' => {$data['severely']}],\n";
    echo "                'underweight' => ['min' => {$data['underweight'][0]}, 'max' => {$data['underweight'][1]}],\n";
    echo "                'normal' => ['min' => {$data['normal'][0]}, 'max' => {$data['normal'][1]}],\n";
    echo "                'overweight' => ['min' => {$data['overweight']}, 'max' => 999]\n";
    echo "            ],\n";
}

echo "        ];\n";
echo "    }\n";
?>
