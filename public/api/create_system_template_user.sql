-- Create system template user for MHO food templates
-- This user will store all template foods that can be used by any user

INSERT INTO community_users (
    email, 
    password,
    name, 
    municipality, 
    barangay, 
    sex, 
    birthday, 
    is_pregnant, 
    weight, 
    height, 
    screening_date, 
    fcm_token, 
    is_flagged, 
    status
) VALUES (
    'system@templates.local',
    'system_template_password_2024',
    'System Templates',
    'SYSTEM',
    'TEMPLATES',
    'N/A',
    '1970-01-01',
    'N/A',
    0,
    0,
    NOW(),
    'system',
    0,
    1
) ON DUPLICATE KEY UPDATE 
    name = 'System Templates',
    municipality = 'SYSTEM',
    barangay = 'TEMPLATES';
