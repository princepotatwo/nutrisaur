const nodemailer = require('nodemailer');

// Email configuration
const emailConfig = {
    host: 'smtp.gmail.com',
    port: 587,
    secure: false,
    auth: {
        user: 'kevinpingol123@gmail.com',
        pass: 'eoax bdlz bogm ikjk'
    }
};

// Create transporter
const transporter = nodemailer.createTransporter(emailConfig);

// Email template
const createVerificationEmail = (username, verificationCode) => {
    return `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Verify Your Account</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .verification-code { 
                font-size: 32px; 
                font-weight: bold; 
                text-align: center; 
                color: #4CAF50; 
                padding: 20px; 
                background: white; 
                border: 2px solid #4CAF50; 
                border-radius: 10px; 
                margin: 20px 0; 
            }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Nutrisaur Account Verification</h1>
            </div>
            <div class="content">
                <h2>Hello ${username}!</h2>
                <p>Thank you for registering with Nutrisaur. To complete your registration, please use the verification code below:</p>
                
                <div class="verification-code">${verificationCode}</div>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This code will expire in 5 minutes</li>
                    <li>If you didn't request this verification, please ignore this email</li>
                    <li>For security, never share this code with anyone</li>
                </ul>
                
                <p>If you have any questions, please contact our support team.</p>
            </div>
            <div class="footer">
                <p>&copy; 2025 Nutrisaur. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    `;
};

// Send verification email
const sendVerificationEmail = async (to, username, verificationCode) => {
    try {
        const mailOptions = {
            from: 'kevinpingol123@gmail.com',
            to: to,
            subject: 'Verify Your Nutrisaur Account',
            html: createVerificationEmail(username, verificationCode),
            text: `Hello ${username}! Your verification code is: ${verificationCode}. This code will expire in 5 minutes.`
        };

        const info = await transporter.sendMail(mailOptions);
        console.log('Email sent successfully:', info.messageId);
        return true;
    } catch (error) {
        console.error('Email sending failed:', error);
        return false;
    }
};

// Export for use in other files
module.exports = {
    sendVerificationEmail
};

// If running directly, test the email service
if (require.main === module) {
    sendVerificationEmail('test@example.com', 'TestUser', '1234')
        .then(success => {
            console.log('Email test result:', success);
        })
        .catch(error => {
            console.error('Email test failed:', error);
        });
}
