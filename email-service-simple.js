const nodemailer = require('nodemailer');

// Configure email transporter (matching your working system)
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: 'kevinpingol123@gmail.com',
    pass: 'eoax bdlz bogm ikjk',
  },
});

// Generate 4-digit code (matching your working system)
function genCode() {
  return Math.floor(1000 + Math.random() * 9000).toString();
}

// Send verification email
async function sendVerificationEmail(email, username, verificationCode) {
  try {
    await transporter.sendMail({
      from: 'kevinpingol123@gmail.com',
      to: email,
      subject: 'Your Nutrisaur verification code',
      text: `Hello ${username}! Your verification code is: ${verificationCode}. This code will expire in 5 minutes.`,
      html: `
        <h2>Nutrisaur Account Verification</h2>
        <p>Hello ${username}!</p>
        <p>Thank you for registering with Nutrisaur. To complete your registration, please use the verification code below:</p>
        <div style="font-size: 32px; font-weight: bold; text-align: center; color: #4CAF50; padding: 20px; background: white; border: 2px solid #4CAF50; border-radius: 10px; margin: 20px 0;">
          ${verificationCode}
        </div>
        <p><strong>Important:</strong></p>
        <ul>
          <li>This code will expire in 5 minutes</li>
          <li>If you didn't request this verification, please ignore this email</li>
          <li>For security, never share this code with anyone</li>
        </ul>
        <p>If you have any questions, please contact our support team.</p>
      `
    });
    
    console.log('Email sent successfully to:', email);
    return true;
  } catch (error) {
    console.error('Email sending failed:', error);
    return false;
  }
}

// Test email function
async function testEmail() {
  const testCode = genCode();
  const result = await sendVerificationEmail('kevinpingol123@gmail.com', 'Kevin', testCode);
  console.log('Test email result:', result);
  return result;
}

// Export functions
module.exports = {
  sendVerificationEmail,
  testEmail,
  genCode
};

// If running directly, test the email
if (require.main === module) {
  testEmail()
    .then(success => {
      console.log('Email test completed:', success ? 'SUCCESS' : 'FAILED');
      process.exit(success ? 0 : 1);
    })
    .catch(error => {
      console.error('Email test error:', error);
      process.exit(1);
    });
}
