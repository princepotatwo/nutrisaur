package com.example.nutrisaur11;

import android.animation.ValueAnimator;
import android.view.View;
import android.widget.TextView;
import android.widget.ProgressBar;
import android.util.Log;

/**
 * Helper class for animating nutrition data updates
 */
public class NutritionAnimationHelper {
    private static final String TAG = "NutritionAnimationHelper";
    private static final int ANIMATION_DURATION = 1000; // 1 second

    /**
     * Animate number changes in TextView
     */
    public static void animateNumberChange(TextView textView, int fromValue, int toValue) {
        if (textView == null) return;
        
        ValueAnimator animator = ValueAnimator.ofInt(fromValue, toValue);
        animator.setDuration(ANIMATION_DURATION);
        animator.addUpdateListener(animation -> {
            int currentValue = (int) animation.getAnimatedValue();
            textView.setText(String.valueOf(currentValue));
        });
        animator.start();
    }

    /**
     * Animate number changes with custom duration
     */
    public static void animateNumberChange(TextView textView, int fromValue, int toValue, int duration) {
        if (textView == null) return;
        
        ValueAnimator animator = ValueAnimator.ofInt(fromValue, toValue);
        animator.setDuration(duration);
        animator.addUpdateListener(animation -> {
            int currentValue = (int) animation.getAnimatedValue();
            textView.setText(String.valueOf(currentValue));
        });
        animator.start();
    }

    /**
     * Animate circular progress bar
     */
    public static void animateProgressBar(ProgressBar progressBar, int fromValue, int toValue) {
        if (progressBar == null) return;
        
        ValueAnimator animator = ValueAnimator.ofInt(fromValue, toValue);
        animator.setDuration(ANIMATION_DURATION);
        animator.addUpdateListener(animation -> {
            int currentValue = (int) animation.getAnimatedValue();
            progressBar.setProgress(currentValue);
        });
        animator.start();
    }

    /**
     * Animate circular progress bar with custom duration
     */
    public static void animateProgressBar(ProgressBar progressBar, int fromValue, int toValue, int duration) {
        if (progressBar == null) return;
        
        ValueAnimator animator = ValueAnimator.ofInt(fromValue, toValue);
        animator.setDuration(duration);
        animator.addUpdateListener(animation -> {
            int currentValue = (int) animation.getAnimatedValue();
            progressBar.setProgress(currentValue);
        });
        animator.start();
    }

    /**
     * Animate view visibility with fade effect
     */
    public static void animateFadeIn(View view) {
        if (view == null) return;
        
        view.setAlpha(0f);
        view.setVisibility(View.VISIBLE);
        view.animate()
            .alpha(1f)
            .setDuration(ANIMATION_DURATION)
            .start();
    }

    /**
     * Animate view visibility with fade out effect
     */
    public static void animateFadeOut(View view) {
        if (view == null) return;
        
        view.animate()
            .alpha(0f)
            .setDuration(ANIMATION_DURATION)
            .withEndAction(() -> view.setVisibility(View.GONE))
            .start();
    }

    /**
     * Animate scale effect for emphasis
     */
    public static void animateScaleEmphasis(View view) {
        if (view == null) return;
        
        view.animate()
            .scaleX(1.1f)
            .scaleY(1.1f)
            .setDuration(200)
            .withEndAction(() -> {
                view.animate()
                    .scaleX(1f)
                    .scaleY(1f)
                    .setDuration(200)
                    .start();
            })
            .start();
    }

    /**
     * Calculate progress percentage for circular progress
     */
    public static int calculateProgressPercentage(int current, int target) {
        if (target <= 0) return 0;
        return Math.min(100, Math.max(0, (current * 100) / target));
    }

    /**
     * Animate multiple text views with staggered timing
     */
    public static void animateStaggeredTextUpdates(TextView[] textViews, int[] fromValues, int[] toValues) {
        if (textViews == null || fromValues == null || toValues == null) return;
        if (textViews.length != fromValues.length || textViews.length != toValues.length) return;
        
        for (int i = 0; i < textViews.length; i++) {
            final int index = i;
            textViews[i].postDelayed(() -> {
                animateNumberChange(textViews[index], fromValues[index], toValues[index]);
            }, i * 200); // 200ms delay between each animation
        }
    }

    /**
     * Animate color change for status indicators
     */
    public static void animateColorChange(TextView textView, int fromColor, int toColor) {
        if (textView == null) return;
        
        ValueAnimator animator = ValueAnimator.ofArgb(fromColor, toColor);
        animator.setDuration(ANIMATION_DURATION);
        animator.addUpdateListener(animation -> {
            int currentColor = (int) animation.getAnimatedValue();
            textView.setTextColor(currentColor);
        });
        animator.start();
    }
}
