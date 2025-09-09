const https = require('https');

// Test with a different email
const url = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=register_community_user';

const data = JSON.stringify({
    name: 'Test User 2',
    email: 'test2@example.com',
    password: 'testpassword123',
    municipality: 'Test Municipality 2',
    barangay: 'Test Barangay 2',
    sex: 'Female',
    birthday: '1995-05-15',
    is_pregnant: 'No',
    weight: '65',
    height: '160'
});

const options = {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length
    }
};

console.log('Testing API with different user...');
console.log('URL:', url);
console.log('Data:', data);

const req = https.request(url, options, (res) => {
    console.log(`\nResponse Status: ${res.statusCode}`);
    
    let responseData = '';
    res.on('data', (chunk) => {
        responseData += chunk;
    });
    
    res.on('end', () => {
        console.log('Response Text:', responseData);
        
        try {
            const jsonResponse = JSON.parse(responseData);
            console.log('JSON Response:', JSON.stringify(jsonResponse, null, 2));
        } catch (e) {
            console.log('Response is not valid JSON');
        }
    });
});

req.on('error', (e) => {
    console.error('Error:', e.message);
});

req.write(data);
req.end();
