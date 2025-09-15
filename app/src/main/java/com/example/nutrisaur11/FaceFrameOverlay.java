package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.Path;
import android.util.AttributeSet;
import android.view.View;

public class FaceFrameOverlay extends View {
    private Paint paint;
    private int cornerLength = 40;
    private int strokeWidth = 6;

    public FaceFrameOverlay(Context context) {
        super(context);
        init();
    }

    public FaceFrameOverlay(Context context, AttributeSet attrs) {
        super(context, attrs);
        init();
    }

    public FaceFrameOverlay(Context context, AttributeSet attrs, int defStyleAttr) {
        super(context, attrs, defStyleAttr);
        init();
    }

    private void init() {
        paint = new Paint();
        paint.setColor(0xFFFFFFFF); // White color for visibility on camera
        paint.setStrokeWidth(strokeWidth);
        paint.setStyle(Paint.Style.STROKE);
        paint.setStrokeCap(Paint.Cap.ROUND);
        paint.setAntiAlias(true);
        paint.setShadowLayer(6, 0, 0, 0x80000000); // Add shadow for better visibility
    }

    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        
        int width = getWidth();
        int height = getHeight();
        
        // Calculate frame dimensions (centered, slightly larger for better visibility)
        int frameWidth = width * 4 / 5;
        int frameHeight = height * 4 / 5;
        int left = (width - frameWidth) / 2;
        int top = (height - frameHeight) / 2;
        int right = left + frameWidth;
        int bottom = top + frameHeight;
        
        // Draw L-shaped corners with better visibility
        drawCorner(canvas, left, top, cornerLength, cornerLength, true, true); // Top-left
        drawCorner(canvas, right, top, -cornerLength, cornerLength, true, true); // Top-right
        drawCorner(canvas, left, bottom, cornerLength, -cornerLength, true, true); // Bottom-left
        drawCorner(canvas, right, bottom, -cornerLength, -cornerLength, true, true); // Bottom-right
        
        // Draw center crosshair for better alignment
        int centerX = width / 2;
        int centerY = height / 2;
        int crosshairSize = 20;
        
        // Horizontal line
        canvas.drawLine(centerX - crosshairSize, centerY, centerX + crosshairSize, centerY, paint);
        // Vertical line
        canvas.drawLine(centerX, centerY - crosshairSize, centerX, centerY + crosshairSize, paint);
    }
    
    private void drawCorner(Canvas canvas, int x, int y, int lengthX, int lengthY, boolean horizontal, boolean vertical) {
        if (horizontal) {
            canvas.drawLine(x, y, x + lengthX, y, paint);
        }
        if (vertical) {
            canvas.drawLine(x, y, x, y + lengthY, paint);
        }
    }
}
