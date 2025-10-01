// Google OAuth Configuration
// This file contains the Google OAuth client configuration for web applications

const GOOGLE_OAUTH_CONFIG = {
    // You'll need to get these from Google Cloud Console
    clientId: '43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com',
    redirectUri: window.location.origin + '/home.php',
    scope: 'openid email profile',
    responseType: 'code',
    state: 'google_oauth_state'
};

// Google OAuth utility functions
class GoogleOAuth {
    constructor() {
        this.isLoaded = false;
        this.loadGoogleAPI();
    }

    async loadGoogleAPI() {
        try {
            // Load Google Identity Services library
            const script = document.createElement('script');
            script.src = 'https://accounts.google.com/gsi/client';
            script.async = true;
            script.defer = true;
            script.onload = () => {
                this.isLoaded = true;
                this.initializeGoogleSignIn();
            };
            document.head.appendChild(script);
        } catch (error) {
            console.error('Failed to load Google API:', error);
        }
    }

    initializeGoogleSignIn() {
        if (typeof google !== 'undefined' && google.accounts) {
            google.accounts.id.initialize({
                client_id: GOOGLE_OAUTH_CONFIG.clientId,
                callback: this.handleGoogleResponse.bind(this),
                auto_select: false,
                cancel_on_tap_outside: true,
                // Use popup mode for better modal experience
                ux_mode: 'popup'
            });

            console.log('Google Sign-In initialized successfully');
            
            // Render the sign-in button
            this.renderSignInButton();
        } else {
            console.error('Google accounts API not available');
        }
    }

    renderSignInButton() {
        // Find all Google sign-in buttons and render them
        const googleButtons = document.querySelectorAll('.google-btn');
        googleButtons.forEach(button => {
            // Clear existing content
            button.innerHTML = '';
            
            // Create a div for Google's button
            const googleButtonDiv = document.createElement('div');
            googleButtonDiv.id = 'google-signin-' + Math.random().toString(36).substr(2, 9);
            
            // Add the Google icon
            const icon = document.createElement('div');
            icon.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 10px;">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            `;
            
            // Add text
            const text = document.createElement('span');
            text.textContent = button.dataset.mode === 'register' ? 'Sign up with Google' : 'Sign in with Google';
            
            // Style the button
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.style.cursor = 'pointer';
            
            button.appendChild(icon);
            button.appendChild(text);
            
            // Add click handler
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.triggerGoogleSignIn();
            });
        });
    }

    triggerGoogleSignIn() {
        if (this.isLoaded && typeof google !== 'undefined' && google.accounts) {
            // Use Google's popup method which shows as a modal
            google.accounts.oauth2.initCodeClient({
                client_id: GOOGLE_OAUTH_CONFIG.clientId,
                scope: GOOGLE_OAUTH_CONFIG.scope,
                ux_mode: 'popup',
                callback: (response) => {
                    console.log('Google OAuth response received');
                    this.exchangeCodeForToken(response.code);
                }
            }).then((client) => {
                client.requestCode();
            }).catch((error) => {
                console.error('Google OAuth error:', error);
                this.showMessage('Google sign-in failed. Please try again.', 'error');
            });
        } else {
            console.error('Google API not loaded');
            this.showGoogleSignInPopup();
        }
    }

    showGoogleSignInPopup() {
        // Fallback method using popup
        const authUrl = this.buildAuthUrl();
        const popup = window.open(
            authUrl,
            'googleSignIn',
            'width=500,height=600,scrollbars=yes,resizable=yes'
        );
        
        // Listen for popup completion
        const checkClosed = setInterval(() => {
            if (popup.closed) {
                clearInterval(checkClosed);
                // The popup was closed, check if we have the auth code
                this.checkForAuthCode();
            }
        }, 1000);
    }

    buildAuthUrl() {
        const params = new URLSearchParams({
            client_id: GOOGLE_OAUTH_CONFIG.clientId,
            redirect_uri: GOOGLE_OAUTH_CONFIG.redirectUri,
            scope: GOOGLE_OAUTH_CONFIG.scope,
            response_type: GOOGLE_OAUTH_CONFIG.responseType,
            state: GOOGLE_OAUTH_CONFIG.state,
            access_type: 'offline',
            prompt: 'select_account'
        });
        
        return `https://accounts.google.com/o/oauth2/v2/auth?${params.toString()}`;
    }

    async handleGoogleResponse(response) {
        try {
            console.log('Google response received:', response);
            
            // Decode the JWT token
            const payload = this.decodeJWT(response.credential);
            console.log('Decoded payload:', payload);
            
            // Send to backend for verification and user creation/login
            await this.processGoogleUser(payload);
            
        } catch (error) {
            console.error('Error handling Google response:', error);
            this.showMessage('Google sign-in failed. Please try again.', 'error');
        }
    }

    decodeJWT(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch (error) {
            console.error('Error decoding JWT:', error);
            throw error;
        }
    }

    async processGoogleUser(userData) {
        try {
            const formData = new FormData();
            formData.append('google_oauth', 'true');
            formData.append('google_id', userData.sub);
            formData.append('email', userData.email);
            formData.append('name', userData.name);
            formData.append('picture', userData.picture);
            formData.append('given_name', userData.given_name || '');
            formData.append('family_name', userData.family_name || '');
            formData.append('email_verified', userData.email_verified ? '1' : '0');
            
            const response = await fetch('/home.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage('Google sign-in successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = '/dash';
                }, 1000);
            } else {
                this.showMessage(result.message || 'Google sign-in failed. Please try again.', 'error');
            }
            
        } catch (error) {
            console.error('Error processing Google user:', error);
            this.showMessage('An error occurred during Google sign-in. Please try again.', 'error');
        }
    }

    checkForAuthCode() {
        // Check URL parameters for auth code
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        const state = urlParams.get('state');
        
        if (code && state === GOOGLE_OAUTH_CONFIG.state) {
            this.exchangeCodeForToken(code);
        }
    }

    async exchangeCodeForToken(code) {
        try {
            const formData = new FormData();
            formData.append('google_oauth_code', 'true');
            formData.append('code', code);
            
            const response = await fetch('/home.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage('Google sign-in successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = '/dash';
                }, 1000);
            } else {
                this.showMessage(result.message || 'Google sign-in failed. Please try again.', 'error');
            }
            
        } catch (error) {
            console.error('Error exchanging code for token:', error);
            this.showMessage('An error occurred during Google sign-in. Please try again.', 'error');
        }
    }

    showMessage(message, type) {
        const messageDiv = document.getElementById('message');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';
        }
    }
}

// Initialize Google OAuth when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we have Google buttons on the page
    if (document.querySelector('.google-btn')) {
        window.googleOAuth = new GoogleOAuth();
    }
});

// Export for use in other scripts
window.GoogleOAuth = GoogleOAuth;
