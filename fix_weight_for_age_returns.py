#!/usr/bin/env python3

# Script to fix all return statements in calculateWeightForAge function to include z_score

def fix_weight_for_age_returns():
    with open('who_growth_standards.php', 'r') as f:
        content = f.read()
    
    # Find the calculateWeightForAge function
    lines = content.split('\n')
    new_lines = []
    
    in_weight_for_age = False
    brace_count = 0
    in_switch = False
    switch_brace_count = 0
    
    i = 0
    while i < len(lines):
        line = lines[i]
        
        # Check if we're entering the calculateWeightForAge function
        if 'public function calculateWeightForAge(' in line:
            in_weight_for_age = True
            new_lines.append(line)
            i += 1
            continue
        
        if in_weight_for_age:
            # Count braces to find end of function
            for char in line:
                if char == '{':
                    brace_count += 1
                elif char == '}':
                    brace_count -= 1
            
            # Check if we're in a switch statement
            if 'switch ($ageInMonths)' in line:
                in_switch = True
                new_lines.append(line)
                i += 1
                continue
            
            if in_switch:
                for char in line:
                    if char == '{':
                        switch_brace_count += 1
                    elif char == '}':
                        switch_brace_count -= 1
                
                if switch_brace_count == 0 and '}' in line:
                    in_switch = False
            
            # Fix return statements within the function
            if 'return [' in line and 'classification' in line and 'z_score' not in line:
                # This is a return statement that needs z_score added
                if 'Severely Underweight' in line:
                    line = line.replace("['classification' => 'Severely Underweight'", "['z_score' => -3.0, 'classification' => 'Severely Underweight'")
                elif 'Underweight' in line:
                    line = line.replace("['classification' => 'Underweight'", "['z_score' => -2.0, 'classification' => 'Underweight'")
                elif 'Normal' in line:
                    line = line.replace("['classification' => 'Normal'", "['z_score' => 0.0, 'classification' => 'Normal'")
                elif 'Overweight' in line:
                    line = line.replace("['classification' => 'Overweight'", "['z_score' => 2.0, 'classification' => 'Overweight'")
            
            # Check if we've reached the end of the function
            if brace_count == 0 and in_weight_for_age:
                in_weight_for_age = False
                in_switch = False
                switch_brace_count = 0
        
        new_lines.append(line)
        i += 1
    
    # Write the updated content
    with open('who_growth_standards.php', 'w') as f:
        f.write('\n'.join(new_lines))
    
    print("Weight-for-age return statements updated with z_score!")

if __name__ == "__main__":
    fix_weight_for_age_returns()
