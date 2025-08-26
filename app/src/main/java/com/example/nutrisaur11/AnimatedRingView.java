package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.util.AttributeSet;
import android.view.View;

public class AnimatedRingView extends View {
    private float progress = 0f; // 0.0 to 1.0
    private float animatedProgress = 0f;
    private int ringColor = Color.parseColor("#4FC3F7");
    private int bgRingColor = Color.parseColor("#E0E7EF");
    private String label = "";
    private Paint ringPaint;
    private Paint bgPaint;
    private Paint textPaint;
    private Paint labelPaint;
    private Runnable animator;

    public AnimatedRingView(Context ctx, AttributeSet attrs) {
        super(ctx, attrs);
        init();
    }
    public AnimatedRingView(Context ctx) {
        super(ctx);
        init();
    }
    private void init() {
        ringPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        ringPaint.setStyle(Paint.Style.STROKE);
        ringPaint.setStrokeWidth(32f);
        ringPaint.setStrokeCap(Paint.Cap.ROUND);
        ringPaint.setColor(ringColor);
        bgPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        bgPaint.setStyle(Paint.Style.STROKE);
        bgPaint.setStrokeWidth(32f);
        bgPaint.setColor(bgRingColor);
        textPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        textPaint.setColor(Color.parseColor("#222222"));
        textPaint.setTextAlign(Paint.Align.CENTER);
        textPaint.setTextSize(72f);
        textPaint.setFakeBoldText(true);
        labelPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        labelPaint.setColor(Color.parseColor("#888888"));
        labelPaint.setTextAlign(Paint.Align.CENTER);
        labelPaint.setTextSize(28f);
        animator = new Runnable() {
            @Override
            public void run() {
                if (Math.abs(animatedProgress - progress) < 0.01f) {
                    animatedProgress = progress;
                    invalidate();
                    return;
                }
                animatedProgress += (progress - animatedProgress) * 0.15f;
                invalidate();
                postDelayed(this, 16);
            }
        };
    }
    public void setProgress(float percent) {
        progress = Math.max(0f, Math.min(1f, percent));
        removeCallbacks(animator);
        post(animator);
    }
    public void setRingColor(int color) {
        ringColor = color;
        ringPaint.setColor(color);
        invalidate();
    }
    public void setLabel(String labelText) {
        label = labelText;
        invalidate();
    }
    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        int w = getWidth();
        int h = getHeight();
        int r = Math.min(w, h) / 2 - 16;
        int cx = w / 2;
        int cy = h / 2;
        // Draw background ring
        canvas.drawCircle(cx, cy, r, bgPaint);
        // Draw animated progress ring
        float sweep = Math.max(0f, Math.min(animatedProgress, 1f)) * 360f; // 360deg arc
        canvas.drawArc(cx - r, cy - r, cx + r, cy + r, -90, sweep, false, ringPaint);
        // Draw percentage
        int percentValue = Math.round(Math.max(0f, Math.min(animatedProgress, 1f)) * 100);
        String percentText = String.format("%d%%", percentValue);
        Paint.FontMetrics fm = textPaint.getFontMetrics();
        float textY = cy - (fm.ascent + fm.descent) / 2;
        canvas.drawText(percentText, cx, textY - 18, textPaint);
        // Draw label
        canvas.drawText(label, cx, textY + 48, labelPaint);
    }
} 