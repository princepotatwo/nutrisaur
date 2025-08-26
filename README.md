# 🦕 Nutrisaur - Malnutrition Assessment System

A comprehensive malnutrition assessment system with both Android mobile app and web backend components, now configured for Railway deployment.

## 🏗️ Project Structure

- **`app/`** - Android mobile application (Gradle project)
- **`sss/`** - PHP web backend components
- **`public/`** - Web application entry point for Railway deployment
- **`railway.json`** - Railway deployment configuration
- **`nixpacks.toml`** - PHP runtime and build configuration

## 🚀 Railway Deployment

This project is now configured for Railway deployment with the following setup:

### Prerequisites
- Railway account
- GitHub repository connected to Railway
- PHP 8.0+ support

### Configuration Files
- `railway.json` - Railway deployment configuration
- `nixpacks.toml` - PHP runtime and build configuration
- `public/.htaccess` - Apache configuration
- `public/index.php` - Main web application entry point

### Deployment Steps

1. **Connect Repository**: Link your GitHub repository to Railway
2. **Auto-Deploy**: Railway will automatically detect the configuration and deploy
3. **Environment Variables**: Set any required environment variables in Railway dashboard
4. **Database**: Configure your database connection (MySQL/PostgreSQL)

### Build Process

The deployment process:
1. Installs PHP and required extensions (mysqli, pdo, json, curl)
2. Creates `public/` directory
3. Copies PHP files to public directory
4. Starts PHP development server on Railway's port

### Health Check

The application includes a health check endpoint at `/health` that Railway uses to verify deployment success.

## 📱 Mobile App

The Android application is built with:
- Java/Kotlin
- Gradle build system
- Firebase integration
- AI-powered food recommendations

## 🌐 Web Backend

The web backend provides:
- Malnutrition assessment tools
- MHO (Malnutrition and Hunger Observatory) standards
- User management
- API endpoints for mobile app integration

## 🔧 Development

### Local Development
```bash
# Start PHP development server
cd public
php -S localhost:8000

# Or use the main entry point
php -S localhost:8000 -t public
```

### Testing
- Health check: `http://localhost:8000/health`
- Main app: `http://localhost:8000/`
- Test page: `http://localhost:8000/test`
- API endpoints: `http://localhost:8000/api`

## 📊 Features

- **WHO Standards**: Official malnutrition assessment criteria
- **Age Groups**: Support for children (6-59 months, 5-19 years) and adults (20+)
- **Risk Scoring**: 0-100 scale based on MHO standards
- **Multi-platform**: Web backend + Android mobile app
- **AI Integration**: Smart food recommendations and filtering

## 🚨 Troubleshooting

### Railway Deployment Issues
1. Check build logs for PHP extension errors
2. Verify `railway.json` configuration
3. Ensure all required files are in the repository
4. Check health check endpoint response

### Common Issues
- **Build failures**: Usually related to missing PHP extensions
- **Port binding**: Railway automatically sets `$PORT` environment variable
- **File permissions**: Ensure PHP can read/write to necessary directories

## 📱 Mobile App Integration

Your Android app can now connect to the Railway-deployed backend:

```java
// Update your API base URL in Constants.java
public static final String BASE_URL = "https://your-app.railway.app/";
```

## 🔄 Continuous Deployment

- Railway will automatically redeploy when you push to GitHub
- Monitor deployment status in Railway dashboard
- Set up notifications for deployment failures

## 📊 Monitoring

- Use Railway's built-in metrics
- Check application logs for errors
- Monitor health check endpoint response times

## 🎯 Success Criteria

Your deployment is successful when:
- [ ] Health check returns "healthy" status
- [ ] Test page shows all extensions loaded
- [ ] Main application loads without errors
- [ ] Mobile app can connect to backend
- [ ] All routes work correctly

---

**Note**: This is a dual-purpose repository containing both Android mobile app and web backend components. Railway deployment focuses on the web backend portion.

**Built with ❤️ for better nutrition management**
