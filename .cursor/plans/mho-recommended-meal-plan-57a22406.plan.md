<!-- 57a22406-8446-418d-8071-d83270ca8faf e5f5680a-4b9a-4b82-b509-e0621297c0fe -->
# MHO Recommended Meal Plan Implementation

## Overview

Add a button in the Android app to display MHO-recommended foods, and add ability for MHO staff to add recommended foods through the web interface.

## Database Changes

### Modify `user_food_history` table

Add a new nullable column to distinguish recommended foods from regular food history:

- Column: `is_mho_recommended` (TINYINT, nullable, default NULL)
- NULL or 0 = regular food history
- 1 = MHO recommended food

SQL migration:

```sql
ALTER TABLE user_food_history 
ADD COLUMN is_mho_recommended TINYINT NULL DEFAULT NULL;
```

## Backend Changes (Web Interface)

### 1. Modify `nutrisaur11/public/screening.php`

#### Add "Add MHO Recommended Food" button

- Add button in the food history modal header (next to user name)
- Button text: "Add MHO Recommended Food"
- Styling: Similar to existing action buttons (green/blue color scheme)

#### Create new JavaScript function `addMHORecommendedFood(userEmail, userName)`

- Opens a form modal to add recommended food
- Form fields:
  - Meal category dropdown (Breakfast/Lunch/Dinner/Snacks)
  - Food name (text input)
  - Serving size (text input)
  - Calories (number input)
  - Protein (number input)
  - Carbs (number input)
  - Fat (number input)
  - Fiber (number input, optional)
- On submit: calls API with `is_mho_recommended=1` and `date='recommended'`

### 2. Modify `nutrisaur11/public/api/food_history_api.php`

#### Update `handleAddFood()` function

- Accept optional `is_mho_recommended` parameter
- If `is_mho_recommended=1`, set the column value to 1
- Keep all other logic the same (duplicate checking, insertion)

#### Add new action `get_recommended_foods`

- New function `handleGetRecommendedFoods($pdo)`
- SQL: `SELECT * FROM user_food_history WHERE user_email = ? AND is_mho_recommended = 1`
- Returns JSON array of recommended foods grouped by meal category

## Android App Changes

### 1. Modify `nutrisaur11/app/src/main/res/layout/activity_food.xml`

Add new CardView button after the "Edit Personalization" card:

```xml
<androidx.cardview.widget.CardView
    android:id="@+id/mho_recommended_card"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_marginBottom="12dp"
    app:cardCornerRadius="16dp"
    app:cardElevation="8dp"
    app:cardBackgroundColor="@color/green_band">
    
    <!-- Icon + Text layout similar to edit_personalization_card -->
    <!-- Icon: @drawable/ic_recommend or @drawable/ic_favorite -->
    <!-- Title: "MHO Recommended Meal Plan" -->
    <!-- Description: "View personalized food recommendations" -->
</androidx.cardview.widget.CardView>
```

### 2. Modify `nutrisaur11/app/src/main/java/com/example/nutrisaur11/FoodActivity.java`

#### Add view reference

```java
private androidx.cardview.widget.CardView mhoRecommendedCard;
```

#### Initialize view in `initializeViews()`

```java
mhoRecommendedCard = findViewById(R.id.mho_recommended_card);
```

#### Add click listener in `setupClickListeners()`

```java
mhoRecommendedCard.setOnClickListener(v -> showMHORecommendedFoods());
```

#### Create new method `showMHORecommendedFoods()`

- Get current user email from SharedPreferences
- Call `ApiClient.getMHORecommendedFoods(userEmail, callback)`
- On success: launch `FoodLoggingActivity` with special intent extra `"category" = "MHO Recommended"`
- Pass the food data as parcelable extras

### 3. Modify `nutrisaur11/app/src/main/java/com/example/nutrisaur11/ApiClient.java`

#### Add new method `getMHORecommendedFoods()`

```java
public void getMHORecommendedFoods(String userEmail, Callback callback) {
    String url = BASE_URL + "api/food_history_api.php?action=get_recommended_foods&user_email=" + userEmail;
    // Make GET request and return response via callback
}
```

### 4. Modify `nutrisaur11/app/src/main/java/com/example/nutrisaur11/FoodLoggingActivity.java`

#### Update to handle "MHO Recommended" category

- Check if intent has extra `"category" = "MHO Recommended"`
- If yes:
  - Set title to "MHO Recommended Meal Plan"
  - Parse food data from intent extras
  - Display in FoodItemAdapter (grouped by meal category)
  - Allow users to add foods to their meal plan (same as other categories)

## Testing Checklist

1. Database migration runs successfully
2. MHO staff can add recommended foods via web interface
3. Recommended foods appear in database with `is_mho_recommended=1`
4. Android app shows "MHO Recommended Meal Plan" button
5. Clicking button fetches and displays recommended foods
6. Users can add recommended foods to their meal plan
7. Regular food history is unaffected by changes
8. Food deletion still works correctly

### To-dos

- [ ] Add is_mho_recommended column to user_food_history table
- [ ] Add 'Add MHO Recommended Food' button in screening.php food history modal
- [ ] Create form modal for adding recommended foods in screening.php
- [ ] Modify food_history_api.php to handle is_mho_recommended parameter
- [ ] Add get_recommended_foods action to food_history_api.php
- [ ] Add MHO Recommended button card in activity_food.xml
- [ ] Add view reference, initialization, and click listener in FoodActivity.java
- [ ] Create showMHORecommendedFoods() method in FoodActivity.java
- [ ] Add getMHORecommendedFoods() method in ApiClient.java
- [ ] Update FoodLoggingActivity.java to handle MHO Recommended category
- [ ] Test end-to-end functionality: web adding and Android viewing/adding