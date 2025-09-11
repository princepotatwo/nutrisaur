package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.RectF;
import android.util.AttributeSet;
import android.view.View;
import android.widget.TextView;
import androidx.core.content.ContextCompat;

public class LoadingCalorieView extends View {
    private static final String TAG = "LoadingCalorieView";
    
    private Paint backgroundPaint;
    private Paint progressPaint;
    private Paint textPaint;
    private RectF progressRect;
    
    private int progress = 0;
    private int maxProgress = 100;
    private String displayText = "0";
    private boolean isLoading = false;
    
    private int backgroundColor;
    private int progressColor;
    private int textColor;
    
    public LoadingCalorieView(Context context) {
        super(context);
        init();
    }
    
    public LoadingCalorieView(Context context, AttributeSet attrs) {
        super(context, attrs);
        init();
    }
    
    public LoadingCalorieView(Context context, AttributeSet attrs, int defStyleAttr) {
        super(context, attrs, defStyleAttr);
        init();
    }
    
    private void init() {
        // Initialize colors
        backgroundColor = ContextCompat.getColor(getContext(), android.R.color.darker_gray);
        progressColor = ContextCompat.getColor(getContext(), R.color.green_primary);
        textColor = ContextCompat.getColor(getContext(), android.R.color.black);
        
        // Initialize paints
        backgroundPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        backgroundPaint.setColor(backgroundColor);
        backgroundPaint.setStyle(Paint.Style.STROKE);
        backgroundPaint.setStrokeWidth(8f);
        
        progressPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        progressPaint.setColor(progressColor);
        progressPaint.setStyle(Paint.Style.STROKE);
        progressPaint.setStrokeWidth(8f);
        progressPaint.setStrokeCap(Paint.Cap.ROUND);
        
        textPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        textPaint.setColor(textColor);
        textPaint.setTextSize(48f);
        textPaint.setTextAlign(Paint.Align.CENTER);
        textPaint.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
        
        progressRect = new RectF();
    }
    
    @Override
    protected void onSizeChanged(int w, int h, int oldw, int oldh) {
        super.onSizeChanged(w, h, oldw, oldh);
        
        // Calculate the rectangle for the circular progress
        float padding = 20f;
        float size = Math.min(w, h) - padding;
        float left = (w - size) / 2f;
        float top = (h - size) / 2f;
        progressRect.set(left, top, left + size, top + size);
    }
    
    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        
        if (isLoading) {
            drawLoadingAnimation(canvas);
        } else {
            drawProgress(canvas);
        }
    }
    
    private void drawLoadingAnimation(Canvas canvas) {
        // Draw background circle
        canvas.drawCircle(progressRect.centerX(), progressRect.centerY(), 
                         progressRect.width() / 2f, backgroundPaint);
        
        // Draw loading text
        textPaint.setTextSize(24f);
        textPaint.setColor(textColor);
        canvas.drawText("...", progressRect.centerX(), 
                       progressRect.centerY() + textPaint.getTextSize() / 3f, textPaint);
    }
    
    private void drawProgress(Canvas canvas) {
        // Draw background circle
        canvas.drawCircle(progressRect.centerX(), progressRect.centerY(), 
                         progressRect.width() / 2f, backgroundPaint);
        
        // Draw progress arc
        float sweepAngle = (float) progress / maxProgress * 360f;
        canvas.drawArc(progressRect, -90f, sweepAngle, false, progressPaint);
        
        // Draw text
        textPaint.setTextSize(48f);
        textPaint.setColor(textColor);
        canvas.drawText(displayText, progressRect.centerX(), 
                       progressRect.centerY() + textPaint.getTextSize() / 3f, textPaint);
    }
    
    public void showLoading() {
        isLoading = true;
        invalidate();
    }
    
    public void showValue(String value) {
        isLoading = false;
        displayText = value;
        try {
            progress = Integer.parseInt(value);
        } catch (NumberFormatException e) {
            progress = 0;
        }
        invalidate();
    }
    
    public void setProgress(int progress) {
        this.progress = progress;
        this.displayText = String.valueOf(progress);
        invalidate();
    }
    
    public void setMaxProgress(int maxProgress) {
        this.maxProgress = maxProgress;
        invalidate();
    }
    
    public int getProgress() {
        return progress;
    }
    
    public int getMaxProgress() {
        return maxProgress;
    }
}
