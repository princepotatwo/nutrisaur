const https = require('https');

// Test the save_screening API endpoint
const url = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=save_screening';

const data = JSON.stringify({
    email: 'test@example.com',
    municipality: 'CITY OF BALANGA',
    barangay: 'Central',
    sex: 'Male',
    birthday: '1990-01-01',
    is_pregnant: 'No',
    weight: '70',
    height: '175'
});

const options = {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length
    }
};

console.log('Testing save_screening API endpoint...');
console.log('URL:', url);
console.log('Data:', data);

const req = https.request(url, options, (res) => {
    console.log(`\nResponse Status: ${res.statusCode}`);
    console.log('Response Headers:', res.headers);
    
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
