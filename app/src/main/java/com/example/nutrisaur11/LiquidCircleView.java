package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Path;
import android.util.AttributeSet;
import android.view.View;

public class LiquidCircleView extends View {
    private float fillLevel = 0.5f; // 0.0 to 1.0
    private float tilt = 0f; // in radians, -PI/4 to PI/4
    private float wavePhase = 0f;
    private Paint circlePaint;
    private Paint liquidPaint;
    private Paint borderPaint;
    private int liquidColor = Color.parseColor("#4CAF50");
    private int borderColor = Color.parseColor("#222222");
    private int bgColor = Color.WHITE;
    private Runnable animator;

    public LiquidCircleView(Context ctx, AttributeSet attrs) {
        super(ctx, attrs);
        init();
    }
    public LiquidCircleView(Context ctx) {
        super(ctx);
        init();
    }
    private void init() {
        circlePaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        circlePaint.setColor(bgColor);
        circlePaint.setStyle(Paint.Style.FILL);
        liquidPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        liquidPaint.setColor(liquidColor);
        liquidPaint.setStyle(Paint.Style.FILL);
        borderPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        borderPaint.setColor(borderColor);
        borderPaint.setStyle(Paint.Style.STROKE);
        borderPaint.setStrokeWidth(8f);
        // Animate the wave
        animator = new Runnable() {
            @Override
            public void run() {
                wavePhase += 0.08f;
                invalidate();
                postDelayed(this, 16);
            }
        };
        post(animator);
    }
    public void setFillLevel(float level) {
        fillLevel = Math.max(0f, Math.min(1f, level));
        invalidate();
    }
    public void setLiquidColor(int color) {
        liquidColor = color;
        liquidPaint.setColor(color);
        invalidate();
    }
    public void setTilt(float angle) {
        tilt = angle;
        invalidate();
    }
    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        int w = getWidth();
        int h = getHeight();
        int r = Math.min(w, h) / 2 - 8;
        int cx = w / 2;
        int cy = h / 2;
        // Draw circle background
        canvas.drawCircle(cx, cy, r, circlePaint);
        // Draw liquid
        Path wave = new Path();
        int points = 80;
        float waveHeight = r * 0.08f;
        float baseY = cy + r - 2 * r * fillLevel;
        float angle = tilt;
        wave.moveTo(cx - r, cy + r);
        for (int i = 0; i <= points; i++) {
            float x = cx - r + (2f * r * i / points);
            float rel = (x - (cx - r)) / (2f * r);
            float y = (float) (baseY + Math.sin(wavePhase + rel * 2 * Math.PI + angle) * waveHeight);
            wave.lineTo(x, y);
        }
        wave.lineTo(cx + r, cy + r);
        wave.close();
        canvas.save();
        canvas.clipRect(cx - r, cy - r, cx + r, cy + r);
        canvas.drawPath(wave, liquidPaint);
        canvas.restore();
        // Draw border
        canvas.drawCircle(cx, cy, r, borderPaint);
    }
} 