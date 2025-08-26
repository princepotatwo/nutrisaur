# 🚀 Railway Deployment Checklist for Nutrisaur

## ✅ Pre-Deployment Setup (COMPLETED)

- [x] Created `railway.json` configuration
- [x] Created `nixpacks.toml` for PHP runtime
- [x] Created `public/` directory structure
- [x] Copied PHP files to public directory
- [x] Created health check endpoint (`/health`)
- [x] Created main entry point (`index.php`)
- [x] Created test page (`/test`)
- [x] Updated `.gitignore` for web deployment
- [x] Created comprehensive README.md

## 🚀 Deployment Steps

### 1. Commit and Push Changes
```bash
git add .
git commit -m "Configure Railway deployment for Nutrisaur web backend"
git push origin main
```

### 2. Railway Dashboard Setup
1. Go to [Railway Dashboard](https://railway.app/dashboard)
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your `princepotatwo/nutrisaur11` repository
5. Railway will auto-detect the configuration

### 3. Environment Variables (if needed)
- `PORT`: Automatically set by Railway
- `DATABASE_URL`: If you need database connection
- Any other environment-specific variables

### 4. Monitor Deployment
- Watch the build logs for any errors
- Check that all phases complete successfully:
  - ✅ Setup (PHP + extensions)
  - ✅ Install (create directories)
  - ✅ Build (copy files)
  - ✅ Deploy (start server)

## 🔍 Post-Deployment Verification

### Health Check
- Visit: `https://your-app.railway.app/health`
- Should return JSON with status: "healthy"

### Test Page
- Visit: `https://your-app.railway.app/test`
- Should show PHP environment details
- All extensions should show ✅

### Main Application
- Visit: `https://your-app.railway.app/`
- Should load the MHO settings page

## 🚨 Troubleshooting Common Issues

### Build Failures
**Error**: "Error creating build plan with Railpack"
**Solution**: 
- Verify `railway.json` and `nixpacks.toml` are in root
- Check that PHP files exist in `sss/` directory
- Ensure no syntax errors in PHP files

### Port Binding Issues
**Error**: "Port already in use"
**Solution**: 
- Railway automatically sets `$PORT` environment variable
- Verify `nixpacks.toml` uses `$PORT` correctly

### Missing Extensions
**Error**: "Extension not loaded"
**Solution**:
- Check `nixpacks.toml` includes required extensions
- Verify extension names are correct

### File Not Found Errors
**Error**: "404 - Page Not Found"
**Solution**:
- Verify all PHP files were copied to `public/` directory
- Check file permissions
- Ensure routing logic in `index.php` is correct

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

**Need Help?** Check Railway documentation or review the build logs for specific error messages.
