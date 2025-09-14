package com.example.nutrisaur11;

import android.Manifest;
import android.content.pm.PackageManager;
import android.graphics.SurfaceTexture;
import android.hardware.camera2.CameraAccessException;
import android.hardware.camera2.CameraCaptureSession;
import android.hardware.camera2.CameraCharacteristics;
import android.hardware.camera2.CameraDevice;
import android.hardware.camera2.CameraManager;
import android.hardware.camera2.CaptureRequest;
import android.os.Bundle;
import android.os.Handler;
import android.os.HandlerThread;
import android.os.Looper;
import android.util.Log;
import android.util.Size;
import android.view.Surface;
import android.view.TextureView;
import android.view.View;
import android.widget.Toast;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import java.util.Arrays;

public class FullScreenCameraActivity extends AppCompatActivity {
    private static final String TAG = "FullScreenCamera";
    private static final int CAMERA_PERMISSION_REQUEST = 1001;
    
    private TextureView textureView;
    private ImageView backButton;
    private TextView holdStillText;
    private TextView instructionText;
    private ProgressBar progressBar;
    
    private CameraDevice cameraDevice;
    private CameraCaptureSession captureSession;
    private CaptureRequest.Builder captureRequestBuilder;
    private Handler backgroundHandler;
    private Handler mainHandler;
    
    private String cameraId;
    private Size previewSize;
    private boolean isScanning = false;
    private int scanProgress = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_full_screen_camera);
        
        // Hide status bar and navigation bar for full screen
        hideSystemUI();
        
        initializeViews();
        setupClickListeners();
        checkCameraPermission();
    }
    
    private void hideSystemUI() {
        View decorView = getWindow().getDecorView();
        int uiOptions = View.SYSTEM_UI_FLAG_FULLSCREEN
                | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                | View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY;
        decorView.setSystemUiVisibility(uiOptions);
    }
    
    private void initializeViews() {
        textureView = findViewById(R.id.texture_view);
        backButton = findViewById(R.id.back_button);
        holdStillText = findViewById(R.id.hold_still_text);
        instructionText = findViewById(R.id.instruction_text);
        progressBar = findViewById(R.id.progress_bar);
        
        mainHandler = new Handler(Looper.getMainLooper());
        
        // Initialize background handler for camera operations
        HandlerThread backgroundThread = new HandlerThread("CameraBackground");
        backgroundThread.start();
        backgroundHandler = new Handler(backgroundThread.getLooper());
        
        // Set up texture view listener
        textureView.setSurfaceTextureListener(textureListener);
    }
    
    private void setupClickListeners() {
        backButton.setOnClickListener(v -> {
            finish();
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        });
    }
    
    private void checkCameraPermission() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) 
                != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(this, 
                new String[]{Manifest.permission.CAMERA}, CAMERA_PERMISSION_REQUEST);
        } else {
            openCamera();
        }
    }
    
    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, 
                                         @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == CAMERA_PERMISSION_REQUEST) {
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                openCamera();
            } else {
                finish();
            }
        }
    }
    
    private void openCamera() {
        // Ultra-simple approach - just wait longer and try once
        mainHandler.postDelayed(() -> {
            try {
                CameraManager cameraManager = (CameraManager) getSystemService(CAMERA_SERVICE);
                cameraId = "0";
                previewSize = new Size(1920, 1080);
                
                if (ActivityCompat.checkSelfPermission(this, Manifest.permission.CAMERA) 
                        == PackageManager.PERMISSION_GRANTED) {
                    // Just try once - no retries to avoid system property spam
                    cameraManager.openCamera(cameraId, stateCallback, backgroundHandler);
                }
            } catch (Exception e) {
                // Only retry once more after a longer delay
                mainHandler.postDelayed(() -> {
                    try {
                        CameraManager cameraManager = (CameraManager) getSystemService(CAMERA_SERVICE);
                        cameraManager.openCamera("0", stateCallback, backgroundHandler);
                    } catch (Exception ex) {
                        // If it still fails, just show a simple message
                        runOnUiThread(() -> {
                            Toast.makeText(this, "Camera not available", Toast.LENGTH_SHORT).show();
                            finish();
                        });
                    }
                }, 3000);
            }
        }, 2000); // Wait 2 seconds for system to fully initialize
    }
    
    
    private final TextureView.SurfaceTextureListener textureListener = new TextureView.SurfaceTextureListener() {
        @Override
        public void onSurfaceTextureAvailable(SurfaceTexture surface, int width, int height) {
            openCamera();
        }
        
        @Override
        public void onSurfaceTextureSizeChanged(SurfaceTexture surface, int width, int height) {
            // No action needed
        }
        
        @Override
        public boolean onSurfaceTextureDestroyed(SurfaceTexture surface) {
            return false;
        }
        
        @Override
        public void onSurfaceTextureUpdated(SurfaceTexture surface) {
            // Reset UI for another scan if analysis is complete
            if (!isScanning && scanProgress == 0 && holdStillText.getText().toString().contains("Analysis Complete")) {
                holdStillText.setText("Hold still");
                instructionText.setText("Look directly into the camera to capture and detect malnutrition signs");
            }
        }
    };
    
    private final CameraDevice.StateCallback stateCallback = new CameraDevice.StateCallback() {
        @Override
        public void onOpened(@NonNull CameraDevice camera) {
            cameraDevice = camera;
            createCameraPreviewSession();
        }
        
        @Override
        public void onDisconnected(@NonNull CameraDevice camera) {
            camera.close();
            cameraDevice = null;
        }
        
        @Override
        public void onError(@NonNull CameraDevice camera, int error) {
            camera.close();
            cameraDevice = null;
            runOnUiThread(() -> {
                Toast.makeText(FullScreenCameraActivity.this, "Camera not available", Toast.LENGTH_SHORT).show();
                finish();
            });
        }
    };
    
    private void createCameraPreviewSession() {
        try {
            SurfaceTexture texture = textureView.getSurfaceTexture();
            if (texture == null) {
                mainHandler.postDelayed(() -> createCameraPreviewSession(), 500);
                return;
            }
            
            texture.setDefaultBufferSize(previewSize.getWidth(), previewSize.getHeight());
            Surface surface = new Surface(texture);
            
            captureRequestBuilder = cameraDevice.createCaptureRequest(CameraDevice.TEMPLATE_PREVIEW);
            captureRequestBuilder.addTarget(surface);
            
            cameraDevice.createCaptureSession(Arrays.asList(surface),
                new CameraCaptureSession.StateCallback() {
                    @Override
                    public void onConfigured(@NonNull CameraCaptureSession session) {
                        captureSession = session;
                        try {
                            // Minimal settings to avoid triggering system checks
                            captureRequestBuilder.set(CaptureRequest.CONTROL_AF_MODE,
                                CaptureRequest.CONTROL_AF_MODE_CONTINUOUS_PICTURE);
                            
                            captureSession.setRepeatingRequest(captureRequestBuilder.build(),
                                null, backgroundHandler);
                            
                            // Start scanning animation
                            mainHandler.postDelayed(() -> startScanningAnimation(), 1000);
                            
                        } catch (Exception e) {
                            // If it fails, just show error and finish
                            runOnUiThread(() -> {
                                Toast.makeText(FullScreenCameraActivity.this, "Camera setup failed", Toast.LENGTH_SHORT).show();
                                finish();
                            });
                        }
                    }
                    
                    @Override
                    public void onConfigureFailed(@NonNull CameraCaptureSession session) {
                        runOnUiThread(() -> {
                            Toast.makeText(FullScreenCameraActivity.this, "Camera setup failed", Toast.LENGTH_SHORT).show();
                            finish();
                        });
                    }
                }, backgroundHandler);
                
        } catch (Exception e) {
            runOnUiThread(() -> {
                Toast.makeText(this, "Camera setup failed", Toast.LENGTH_SHORT).show();
                finish();
            });
        }
    }
    
    private void startScanningAnimation() {
        // Only start if not already scanning and UI is ready
        if (isScanning || !holdStillText.getText().toString().equals("Hold still")) {
            return;
        }
        
        isScanning = true;
        scanProgress = 0;
        
        Runnable scanRunnable = new Runnable() {
            @Override
            public void run() {
                if (isScanning && scanProgress < 100) {
                    scanProgress += 2;
                    progressBar.setProgress(scanProgress);
                    
                    if (scanProgress >= 100) {
                        // Scanning complete
                        isScanning = false;
                        onScanComplete();
                    } else {
                        mainHandler.postDelayed(this, 50);
                    }
                }
            }
        };
        
        mainHandler.post(scanRunnable);
    }
    
    /**
     * Restart scanning for another analysis
     */
    public void restartScanning() {
        if (!isScanning) {
            holdStillText.setText("Hold still");
            instructionText.setText("Look directly into the camera to capture and detect malnutrition signs");
            scanProgress = 0;
            progressBar.setProgress(0);
            startScanningAnimation();
        }
    }
    
    private void onScanComplete() {
        // Show completion message or process results
        holdStillText.setText("Analysis Complete!");
        instructionText.setText("Malnutrition signs detected. Tap back to return or scan again.");
        
        // Reset scanning state to allow another scan
        isScanning = false;
        scanProgress = 0;
        progressBar.setProgress(0);
        
        // Don't close camera - stay on screen for another scan
        Log.d(TAG, "Analysis complete - staying on camera screen");
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        hideSystemUI();
        
        if (textureView.isAvailable()) {
            openCamera();
        } else {
            textureView.setSurfaceTextureListener(textureListener);
        }
    }
    
    @Override
    protected void onPause() {
        super.onPause();
        closeCamera();
    }
    
    private void closeCamera() {
        if (captureSession != null) {
            captureSession.close();
            captureSession = null;
        }
        if (cameraDevice != null) {
            cameraDevice.close();
            cameraDevice = null;
        }
        if (backgroundHandler != null) {
            backgroundHandler.getLooper().quitSafely();
            backgroundHandler = null;
        }
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        closeCamera();
    }
    
    @Override
    public void onBackPressed() {
        super.onBackPressed();
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
    }
}
