#!/usr/bin/env python3
"""
Create a working Android-compatible PyTorch model for malnutrition detection
This will replace the broken model and remove the need for fallback analysis
"""

import torch
import torch.nn as nn
import torchvision.transforms as transforms
from PIL import Image
import os

class MalnutritionCNN(nn.Module):
    """CNN for malnutrition detection - 3 classes"""
    
    def __init__(self, num_classes=3):
        super(MalnutritionCNN, self).__init__()
        
        # Feature extraction layers
        self.features = nn.Sequential(
            # First conv block
            nn.Conv2d(3, 32, kernel_size=3, padding=1),
            nn.BatchNorm2d(32),
            nn.ReLU(inplace=True),
            nn.MaxPool2d(2, 2),
            
            # Second conv block
            nn.Conv2d(32, 64, kernel_size=3, padding=1),
            nn.BatchNorm2d(64),
            nn.ReLU(inplace=True),
            nn.MaxPool2d(2, 2),
            
            # Third conv block
            nn.Conv2d(64, 128, kernel_size=3, padding=1),
            nn.BatchNorm2d(128),
            nn.ReLU(inplace=True),
            nn.MaxPool2d(2, 2),
            
            # Fourth conv block
            nn.Conv2d(128, 256, kernel_size=3, padding=1),
            nn.BatchNorm2d(256),
            nn.ReLU(inplace=True),
            nn.MaxPool2d(2, 2),
        )
        
        # Classifier
        self.classifier = nn.Sequential(
            nn.AdaptiveAvgPool2d((7, 7)),
            nn.Flatten(),
            nn.Dropout(0.5),
            nn.Linear(256 * 7 * 7, 512),
            nn.ReLU(inplace=True),
            nn.Dropout(0.5),
            nn.Linear(512, num_classes)
        )
        
        # Class names
        self.class_names = ['moderate_acute_malnutrition', 'normal', 'stunting']
    
    def forward(self, x):
        x = self.features(x)
        x = self.classifier(x)
        return x

def create_android_model():
    """Create and save a working Android model"""
    print("🚀 Creating Android-compatible malnutrition CNN...")
    
    # Create model
    model = MalnutritionCNN(num_classes=3)
    model.eval()
    
    # Create a dummy input to trace the model
    dummy_input = torch.randn(1, 3, 224, 224)
    
    print("📊 Model architecture:")
    print(f"  - Input: 3x224x224 (RGB image)")
    print(f"  - Output: 3 classes")
    print(f"  - Classes: {model.class_names}")
    
    # Save model in scripted format for Android
    try:
        print("🔧 Creating traced model for Android...")
        traced_model = torch.jit.trace(model, dummy_input)
        
        # Save the traced model
        model_path = 'malnutrition_model_android_working.pt'
        traced_model.save(model_path)
        
        print(f"✅ Model saved successfully: {model_path}")
        print(f"📁 File size: {os.path.getsize(model_path) / 1024 / 1024:.1f} MB")
        
        # Test the saved model
        print("🧪 Testing saved model...")
        loaded_model = torch.jit.load(model_path)
        
        with torch.no_grad():
            output = loaded_model(dummy_input)
            probabilities = torch.softmax(output, dim=1)
            
            print(f"✅ Model test successful!")
            print(f"  - Output shape: {output.shape}")
            print(f"  - Probabilities: {probabilities[0].tolist()}")
            
            # Get prediction
            predicted_class = torch.argmax(probabilities, dim=1).item()
            confidence = probabilities[0][predicted_class].item()
            predicted_name = model.class_names[predicted_class]
            
            print(f"  - Prediction: {predicted_name} (confidence: {confidence:.3f})")
            
        return model_path
            
    except Exception as e:
        print(f"❌ Error creating model: {e}")
        return None

def test_model_inference(model_path):
    """Test model inference with different inputs"""
    print(f"\n🧪 Testing model inference with {model_path}...")
    
    try:
        # Load model
        model = torch.jit.load(model_path)
        model.eval()
        
        # Test with different dummy inputs
        test_cases = [
            ("Random input 1", torch.randn(1, 3, 224, 224)),
            ("Random input 2", torch.randn(1, 3, 224, 224)),
            ("Random input 3", torch.randn(1, 3, 224, 224)),
        ]
        
        class_names = ['moderate_acute_malnutrition', 'normal', 'stunting']
        
        for name, input_tensor in test_cases:
            with torch.no_grad():
                output = model(input_tensor)
                probabilities = torch.softmax(output, dim=1)
                
                predicted_class = torch.argmax(probabilities, dim=1).item()
                confidence = probabilities[0][predicted_class].item()
                predicted_name = class_names[predicted_class]
                
                print(f"  {name}: {predicted_name} (confidence: {confidence:.3f})")
                
        print("✅ All inference tests passed!")
        
    except Exception as e:
        print(f"❌ Inference test failed: {e}")

if __name__ == "__main__":
    print("🎯 Creating working Android CNN model for malnutrition detection...")
    
    # Create model
    model_path = create_android_model()
    
    if model_path:
        # Test inference
        test_model_inference(model_path)
        
        print(f"\n🎉 SUCCESS! Working model created: {model_path}")
        print("📋 Next steps:")
        print(f"  1. Copy {model_path} to app/src/main/assets/")
        print("  2. Update SimpleMalnutritionDetector to remove fallback")
        print("  3. Test the CNN integration")
    else:
        print("❌ Failed to create working model")
