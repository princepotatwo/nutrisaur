package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.RectF;
import android.util.AttributeSet;
import android.view.View;

public class CircularProgressView extends View {
    private Paint backgroundPaint;
    private Paint progressPaint;
    private RectF rectF;
    private float progress = 0f;
    private int maxProgress = 100;

    public CircularProgressView(Context context) {
        super(context);
        init();
    }

    public CircularProgressView(Context context, AttributeSet attrs) {
        super(context, attrs);
        init();
    }

    public CircularProgressView(Context context, AttributeSet attrs, int defStyleAttr) {
        super(context, attrs, defStyleAttr);
        init();
    }

    private void init() {
        backgroundPaint = new Paint();
        backgroundPaint.setColor(getContext().getResources().getColor(android.R.color.darker_gray));
        backgroundPaint.setStyle(Paint.Style.STROKE);
        backgroundPaint.setStrokeWidth(8f);
        backgroundPaint.setAntiAlias(true);

        progressPaint = new Paint();
        progressPaint.setColor(getContext().getResources().getColor(R.color.green_band));
        progressPaint.setStyle(Paint.Style.STROKE);
        progressPaint.setStrokeWidth(8f);
        progressPaint.setAntiAlias(true);
        progressPaint.setStrokeCap(Paint.Cap.ROUND);

        rectF = new RectF();
    }

    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);

        float centerX = getWidth() / 2f;
        float centerY = getHeight() / 2f;
        float radius = Math.min(centerX, centerY) - 8f;

        rectF.set(centerX - radius, centerY - radius, centerX + radius, centerY + radius);

        // Draw background circle
        canvas.drawCircle(centerX, centerY, radius, backgroundPaint);

        // Draw progress arc
        float sweepAngle = (progress / maxProgress) * 360f;
        canvas.drawArc(rectF, -90f, sweepAngle, false, progressPaint);
    }

    public void setProgress(float progress) {
        this.progress = Math.max(0, Math.min(progress, maxProgress));
        invalidate();
    }

    public float getProgress() {
        return progress;
    }

    public void setMaxProgress(int maxProgress) {
        this.maxProgress = maxProgress;
        invalidate();
    }

    public int getMaxProgress() {
        return maxProgress;
    }
}
