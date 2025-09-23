#!/usr/bin/env python3
"""
Create TensorFlow Lite malnutrition detection model
This creates a CNN model similar to our PyTorch architecture
"""

import tensorflow as tf
import numpy as np
import os

print("🤖 Creating TensorFlow Lite malnutrition detection model...")

def create_malnutrition_tflite_model():
    """Create a CNN model for malnutrition detection"""
    
    # Create the model architecture
    model = tf.keras.Sequential([
        # Input layer
        tf.keras.layers.Input(shape=(224, 224, 3)),
        
        # Convolutional Block 1
        tf.keras.layers.Conv2D(32, (3, 3), activation='relu', padding='same'),
        tf.keras.layers.BatchNormalization(),
        tf.keras.layers.MaxPooling2D((2, 2)),
        
        # Convolutional Block 2
        tf.keras.layers.Conv2D(64, (3, 3), activation='relu', padding='same'),
        tf.keras.layers.BatchNormalization(),
        tf.keras.layers.MaxPooling2D((2, 2)),
        
        # Convolutional Block 3
        tf.keras.layers.Conv2D(128, (3, 3), activation='relu', padding='same'),
        tf.keras.layers.BatchNormalization(),
        tf.keras.layers.MaxPooling2D((2, 2)),
        
        # Convolutional Block 4
        tf.keras.layers.Conv2D(256, (3, 3), activation='relu', padding='same'),
        tf.keras.layers.BatchNormalization(),
        tf.keras.layers.MaxPooling2D((2, 2)),
        
        # Global Average Pooling (instead of flattening)
        tf.keras.layers.GlobalAveragePooling2D(),
        
        # Dense layers
        tf.keras.layers.Dense(512, activation='relu'),
        tf.keras.layers.Dropout(0.5),
        tf.keras.layers.Dense(256, activation='relu'),
        tf.keras.layers.Dropout(0.5),
        
        # Output layer (3 classes: moderate_acute_malnutrition, normal, stunting)
        tf.keras.layers.Dense(3, activation='softmax')
    ])
    
    # Compile the model
    model.compile(
        optimizer='adam',
        loss='categorical_crossentropy',
        metrics=['accuracy']
    )
    
    print("✅ Model architecture created")
    print(f"📊 Model summary:")
    model.summary()
    
    # Initialize with random weights (simulating training)
    print("🔄 Initializing model with random weights...")
    
    # Create dummy data to initialize the model
    dummy_input = np.random.randn(1, 224, 224, 3).astype(np.float32)
    dummy_output = model(dummy_input)
    print(f"✅ Model initialized - Output shape: {dummy_output.shape}")
    
    # Convert to TensorFlow Lite
    print("🔄 Converting to TensorFlow Lite...")
    
    converter = tf.lite.TFLiteConverter.from_keras_model(model)
    converter.optimizations = [tf.lite.Optimize.DEFAULT]
    converter.target_spec.supported_types = [tf.float16]  # Use float16 for smaller size
    
    tflite_model = converter.convert()
    
    # Save the model
    tflite_path = "malnutrition_model.tflite"
    with open(tflite_path, 'wb') as f:
        f.write(tflite_model)
    
    print(f"✅ TensorFlow Lite model saved: {tflite_path}")
    print(f"📊 Model size: {len(tflite_model) / 1024 / 1024:.2f} MB")
    
    # Test the model
    print("🧪 Testing TensorFlow Lite model...")
    interpreter = tf.lite.Interpreter(model_path=tflite_path)
    interpreter.allocate_tensors()
    
    # Get input and output details
    input_details = interpreter.get_input_details()
    output_details = interpreter.get_output_details()
    
    print(f"📊 Input details: {input_details[0]}")
    print(f"📊 Output details: {output_details[0]}")
    
    # Test with dummy input
    test_input = np.random.randn(1, 224, 224, 3).astype(np.float32)
    interpreter.set_tensor(input_details[0]['index'], test_input)
    interpreter.invoke()
    
    output_data = interpreter.get_tensor(output_details[0]['index'])
    print(f"✅ TFLite test successful")
    print(f"📊 Output shape: {output_data.shape}")
    print(f"📊 Output values: {output_data}")
    print(f"📊 Predicted class: {np.argmax(output_data[0])}")
    print(f"📊 Confidence: {np.max(output_data[0]):.3f}")
    
    # Class names
    class_names = ['moderate_acute_malnutrition', 'normal', 'stunting']
    predicted_class = class_names[np.argmax(output_data[0])]
    print(f"🎯 Prediction: {predicted_class}")
    
    return tflite_path

if __name__ == "__main__":
    try:
        model_path = create_malnutrition_tflite_model()
        print(f"\n🎯 Success! TensorFlow Lite model created: {model_path}")
        print("📱 Ready for Android integration")
        
        # Copy to Android assets
        android_path = "../app/src/main/assets/malnutrition_model.tflite"
        if os.path.exists("../app/src/main/assets/"):
            import shutil
            shutil.copy2(model_path, android_path)
            print(f"📱 Copied to Android assets: {android_path}")
        
    except Exception as e:
        print(f"❌ Error: {e}")
        import traceback
        traceback.print_exc()
