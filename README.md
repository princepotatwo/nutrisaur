# 🦕 NutriSaur - Nutrition Management System

A comprehensive nutrition management and monitoring system built with PHP, designed for healthcare professionals to track and manage nutrition data for children and communities.

## 🚀 Features

- **Dashboard Analytics** - Comprehensive nutrition statistics and charts
- **Community Management** - Barangay-based data organization
- **Nutrition Screening** - Automated risk assessment and scoring
- **Event Management** - Nutrition event notifications and tracking
- **AI Chatbot** - Intelligent nutrition assistance
- **Mobile API** - RESTful API for Android mobile app
- **Theme Support** - Light/Dark theme switching
- **Responsive Design** - Works on desktop and mobile devices

## 🏗️ Project Structure

```
thesis355/
├── config.example.php          # Configuration template (safe for GitHub)
├── config.php                  # Database configuration (NOT in GitHub)
├── unified_api.php            # Main API endpoint for mobile app
├── DEPLOYMENT_GUIDE.md        # Deployment instructions
├── .gitignore                 # Protects sensitive files
├── sss/                       # Web application
│   ├── dash.php              # Main dashboard
│   ├── home.php              # Login/registration
│   ├── settings.php          # Admin settings
│   ├── event.php             # Event management
│   ├── AI.php                # AI chatbot interface
│   └── api/                  # API endpoints
│       ├── login.php         # User authentication
│       ├── register.php      # User registration
│       └── ...               # Other API endpoints
└── README.md                  # This file
```

## ⚠️ Security Notice

**IMPORTANT**: This repository contains sensitive configuration files that are NOT committed to GitHub:

- `config.php` - Database credentials
- `*.json` - Firebase and other API keys
- `config_production.php` - Production settings

## 🛠️ Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- XAMPP/WAMP/MAMP (for local development)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/nutrisaur.git
   cd nutrisaur
   ```

2. **Configure database**
   ```bash
   cp config.example.php config.php
   # Edit config.php with your database credentials
   ```

3. **Set up database**
   - Create database `nutrisaur_db`
   - Import your database schema

4. **Configure Firebase** (if using notifications)
   - Place your Firebase admin SDK JSON in `sss/`
   - Update API endpoints as needed

5. **Set permissions**
   ```bash
   chmod 755 sss/
   chmod 644 config.php
   ```

## 🌐 Deployment

See `DEPLOYMENT_GUIDE.md` for detailed deployment instructions to InfinityFree or other hosting providers.

## 📱 Mobile App

The Android mobile app connects to this API via the `unified_api.php` endpoint. Update the API base URL in the mobile app when deploying to production.

## 🔧 Development

- **Local Development**: Use XAMPP with `http://localhost/thesis355/`
- **Production**: Update `config.php` with production database and URL
- **Testing**: All features tested and working in local environment

## 📄 License

This project is developed for academic purposes as part of a thesis project.

## 🤝 Contributing

This is an academic project, but suggestions and improvements are welcome through issues and pull requests.

## 📞 Support

For support or questions, please create an issue in this repository.

---

**Built with ❤️ for better nutrition management**
