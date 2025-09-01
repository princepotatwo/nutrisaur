# 📱 Updated Screening Architecture - Mobile App + Web Dashboard

## 🎯 **New Architecture Overview**

The nutrition screening system has been restructured to follow a **mobile-first approach**:

### **📱 Mobile App (Primary Interface)**
- **ComprehensiveScreeningActivity.java** - Complete 7-section screening form
- **activity_comprehensive_screening_form.xml** - Mobile-optimized UI
- **Data Collection**: All screening inputs, validation, and calculations
- **API Integration**: Submits data to `/api/comprehensive_screening.php`

### **🌐 Web Dashboard (Results Display)**
- **`sss/screening.php`** - Assessment results and analytics dashboard
- **Data Visualization**: Summary cards, risk analysis, assessment history
- **Search & Filter**: Find specific assessments by location, risk level, etc.
- **Detailed View**: Modal popup with complete assessment details

## 🔄 **Data Flow**

```
Mobile App → API → Database → Web Dashboard
     ↓           ↓         ↓           ↓
   Screening   Process   Store     Display
   Form        Data      Results    Analytics
```

## 📊 **Web Dashboard Features**

### **Summary Cards**
- **Total Assessments**: Count of all screenings
- **High Risk Cases**: Assessments with risk score > 20
- **Average Risk Score**: Statistical overview

### **Assessment Table**
- **Date & Time**: When screening was completed
- **Location**: Municipality and barangay
- **Demographics**: Age, sex, BMI category
- **Risk Analysis**: Score and level (Low/Medium/High)
- **Actions**: View detailed assessment

### **Search & Filter**
- **Text Search**: Find by location, name, or any field
- **Risk Filter**: Filter by Low/Medium/High risk levels
- **Real-time**: Instant filtering as you type

### **Detailed Modal**
- **Complete Assessment**: All 7 sections of data
- **Risk Breakdown**: Detailed risk factor analysis
- **Recommendations**: Personalized intervention suggestions
- **Responsive Design**: Works on all screen sizes

## 🎨 **Design Consistency**

### **Theme Integration**
- **Dark/Light Mode**: Seamless theme switching
- **Color Scheme**: Matches existing NutriSaur branding
- **Typography**: Consistent font families and sizes
- **Spacing**: Unified padding and margins

### **Navigation**
- **Updated Navbar**: "Assessment Results" instead of "Nutrition Screening"
- **Active States**: Proper highlighting of current page
- **Consistent Icons**: Unified iconography across pages

## 📱 **Mobile App Integration**

### **Data Submission**
```java
// ComprehensiveScreeningActivity.java
private void submitAssessment() {
    // Collect all form data
    // Calculate BMI and risk score
    // Submit to API endpoint
    // Show success/error feedback
}
```

### **API Endpoint**
```php
// public/api/comprehensive_screening.php
POST /api/comprehensive_screening.php
{
    "municipality": "Balanga",
    "barangay": "San Jose",
    "age": 25,
    "sex": "Female",
    "weight": 65.5,
    "height": 165.0,
    "bmi": 24.0,
    "meal_recall": "Rice, fish, vegetables...",
    "family_history": ["Diabetes", "Hypertension"],
    "lifestyle": "Active",
    "risk_score": 15,
    "assessment_summary": "Normal BMI with family history...",
    "recommendations": "Monitor blood pressure..."
}
```

## 🔧 **Technical Implementation**

### **Database Schema**
```sql
CREATE TABLE screening_assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    municipality VARCHAR(255),
    barangay VARCHAR(255),
    age INT,
    age_months INT,
    sex VARCHAR(10),
    pregnant VARCHAR(20),
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    bmi DECIMAL(4,2),
    meal_recall TEXT,
    family_history JSON,
    lifestyle VARCHAR(50),
    lifestyle_other VARCHAR(255),
    immunization JSON,
    risk_score INT,
    assessment_summary TEXT,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **Risk Scoring Algorithm**
```php
function calculateRiskScore($input, $bmi) {
    $score = 0;
    
    // BMI scoring
    if ($bmi < 18.5) $score += 10; // Underweight
    elseif ($bmi >= 30) $score += 15; // Obese
    
    // Family history
    $family_history = json_decode($input['family_history'], true);
    foreach ($family_history as $condition) {
        if ($condition !== 'None') $score += 5;
    }
    
    // Lifestyle
    if ($input['lifestyle'] === 'Sedentary') $score += 10;
    
    // Meal balance (simplified)
    if (strpos($input['meal_recall'], 'unbalanced') !== false) $score += 5;
    
    return $score;
}
```

## 🚀 **Benefits of New Architecture**

### **User Experience**
- **Mobile-First**: Screening optimized for mobile devices
- **Offline Capable**: Can work without constant internet
- **Faster Input**: Touch-optimized form controls
- **Real-time Validation**: Immediate feedback on inputs

### **Data Management**
- **Centralized Storage**: All data in one database
- **Real-time Sync**: Instant updates to web dashboard
- **Backup & Recovery**: Robust data protection
- **Analytics Ready**: Structured data for reporting

### **Scalability**
- **API-Based**: Easy to add new clients (web, tablet, etc.)
- **Modular Design**: Separate concerns (input vs. display)
- **Performance**: Optimized for each platform
- **Maintenance**: Easier to update and debug

## 📋 **Next Steps**

1. **Test Mobile App**: Verify all form sections work correctly
2. **API Testing**: Ensure data submission and retrieval
3. **Dashboard Testing**: Check all display and filter features
4. **User Training**: Educate users on new workflow
5. **Performance Monitoring**: Track system usage and performance

## 🎯 **Key Advantages**

- **✅ Mobile-Optimized**: Better user experience on phones
- **✅ Real-time Analytics**: Instant insights on web dashboard
- **✅ Scalable Architecture**: Easy to extend and maintain
- **✅ Data Integrity**: Centralized storage with validation
- **✅ User-Friendly**: Intuitive interface on both platforms

This architecture provides the best of both worlds: **mobile convenience** for data collection and **web power** for analysis and management.
