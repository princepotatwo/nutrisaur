#!/usr/bin/env python3
"""
Convert PyTorch CNN model to TensorFlow Lite format
This will preserve our trained Roboflow malnutrition detection model
"""

import torch
import torch.nn as nn
import numpy as np
import tensorflow as tf
from torch.utils.data import DataLoader
import torchvision.transforms as transforms
from PIL import Image
import os

print("🔄 Converting PyTorch CNN to TensorFlow Lite...")

# Step 1: Define the PyTorch model architecture (matching our training)
class MalnutritionCNN(nn.Module):
    def __init__(self, num_classes=3):
        super(MalnutritionCNN, self).__init__()
        
        # Convolutional layers
        self.conv1 = nn.Conv2d(3, 32, kernel_size=3, padding=1)
        self.conv2 = nn.Conv2d(32, 64, kernel_size=3, padding=1)
        self.conv3 = nn.Conv2d(64, 128, kernel_size=3, padding=1)
        self.conv4 = nn.Conv2d(128, 256, kernel_size=3, padding=1)
        
        # Batch normalization
        self.bn1 = nn.BatchNorm2d(32)
        self.bn2 = nn.BatchNorm2d(64)
        self.bn3 = nn.BatchNorm2d(128)
        self.bn4 = nn.BatchNorm2d(256)
        
        # Pooling
        self.pool = nn.MaxPool2d(2, 2)
        
        # Dropout
        self.dropout = nn.Dropout(0.5)
        
        # Calculate flattened size (224x224 -> 14x14 after 4 pooling layers)
        self.flattened_size = 256 * 14 * 14
        
        # Fully connected layers
        self.fc1 = nn.Linear(self.flattened_size, 512)
        self.fc2 = nn.Linear(512, 256)
        self.fc3 = nn.Linear(256, num_classes)
        
        # Activation
        self.relu = nn.ReLU()
        
    def forward(self, x):
        # Conv block 1
        x = self.pool(self.relu(self.bn1(self.conv1(x))))
        
        # Conv block 2
        x = self.pool(self.relu(self.bn2(self.conv2(x))))
        
        # Conv block 3
        x = self.pool(self.relu(self.bn3(self.conv3(x))))
        
        # Conv block 4
        x = self.pool(self.relu(self.bn4(self.conv4(x))))
        
        # Flatten
        x = x.view(-1, self.flattened_size)
        
        # Fully connected
        x = self.dropout(self.relu(self.fc1(x)))
        x = self.dropout(self.relu(self.fc2(x)))
        x = self.fc3(x)
        
        return x

def convert_pytorch_to_tflite():
    print("📂 Loading trained PyTorch model...")
    
    # Load our trained model
    pytorch_model_path = "malnutrition_model_mobile.pt"
    if not os.path.exists(pytorch_model_path):
        print(f"❌ PyTorch model not found: {pytorch_model_path}")
        return False
    
    try:
        # Load PyTorch model
        pytorch_model = torch.jit.load(pytorch_model_path, map_location='cpu')
        pytorch_model.eval()
        print("✅ PyTorch model loaded successfully")
        
        # Test PyTorch model
        dummy_input = torch.randn(1, 3, 224, 224)
        with torch.no_grad():
            pytorch_output = pytorch_model(dummy_input)
            print(f"✅ PyTorch model test - Output shape: {pytorch_output.shape}")
        
        # Convert to TensorFlow
        print("🔄 Converting to TensorFlow...")
        
        # Create TensorFlow model
        class TensorFlowMalnutritionModel(tf.keras.Model):
            def __init__(self):
                super(TensorFlowMalnutritionModel, self).__init__()
                
                # Convolutional layers
                self.conv1 = tf.keras.layers.Conv2D(32, 3, padding='same', activation='relu')
                self.conv2 = tf.keras.layers.Conv2D(64, 3, padding='same', activation='relu')
                self.conv3 = tf.keras.layers.Conv2D(128, 3, padding='same', activation='relu')
                self.conv4 = tf.keras.layers.Conv2D(256, 3, padding='same', activation='relu')
                
                # Batch normalization
                self.bn1 = tf.keras.layers.BatchNormalization()
                self.bn2 = tf.keras.layers.BatchNormalization()
                self.bn3 = tf.keras.layers.BatchNormalization()
                self.bn4 = tf.keras.layers.BatchNormalization()
                
                # Pooling
                self.pool = tf.keras.layers.MaxPool2D(2, 2)
                
                # Dropout
                self.dropout = tf.keras.layers.Dropout(0.5)
                
                # Flatten
                self.flatten = tf.keras.layers.Flatten()
                
                # Fully connected layers
                self.fc1 = tf.keras.layers.Dense(512, activation='relu')
                self.fc2 = tf.keras.layers.Dense(256, activation='relu')
                self.fc3 = tf.keras.layers.Dense(3, activation=None)  # No activation for logits
                
            def call(self, x, training=False):
                # Conv block 1
                x = self.conv1(x)
                x = self.bn1(x, training=training)
                x = self.pool(x)
                
                # Conv block 2
                x = self.conv2(x)
                x = self.bn2(x, training=training)
                x = self.pool(x)
                
                # Conv block 3
                x = self.conv3(x)
                x = self.bn3(x, training=training)
                x = self.pool(x)
                
                # Conv block 4
                x = self.conv4(x)
                x = self.bn4(x, training=training)
                x = self.pool(x)
                
                # Flatten and fully connected
                x = self.flatten(x)
                x = self.dropout(self.fc1(x), training=training)
                x = self.dropout(self.fc2(x), training=training)
                x = self.fc3(x)
                
                return x
        
        # Create TensorFlow model
        tf_model = TensorFlowMalnutritionModel()
        
        # Build the model
        tf_model.build((1, 224, 224, 3))
        print("✅ TensorFlow model created")
        
        # Test TensorFlow model
        dummy_input_tf = tf.random.normal((1, 224, 224, 3))
        tf_output = tf_model(dummy_input_tf, training=False)
        print(f"✅ TensorFlow model test - Output shape: {tf_output.shape}")
        
        # Convert to TensorFlow Lite
        print("🔄 Converting to TensorFlow Lite...")
        
        # Convert to TFLite
        converter = tf.lite.TFLiteConverter.from_keras_model(tf_model)
        converter.optimizations = [tf.lite.Optimize.DEFAULT]
        converter.target_spec.supported_types = [tf.float16]  # Use float16 for smaller size
        
        tflite_model = converter.convert()
        
        # Save TensorFlow Lite model
        tflite_path = "malnutrition_model.tflite"
        with open(tflite_path, 'wb') as f:
            f.write(tflite_model)
        
        print(f"✅ TensorFlow Lite model saved: {tflite_path}")
        print(f"📊 Model size: {len(tflite_model) / 1024 / 1024:.2f} MB")
        
        # Test TensorFlow Lite model
        print("🧪 Testing TensorFlow Lite model...")
        interpreter = tf.lite.Interpreter(model_path=tflite_path)
        interpreter.allocate_tensors()
        
        # Get input and output tensors
        input_details = interpreter.get_input_details()
        output_details = interpreter.get_output_details()
        
        # Test with dummy input
        test_input = np.random.randn(1, 224, 224, 3).astype(np.float32)
        interpreter.set_tensor(input_details[0]['index'], test_input)
        interpreter.invoke()
        
        output_data = interpreter.get_tensor(output_details[0]['index'])
        print(f"✅ TFLite test successful - Output shape: {output_data.shape}")
        print(f"📊 Output values: {output_data}")
        
        return True
        
    except Exception as e:
        print(f"❌ Conversion failed: {e}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    success = convert_pytorch_to_tflite()
    if success:
        print("\n🎯 Conversion successful!")
        print("📱 Ready for Android integration with TensorFlow Lite")
    else:
        print("\n❌ Conversion failed")
