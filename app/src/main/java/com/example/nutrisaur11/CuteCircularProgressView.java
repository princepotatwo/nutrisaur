package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.RectF;
import android.util.AttributeSet;
import android.view.View;
import android.animation.ValueAnimator;
import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;

public class CuteCircularProgressView extends View {
    private Paint backgroundPaint;
    private Paint progressPaint;
    private Paint textPaint;
    private RectF rectF;
    
    private float progress = 0f;
    private float maxProgress = 100f;
    private String centerText = "0";
    
    // Colors - Green theme matching header
    private int backgroundColor = 0xFFE5E7EB; // Light gray background
    private int progressColor = 0xFF4CAF50;   // Green progress (matches header)
    private int textColor = 0xFF1F2937;       // Dark text
    private boolean showProgress = false;     // Initially no progress color
    
    public CuteCircularProgressView(Context context) {
        super(context);
        init();
    }
    
    public CuteCircularProgressView(Context context, AttributeSet attrs) {
        super(context, attrs);
        init();
    }
    
    public CuteCircularProgressView(Context context, AttributeSet attrs, int defStyleAttr) {
        super(context, attrs, defStyleAttr);
        init();
    }
    
    private void init() {
        // Background circle paint
        backgroundPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        backgroundPaint.setColor(backgroundColor);
        backgroundPaint.setStyle(Paint.Style.STROKE);
        backgroundPaint.setStrokeWidth(40f); // 2x thicker stroke width
        backgroundPaint.setStrokeCap(Paint.Cap.ROUND);
        
        // Progress circle paint
        progressPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        progressPaint.setColor(progressColor);
        progressPaint.setStyle(Paint.Style.STROKE);
        progressPaint.setStrokeWidth(40f); // 2x thicker stroke width
        progressPaint.setStrokeCap(Paint.Cap.ROUND);
        
        // Text paint
        textPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        textPaint.setColor(textColor);
        textPaint.setTextAlign(Paint.Align.CENTER);
        textPaint.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
        
        rectF = new RectF();
        
        // Initialize with no progress
        showProgress = false;
        progress = 0f;
    }
    
    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        
        float centerX = getWidth() / 2f;
        float centerY = getHeight() / 2f;
        float radius = Math.min(centerX, centerY) - 40f; // 40dp padding for thicker stroke
        
        // Update rectF for drawing
        rectF.set(centerX - radius, centerY - radius, centerX + radius, centerY + radius);
        
        // Draw background circle
        canvas.drawCircle(centerX, centerY, radius, backgroundPaint);
        
        // Draw progress arc only if showProgress is true
        if (showProgress) {
            float sweepAngle = (progress / maxProgress) * 360f;
            canvas.drawArc(rectF, -90f, sweepAngle, false, progressPaint);
        }
        
        // Draw center text
        textPaint.setTextSize(radius * 0.4f); // Scale text with circle size
        float textY = centerY + (textPaint.descent() - textPaint.ascent()) / 2 - textPaint.descent();
        canvas.drawText(centerText, centerX, textY, textPaint);
    }
    
    public void setProgress(float progress) {
        this.progress = Math.max(0f, Math.min(progress, maxProgress));
        invalidate();
    }
    
    public void setCenterText(String text) {
        this.centerText = text;
        invalidate();
    }
    
    public String getCenterText() {
        return this.centerText;
    }
    
    public void setMaxProgress(float maxProgress) {
        this.maxProgress = maxProgress;
        invalidate();
    }
    
    public float getMaxProgress() {
        return this.maxProgress;
    }
    
    public void animateProgress(float targetProgress, String targetText) {
        // Animate progress
        ValueAnimator progressAnimator = ValueAnimator.ofFloat(progress, targetProgress);
        progressAnimator.setDuration(1000);
        progressAnimator.addUpdateListener(animation -> {
            setProgress((Float) animation.getAnimatedValue());
        });
        
        // Animate text (if different)
        if (!centerText.equals(targetText)) {
            ObjectAnimator textAnimator = ObjectAnimator.ofObject(this, "centerText", 
                new android.animation.TypeEvaluator<String>() {
                    @Override
                    public String evaluate(float fraction, String startValue, String endValue) {
                        // Simple text animation - you can enhance this
                        return fraction < 0.5f ? startValue : endValue;
                    }
                }, centerText, targetText);
            textAnimator.setDuration(500);
            textAnimator.setStartDelay(500);
            
            AnimatorSet animatorSet = new AnimatorSet();
            animatorSet.playTogether(progressAnimator, textAnimator);
            animatorSet.start();
        } else {
            progressAnimator.start();
        }
    }
    
    public void showLoading() {
        setCenterText("...");
        setProgress(0f);
        showProgress = false; // No progress color during loading
    }
    
    public void showValue(String value) {
        setCenterText(value);
        // Enable progress color when showing a value
        showProgress = true;
        // For calories left, show progress based on remaining calories vs total goal
        try {
            float numericValue = Float.parseFloat(value);
            // Calculate progress based on remaining calories vs max progress
            float progressPercent = (numericValue / maxProgress) * 100f;
            animateProgress(progressPercent, value);
        } catch (NumberFormatException e) {
            setCenterText(value);
            setProgress(50f); // Default progress
        }
    }
    
    public void showValueWithProgress(String value, float progressValue) {
        setCenterText(value);
        // Enable progress color when showing a value
        showProgress = true;
        // Use the provided progress value instead of calculating from the text
        float progressPercent = (progressValue / maxProgress) * 100f;
        animateProgress(progressPercent, value);
    }
    
    public void resetProgress() {
        showProgress = false;
        setProgress(0f);
        setCenterText("0");
        invalidate();
    }
}
