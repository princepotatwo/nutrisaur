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
        paint.setShadowLayer(4, 0, 0, 0x80000000); // Add shadow for better visibility
    }

    @Override
    protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        
        int width = getWidth();
        int height = getHeight();
        
        // Calculate frame dimensions (centered)
        int frameWidth = width * 3 / 4;
        int frameHeight = height * 3 / 4;
        int left = (width - frameWidth) / 2;
        int top = (height - frameHeight) / 2;
        int right = left + frameWidth;
        int bottom = top + frameHeight;
        
        // Draw L-shaped corners
        drawCorner(canvas, left, top, cornerLength, cornerLength, true, true); // Top-left
        drawCorner(canvas, right, top, -cornerLength, cornerLength, true, true); // Top-right
        drawCorner(canvas, left, bottom, cornerLength, -cornerLength, true, true); // Bottom-left
        drawCorner(canvas, right, bottom, -cornerLength, -cornerLength, true, true); // Bottom-right
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
