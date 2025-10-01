import re

# Read the file
with open('app/src/main/java/com/example/nutrisaur11/MainActivity.java', 'r') as f:
    content = f.read()

# Remove all references to deleted classes and services
replacements = [
    # Remove EventBackgroundService references
    (r'Intent eventServiceIntent = new Intent\([^;]+EventBackgroundService[^;]+;', '// EventBackgroundService removed'),
    (r'startService\(eventServiceIntent\);', '// Service removed'),
    (r'stopService\(eventServiceIntent\);', '// Service removed'),
    
    # Remove ScreeningResultStore references
    (r'ScreeningResultStore\.init\([^)]+\);', '// ScreeningResultStore removed'),
    (r'ScreeningResultStore\.getRiskScore\(\);', '0'),  # Default to 0
    (r'ScreeningResultStore\.setRiskScore\([^)]+\);', '// ScreeningResultStore removed'),
    (r'ScreeningResultStore\.getRiskScoreAsync\([^}]+}\s*\);', '// ScreeningResultStore removed'),
    
    # Remove missing layout references
    (r'findViewById\(R\.id\.apple_login\);', 'null'),
    (r'findViewById\(R\.id\.camera_switch\);', 'null'),
    (r'findViewById\(R\.id\.sync_switch\);', 'null'),
    (r'findViewById\(R\.id\.privacy_switch\);', 'null'),
    (r'findViewById\(R\.id\.logout_section\);', 'null'),
    (r'findViewById\(R\.id\.camera_button\);', 'null'),
    (r'findViewById\(R\.id\.animated_ring_view\);', 'null'),
    
    # Remove @Override annotations that don't override anything
    (r'@Override\s*\n\s*public void onResume\(\) \{', 'public void onResume() {'),
    (r'@Override\s*\n\s*public void onPause\(\) \{', 'public void onPause() {'),
]

# Apply replacements
for pattern, replacement in replacements:
    content = re.sub(pattern, replacement, content, flags=re.MULTILINE | re.DOTALL)

# Write the fixed file
with open('app/src/main/java/com/example/nutrisaur11/MainActivity.java', 'w') as f:
    f.write(content)

print("Fixed MainActivity.java")
