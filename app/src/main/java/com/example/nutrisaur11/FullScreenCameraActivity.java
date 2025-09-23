package com.example.nutrisaur11;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.SurfaceTexture;
import android.hardware.camera2.CameraAccessException;
import android.hardware.camera2.CameraCaptureSession;
import android.hardware.camera2.CameraCharacteristics;
import android.hardware.camera2.CameraDevice;
import android.hardware.camera2.CameraManager;
import android.hardware.camera2.params.StreamConfigurationMap;
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
import android.widget.Button;
import com.google.android.material.floatingactionbutton.FloatingActionButton;

import com.example.nutrisaur11.ml.TensorFlowLiteMalnutritionDetector;
import com.example.nutrisaur11.ml.TensorFlowLiteMalnutritionDetector.MalnutritionAnalysisResult;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import java.util.Arrays;
import java.util.concurrent.Semaphore;
import java.util.concurrent.TimeUnit;

public class FullScreenCameraActivity extends AppCompatActivity {
    private static final String TAG = "FullScreenCamera";
    private static final int CAMERA_PERMISSION_REQUEST = 1001;
    private static final int MAX_PREVIEW_WIDTH = 1920;
    private static final int MAX_PREVIEW_HEIGHT = 1080;
    
    private TextureView textureView;
    private ImageView backButton;
    private ImageView cameraSwitchButton;
    private TextView holdStillText;
    private TextView instructionText;
    private ProgressBar progressBar;
    private FloatingActionButton cnnCaptureButton;
    private TextView aiStatusText;
    
    private CameraDevice cameraDevice;
    private CameraCaptureSession captureSession;
    private CaptureRequest.Builder captureRequestBuilder;
    private Handler backgroundHandler;
    private Handler mainHandler;
    private HandlerThread backgroundThread;
    
    private String cameraId;
    private Size previewSize;
    private boolean isScanning = false;
    private int scanProgress = 0;
    private boolean isCameraOpen = false;
    private boolean isActivityPaused = false;
    private Semaphore cameraOpenCloseLock = new Semaphore(1);
    private int retryCount = 0;
    private static final int MAX_RETRY_COUNT = 3;
    
    // Camera cycle functionality
    private String[] availableCameraIds;
    private int currentCameraIndex = 0;
    private boolean isFrontCamera = false;
    
    // TensorFlow Lite Malnutrition Detection
    private TensorFlowLiteMalnutritionDetector malnutritionDetector;
    private boolean isAnalyzing = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_full_screen_camera);
        
        // Hide status bar and navigation bar for full screen
        hideSystemUI();
        
        initializeViews();
        setupClickListeners();
        initializeTensorFlowLiteDetector();
        checkCameraPermission();
    }
    
    private void hideSystemUI() {
        View decorView = getWindow().getDecorView();
        int uiOptions = View.SYSTEM_UI_FLAG_LAYOUT_STABLE
                | View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                | View.SYSTEM_UI_FLAG_FULLSCREEN
                | View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY;
        decorView.setSystemUiVisibility(uiOptions);
        
        // Set status bar and navigation bar to transparent
        getWindow().setStatusBarColor(android.graphics.Color.TRANSPARENT);
        getWindow().setNavigationBarColor(android.graphics.Color.TRANSPARENT);
    }
    
    private void initializeViews() {
        textureView = findViewById(R.id.texture_view);
        backButton = findViewById(R.id.back_button);
        cameraSwitchButton = findViewById(R.id.camera_switch_button);
        holdStillText = findViewById(R.id.hold_still_text);
        instructionText = findViewById(R.id.instruction_text);
        progressBar = findViewById(R.id.progress_bar);
        cnnCaptureButton = findViewById(R.id.cnn_capture_button);
        aiStatusText = findViewById(R.id.ai_status_text);
        
        mainHandler = new Handler(Looper.getMainLooper());
        
        // Set up texture view listener
        textureView.setSurfaceTextureListener(textureListener);
    }
    
    private void setupClickListeners() {
        backButton.setOnClickListener(v -> {
            finish();
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        });
        
        cameraSwitchButton.setOnClickListener(v -> {
            switchCamera();
        });
        
        cnnCaptureButton.setOnClickListener(v -> {
            captureImageForAnalysis();
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
        if (isCameraOpen || isActivityPaused) {
            return;
        }
        
        try {
            if (!cameraOpenCloseLock.tryAcquire(2500, TimeUnit.MILLISECONDS)) {
                Log.w(TAG, "Time out waiting to lock camera opening.");
                return;
            }
            
            CameraManager cameraManager = (CameraManager) getSystemService(CAMERA_SERVICE);
            if (cameraManager == null) {
                Log.e(TAG, "Camera manager is null");
                return;
            }
            
            // Initialize available camera IDs
            availableCameraIds = cameraManager.getCameraIdList();
            if (availableCameraIds.length == 0) {
                Log.e(TAG, "No cameras available");
                return;
            }
            
            // Find the best camera only if not already set (e.g., during camera switch)
            if (cameraId == null) {
                cameraId = chooseOptimalCamera(cameraManager);
                if (cameraId == null) {
                    Log.e(TAG, "No suitable camera found");
                    return;
                }
            }
            
            // Set current camera index
            currentCameraIndex = 0; // Default to first camera
            for (int i = 0; i < availableCameraIds.length; i++) {
                if (availableCameraIds[i].equals(cameraId)) {
                    currentCameraIndex = i;
                    break;
                }
            }
            
            Log.d(TAG, "Available cameras: " + java.util.Arrays.toString(availableCameraIds));
            Log.d(TAG, "Selected camera: " + cameraId + " at index: " + currentCameraIndex);
            
            // Get camera characteristics and set preview size
            CameraCharacteristics characteristics = cameraManager.getCameraCharacteristics(cameraId);
            StreamConfigurationMap map = characteristics.get(CameraCharacteristics.SCALER_STREAM_CONFIGURATION_MAP);
            if (map == null) {
                Log.e(TAG, "Stream configuration map is null");
                return;
            }
            
            // Choose optimal preview size
            Size[] sizes = map.getOutputSizes(SurfaceTexture.class);
            previewSize = chooseOptimalSize(sizes, textureView.getWidth(), textureView.getHeight());
            
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                Log.e(TAG, "Camera permission not granted");
                return;
            }
            
            // Start background thread if not already started
            if (backgroundThread == null || !backgroundThread.isAlive()) {
                backgroundThread = new HandlerThread("CameraBackground");
                backgroundThread.start();
                backgroundHandler = new Handler(backgroundThread.getLooper());
            }
            
            // Open camera
            cameraManager.openCamera(cameraId, stateCallback, backgroundHandler);
            isCameraOpen = true;
            retryCount = 0; // Reset retry count on successful open
            
        } catch (CameraAccessException e) {
            Log.e(TAG, "Camera access exception: " + e.getMessage());
            handleCameraError("Camera access denied");
        } catch (InterruptedException e) {
            Log.e(TAG, "Camera open interrupted: " + e.getMessage());
            Thread.currentThread().interrupt();
        } catch (Exception e) {
            Log.e(TAG, "Camera open exception: " + e.getMessage());
            handleCameraError("Camera initialization failed");
        } finally {
            cameraOpenCloseLock.release();
        }
    }
    
    private String chooseOptimalCamera(CameraManager cameraManager) {
        try {
            // First try back camera
            for (String cameraId : cameraManager.getCameraIdList()) {
                CameraCharacteristics characteristics = cameraManager.getCameraCharacteristics(cameraId);
                Integer facing = characteristics.get(CameraCharacteristics.LENS_FACING);
                if (facing != null && facing == CameraCharacteristics.LENS_FACING_BACK) {
                    return cameraId;
                }
            }
            // If no back camera, try front camera
            for (String cameraId : cameraManager.getCameraIdList()) {
                CameraCharacteristics characteristics = cameraManager.getCameraCharacteristics(cameraId);
                Integer facing = characteristics.get(CameraCharacteristics.LENS_FACING);
                if (facing != null && facing == CameraCharacteristics.LENS_FACING_FRONT) {
                    return cameraId;
                }
            }
            // If no specific camera found, use the first available camera
            String[] cameraIds = cameraManager.getCameraIdList();
            return cameraIds.length > 0 ? cameraIds[0] : null;
        } catch (CameraAccessException e) {
            Log.e(TAG, "Error getting camera list: " + e.getMessage());
            return null;
        }
    }
    
    private Size chooseOptimalSize(Size[] choices, int width, int height) {
        if (choices == null || choices.length == 0) {
            return new Size(1280, 720); // Default fallback
        }
        
        // Find the best size that is not larger than the max preview size
        Size optimalSize = null;
        for (Size size : choices) {
            if (size.getWidth() <= MAX_PREVIEW_WIDTH && size.getHeight() <= MAX_PREVIEW_HEIGHT) {
                if (optimalSize == null || 
                    (size.getWidth() * size.getHeight() > optimalSize.getWidth() * optimalSize.getHeight())) {
                    optimalSize = size;
                }
            }
        }
        
        return optimalSize != null ? optimalSize : choices[0];
    }
    
    private void handleCameraError(String message) {
        runOnUiThread(() -> {
            if (retryCount < MAX_RETRY_COUNT) {
                retryCount++;
                Log.d(TAG, "Retrying camera open, attempt: " + retryCount);
                mainHandler.postDelayed(() -> openCamera(), 2000);
            } else {
                Toast.makeText(this, message + " - Please restart the app", Toast.LENGTH_LONG).show();
                holdStillText.setText("Camera Error");
                instructionText.setText("Please restart the app to try again");
            }
        });
    }
    
    
    private final TextureView.SurfaceTextureListener textureListener = new TextureView.SurfaceTextureListener() {
        @Override
        public void onSurfaceTextureAvailable(SurfaceTexture surface, int width, int height) {
            Log.d(TAG, "Surface texture available: " + width + "x" + height);
            if (!isCameraOpen && !isActivityPaused) {
                openCamera();
            }
        }
        
        @Override
        public void onSurfaceTextureSizeChanged(SurfaceTexture surface, int width, int height) {
            Log.d(TAG, "Surface texture size changed: " + width + "x" + height);
            // Reconfigure camera if needed
            if (isCameraOpen && captureSession != null) {
                try {
                    captureSession.stopRepeating();
                    createCameraPreviewSession();
                } catch (CameraAccessException e) {
                    Log.e(TAG, "Error reconfiguring camera: " + e.getMessage());
                }
            }
        }
        
        @Override
        public boolean onSurfaceTextureDestroyed(SurfaceTexture surface) {
            Log.d(TAG, "Surface texture destroyed");
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
            Log.d(TAG, "Camera opened successfully");
            cameraDevice = camera;
            isCameraOpen = true;
            
            // Update camera type indicator on main thread
            mainHandler.post(() -> updateCameraTypeIndicator());
            
            // Create preview session
            createCameraPreviewSession();
        }
        
        @Override
        public void onDisconnected(@NonNull CameraDevice camera) {
            Log.w(TAG, "Camera disconnected");
            closeCamera();
            runOnUiThread(() -> {
                if (!isActivityPaused) {
                    Toast.makeText(FullScreenCameraActivity.this, "Camera disconnected", Toast.LENGTH_SHORT).show();
                    // Don't auto-retry on disconnect, let user manually retry
                    holdStillText.setText("Camera Disconnected");
                    instructionText.setText("Tap back and try again");
                }
            });
        }
        
        @Override
        public void onError(@NonNull CameraDevice camera, int error) {
            Log.e(TAG, "Camera error: " + error);
            closeCamera();
            runOnUiThread(() -> {
                if (!isActivityPaused) {
                    String errorMessage = "Camera error occurred";
                    switch (error) {
                        case 1: // STATE_ERROR_CAMERA_IN_USE
                            errorMessage = "Camera is in use by another app";
                            break;
                        case 2: // STATE_ERROR_CAMERA_DISABLED
                            errorMessage = "Camera is disabled";
                            break;
                        case 3: // STATE_ERROR_CAMERA_DEVICE
                            errorMessage = "Camera device error";
                            break;
                        case 4: // STATE_ERROR_CAMERA_SERVICE
                            errorMessage = "Camera service error";
                            break;
                        case 5: // STATE_ERROR_MAX_CAMERAS_IN_USE
                            errorMessage = "Maximum cameras in use";
                            break;
                        default:
                            errorMessage = "Camera error code: " + error;
                            break;
                    }
                    Toast.makeText(FullScreenCameraActivity.this, errorMessage, Toast.LENGTH_LONG).show();
                    holdStillText.setText("Camera Error");
                    instructionText.setText("Please restart the app");
                }
            });
        }
    };
    
    private void createCameraPreviewSession() {
        if (cameraDevice == null || !isCameraOpen) {
            Log.w(TAG, "Camera device is null or not open, skipping preview session creation");
            return;
        }
        
        try {
            SurfaceTexture texture = textureView.getSurfaceTexture();
            if (texture == null) {
                Log.w(TAG, "Surface texture is null, retrying in 500ms");
                mainHandler.postDelayed(() -> createCameraPreviewSession(), 500);
                return;
            }
            
            if (previewSize == null) {
                Log.w(TAG, "Preview size is null, using default");
                previewSize = new Size(1280, 720);
            }
            
            texture.setDefaultBufferSize(previewSize.getWidth(), previewSize.getHeight());
            Surface surface = new Surface(texture);
            
            captureRequestBuilder = cameraDevice.createCaptureRequest(CameraDevice.TEMPLATE_PREVIEW);
            captureRequestBuilder.addTarget(surface);
            
            // Configure capture request with minimal settings
            captureRequestBuilder.set(CaptureRequest.CONTROL_AF_MODE, CaptureRequest.CONTROL_AF_MODE_CONTINUOUS_PICTURE);
            captureRequestBuilder.set(CaptureRequest.CONTROL_AE_MODE, CaptureRequest.CONTROL_AE_MODE_ON);
            captureRequestBuilder.set(CaptureRequest.CONTROL_AWB_MODE, CaptureRequest.CONTROL_AWB_MODE_AUTO);
            
            cameraDevice.createCaptureSession(Arrays.asList(surface),
                new CameraCaptureSession.StateCallback() {
                    @Override
                    public void onConfigured(@NonNull CameraCaptureSession session) {
                        Log.d(TAG, "Camera preview session configured");
                        captureSession = session;
                        try {
                            captureSession.setRepeatingRequest(captureRequestBuilder.build(),
                                null, backgroundHandler);
                            
                            // Start scanning animation after successful preview
                            mainHandler.postDelayed(() -> startScanningAnimation(), 1000);
                            
                        } catch (CameraAccessException e) {
                            Log.e(TAG, "Error starting camera preview: " + e.getMessage());
                            runOnUiThread(() -> {
                                Toast.makeText(FullScreenCameraActivity.this, "Camera preview failed", Toast.LENGTH_SHORT).show();
                            });
                        }
                    }
                    
                    @Override
                    public void onConfigureFailed(@NonNull CameraCaptureSession session) {
                        Log.e(TAG, "Camera preview session configuration failed");
                        runOnUiThread(() -> {
                            Toast.makeText(FullScreenCameraActivity.this, "Camera configuration failed", Toast.LENGTH_SHORT).show();
                            holdStillText.setText("Configuration Failed");
                            instructionText.setText("Please restart the app");
                        });
                    }
                }, backgroundHandler);
                
        } catch (CameraAccessException e) {
            Log.e(TAG, "Camera access exception in preview session: " + e.getMessage());
            runOnUiThread(() -> {
                Toast.makeText(this, "Camera access error", Toast.LENGTH_SHORT).show();
            });
        } catch (Exception e) {
            Log.e(TAG, "Unexpected error in preview session: " + e.getMessage());
            runOnUiThread(() -> {
                Toast.makeText(this, "Camera preview error", Toast.LENGTH_SHORT).show();
            });
        }
    }
    
    private void switchCamera() {
        Log.d(TAG, "switchCamera called - available cameras: " + 
              (availableCameraIds != null ? java.util.Arrays.toString(availableCameraIds) : "null") + 
              ", current index: " + currentCameraIndex);
              
        if (availableCameraIds == null || availableCameraIds.length <= 1) {
            mainHandler.post(() -> {
                Toast.makeText(this, "Only one camera available", Toast.LENGTH_SHORT).show();
            });
            return;
        }
        
        // Close current camera
        closeCamera();
        
        // Move to next camera
        currentCameraIndex = (currentCameraIndex + 1) % availableCameraIds.length;
        cameraId = availableCameraIds[currentCameraIndex];
        
        Log.d(TAG, "Switching to camera: " + cameraId + " at index: " + currentCameraIndex);
        
        // Update camera type indicator
        updateCameraTypeIndicator();
        
        // Reopen camera with new ID
        mainHandler.postDelayed(() -> {
            openCamera();
        }, 500);
    }
    
    private void updateCameraTypeIndicator() {
        try {
            CameraManager cameraManager = (CameraManager) getSystemService(CAMERA_SERVICE);
            if (cameraManager != null && cameraId != null) {
                CameraCharacteristics characteristics = cameraManager.getCameraCharacteristics(cameraId);
                Integer facing = characteristics.get(CameraCharacteristics.LENS_FACING);
                isFrontCamera = (facing != null && facing == CameraCharacteristics.LENS_FACING_FRONT);
                
                // Update UI text based on camera type
                if (isFrontCamera) {
                    instructionText.setText("Look directly into the front camera for face detection");
                } else {
                    instructionText.setText("Position your face in the frame for analysis");
                }
            }
        } catch (CameraAccessException e) {
            Log.e(TAG, "Error getting camera characteristics: " + e.getMessage());
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
        isActivityPaused = false;
        
        if (textureView.isAvailable()) {
            openCamera();
        } else {
            textureView.setSurfaceTextureListener(textureListener);
        }
    }
    
    @Override
    protected void onPause() {
        super.onPause();
        isActivityPaused = true;
        closeCamera();
    }
    
    private void closeCamera() {
        try {
            if (!cameraOpenCloseLock.tryAcquire(2500, TimeUnit.MILLISECONDS)) {
                Log.w(TAG, "Time out waiting to lock camera closing.");
                return;
            }
            
            if (captureSession != null) {
                try {
                    captureSession.stopRepeating();
                } catch (CameraAccessException e) {
                    Log.e(TAG, "Error stopping camera session: " + e.getMessage());
                }
                captureSession.close();
                captureSession = null;
            }
            
            if (cameraDevice != null) {
                cameraDevice.close();
                cameraDevice = null;
            }
            
            isCameraOpen = false;
            
        } catch (InterruptedException e) {
            Log.e(TAG, "Camera close interrupted: " + e.getMessage());
            Thread.currentThread().interrupt();
        } finally {
            cameraOpenCloseLock.release();
        }
    }
    
    private void closeBackgroundThread() {
        if (backgroundThread != null) {
            backgroundThread.quitSafely();
            try {
                backgroundThread.join();
            } catch (InterruptedException e) {
                Log.e(TAG, "Error joining background thread: " + e.getMessage());
                Thread.currentThread().interrupt();
            }
            backgroundThread = null;
            backgroundHandler = null;
        }
    }
    
    /**
     * Initialize TensorFlow Lite malnutrition detector
     */
    private void initializeTensorFlowLiteDetector() {
        try {
            malnutritionDetector = new TensorFlowLiteMalnutritionDetector(this);
            Log.d(TAG, "TensorFlow Lite malnutrition detector initialized successfully");
        } catch (Exception e) {
            Log.e(TAG, "Failed to initialize TensorFlow Lite detector: " + e.getMessage());
            runOnUiThread(() -> {
                aiStatusText.setText("AI analysis unavailable");
                cnnCaptureButton.setEnabled(false);
                Toast.makeText(this, "AI analysis not available", Toast.LENGTH_SHORT).show();
            });
        }
    }
    
    /**
     * Capture current camera frame for TensorFlow Lite analysis
     */
    private void captureImageForAnalysis() {
        if (isAnalyzing) {
            Toast.makeText(this, "Analysis in progress...", Toast.LENGTH_SHORT).show();
            return;
        }
        
        if (malnutritionDetector == null || !malnutritionDetector.isAvailable()) {
            Toast.makeText(this, "AI analysis not available", Toast.LENGTH_SHORT).show();
            return;
        }
        
        if (!textureView.isAvailable()) {
            Toast.makeText(this, "Camera not ready", Toast.LENGTH_SHORT).show();
            return;
        }
        
        try {
            // Capture the current frame from TextureView
            Bitmap bitmap = textureView.getBitmap();
            if (bitmap != null) {
                analyzeImageWithTensorFlowLite(bitmap);
            } else {
                Toast.makeText(this, "Failed to capture image", Toast.LENGTH_SHORT).show();
            }
        } catch (Exception e) {
            Log.e(TAG, "Error capturing image: " + e.getMessage());
            Toast.makeText(this, "Error capturing image", Toast.LENGTH_SHORT).show();
        }
    }
    
    /**
     * Analyze captured image with TensorFlow Lite
     */
    private void analyzeImageWithTensorFlowLite(Bitmap bitmap) {
        isAnalyzing = true;
        
        // Update UI to show analysis in progress
        runOnUiThread(() -> {
            aiStatusText.setText("🔍 Analyzing with AI...");
            cnnCaptureButton.setEnabled(false);
            holdStillText.setText("AI Analysis in Progress");
            instructionText.setText("Processing image for malnutrition detection...");
        });
        
        // Run analysis on background thread
        new Thread(() -> {
            try {
                MalnutritionAnalysisResult result = malnutritionDetector.analyzeImage(bitmap);
                runOnUiThread(() -> showAnalysisResults(result));
            } catch (Exception e) {
                Log.e(TAG, "TensorFlow Lite analysis error: " + e.getMessage());
                runOnUiThread(() -> {
                    Toast.makeText(FullScreenCameraActivity.this, "Analysis failed: " + e.getMessage(), Toast.LENGTH_LONG).show();
                    resetAnalysisUI();
                });
            }
        }).start();
    }
    
    /**
     * Show TensorFlow Lite analysis results
     */
    private void showAnalysisResults(MalnutritionAnalysisResult result) {
        resetAnalysisUI();
        
        // Navigate to dedicated results page
        Intent intent = AnalysisResultsActivity.createIntent(this, result, null);
        startActivity(intent);
        
        // Finish this activity to prevent back navigation to camera
        finish();
    }
    
    /**
     * Reset analysis UI to ready state
     */
    private void resetAnalysisUI() {
        isAnalyzing = false;
        aiStatusText.setText("Tap to analyze with AI");
        cnnCaptureButton.setEnabled(true);
        
        if (holdStillText.getText().toString().contains("Analysis Complete")) {
            holdStillText.setText("Hold still");
            instructionText.setText("Position your face in the frame for analysis");
        }
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        closeCamera();
        closeBackgroundThread();
        
        // Clean up TensorFlow Lite malnutrition detector
        if (malnutritionDetector != null) {
            malnutritionDetector.cleanup();
            malnutritionDetector = null;
        }
    }
    
    @Override
    public void onBackPressed() {
        super.onBackPressed();
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
    }
}
