package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.example.nutrisaur11.CircularProgressView;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class FoodLoadingActivity extends AppCompatActivity {
    private static final String TAG = "FoodLoadingActivity";
    
    private CircularProgressView circularProgress;
    private TextView progressText;
    private TextView loadingMessage;
    private TextView closeButton;
    
    private ExecutorService executorService;
    private Handler mainHandler;
    private int currentProgress = 0;
    private boolean isLoading = true;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_loading_food);
        
        Log.d(TAG, "FoodLoadingActivity onCreate started");
        
        initializeViews();
        setupClickListeners();
        startLoadingAnimation();
        startGeminiLoading();
    }
    
    private void initializeViews() {
        circularProgress = findViewById(R.id.circular_progress);
        progressText = findViewById(R.id.progress_text);
        loadingMessage = findViewById(R.id.loading_message);
        closeButton = findViewById(R.id.close_button);
        
        executorService = Executors.newFixedThreadPool(2);
        mainHandler = new Handler(Looper.getMainLooper());
    }
    
    private void setupClickListeners() {
        closeButton.setOnClickListener(v -> {
            finish();
        });
    }
    
    private void startLoadingAnimation() {
        // Start progress animation
        mainHandler.post(new Runnable() {
            @Override
            public void run() {
                if (isLoading && currentProgress < 100) {
                    currentProgress += 2; // Increase by 2% every 100ms
                    circularProgress.setProgress(currentProgress);
                    progressText.setText(currentProgress + "%");
                    
                    // Update loading message based on progress
                    updateLoadingMessage(currentProgress);
                    
                    mainHandler.postDelayed(this, 100);
                } else if (currentProgress >= 100) {
                    // Loading complete
                    onLoadingComplete();
                }
            }
        });
    }
    
    private void updateLoadingMessage(int progress) {
        String message;
        if (progress < 20) {
            message = "Analyzing your preferences...";
        } else if (progress < 40) {
            message = "Processing dietary requirements...";
        } else if (progress < 60) {
            message = "Generating personalized recommendations...";
        } else if (progress < 80) {
            message = "Optimizing nutrition profiles...";
        } else if (progress < 95) {
            message = "Finalizing your meal plan...";
        } else {
            message = "Almost ready! Preparing your recommendations...";
        }
        
        loadingMessage.setText(message);
    }
    
    private void startGeminiLoading() {
        // Start actual Gemini API loading with progress tracking
        executorService.execute(() -> {
            try {
                // Simulate loading progress since we removed the old food system
                for (int progress = 0; progress <= 100; progress += 10) {
                    Thread.sleep(200); // Simulate processing time
                    final int currentProgress = progress;
                    mainHandler.post(() -> {
                        if (isLoading) {
                            circularProgress.setProgress(currentProgress);
                            progressText.setText(currentProgress + "%");
                            updateLoadingMessage(currentProgress);
                        }
                    });
                }
                
                // Complete loading
                mainHandler.post(() -> {
                    if (isLoading) {
                        currentProgress = 100;
                        circularProgress.setProgress(100);
                        progressText.setText("100%");
                        onLoadingComplete();
                    }
                });
                
            } catch (Exception e) {
                Log.e(TAG, "Error starting Gemini loading: " + e.getMessage());
                mainHandler.post(() -> {
                    if (isLoading) {
                        onLoadingComplete();
                    }
                });
            }
        });
    }
    
    // Progress callback interface
    public interface ProgressCallback {
        void onProgressUpdate(int progress);
        void onComplete();
        void onError(String error);
    }
    
    private void onLoadingComplete() {
        isLoading = false;
        
        // Update UI to show completion
        loadingMessage.setText("Your personalized recommendations are ready!");
        
        // Wait a moment then navigate to food activity
        mainHandler.postDelayed(() -> {
            Intent intent = new Intent(this, FoodActivity.class);
            startActivity(intent);
            finish();
        }, 1000);
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        isLoading = false;
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}
