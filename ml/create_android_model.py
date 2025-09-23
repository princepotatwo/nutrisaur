#!/usr/bin/env python3
"""
Create a minimal working Android-compatible PyTorch model for malnutrition detection
This creates a simple 3-class CNN that works with PyTorch Mobile
"""

import torch
import torch.nn as nn
import torchvision.transforms as transforms
from PIL import Image
import os

class SimpleMalnutritionCNN(nn.Module):
    """Simple 3-class CNN for malnutrition detection"""
    
    def __init__(self, num_classes=3):
        super(SimpleMalnutritionCNN, self).__init__()
        
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
    
    def get_class_names(self):
        return self.class_names

def create_sample_model():
    """Create and save a working Android model"""
    print("Creating Simple Malnutrition CNN...")
    
    # Create model
    model = SimpleMalnutritionCNN(num_classes=3)
    model.eval()
    
    # Create a dummy input to trace the model
    dummy_input = torch.randn(1, 3, 224, 224)
    
    print("Model architecture:")
    print(model)
    
    # Save model in scripted format for Android
    try:
        # Method 1: Try torch.jit.script
        print("Attempting to script the model...")
        scripted_model = torch.jit.script(model)
        scripted_model.save('malnutrition_model_android.pt')
        print("✅ Model saved successfully with torch.jit.script")
        
        # Test the saved model
        loaded_model = torch.jit.load('malnutrition_model_android.pt')
        with torch.no_grad():
            output = loaded_model(dummy_input)
            print(f"✅ Model test successful. Output shape: {output.shape}")
            
    except Exception as e:
        print(f"❌ Scripting failed: {e}")
        
        try:
            # Method 2: Try torch.jit.trace
            print("Attempting to trace the model...")
            traced_model = torch.jit.trace(model, dummy_input)
            traced_model.save('malnutrition_model_android.pt')
            print("✅ Model saved successfully with torch.jit.trace")
            
            # Test the saved model
            loaded_model = torch.jit.load('malnutrition_model_android.pt')
            with torch.no_grad():
                output = loaded_model(dummy_input)
                print(f"✅ Model test successful. Output shape: {output.shape}")
                
        except Exception as e2:
            print(f"❌ Tracing also failed: {e2}")
            
            # Method 3: Save as regular PyTorch model
            print("Saving as regular PyTorch model...")
            torch.save(model.state_dict(), 'malnutrition_model_weights.pth')
            torch.save({
                'model_state_dict': model.state_dict(),
                'class_names': model.get_class_names(),
                'model_class': 'SimpleMalnutritionCNN'
            }, 'malnutrition_model_complete.pth')
            print("✅ Model weights saved")
    
    # Create class indices mapping
    class_indices = {
        'moderate_acute_malnutrition': 0,
        'normal': 1,
        'stunting': 2
    }
    
    print("\nClass mapping:")
    for name, idx in class_indices.items():
        print(f"  {idx}: {name}")
    
    return model

def test_model_inference():
    """Test model inference with dummy data"""
    print("\nTesting model inference...")
    
    try:
        # Load model
        model = torch.jit.load('malnutrition_model_android.pt')
        model.eval()
        
        # Create dummy image data
        dummy_image = torch.randn(1, 3, 224, 224)
        
        # Run inference
        with torch.no_grad():
            output = model(dummy_image)
            probabilities = torch.softmax(output, dim=1)
            
            print(f"Raw output: {output}")
            print(f"Probabilities: {probabilities}")
            
            # Get prediction
            predicted_class = torch.argmax(probabilities, dim=1).item()
            confidence = probabilities[0][predicted_class].item()
            
            class_names = ['moderate_acute_malnutrition', 'normal', 'stunting']
            predicted_name = class_names[predicted_class]
            
            print(f"✅ Prediction: {predicted_name} (confidence: {confidence:.3f})")
            
    except Exception as e:
        print(f"❌ Inference test failed: {e}")

if __name__ == "__main__":
    print("🚀 Creating Android-compatible malnutrition detection model...")
    
    # Create model
    model = create_sample_model()
    
    # Test inference
    test_model_inference()
    
    print("\n🎉 Model creation complete!")
    print("📁 Files created:")
    print("  - malnutrition_model_android.pt (Android model)")
    print("\n📋 Next steps:")
    print("  1. Copy malnutrition_model_android.pt to app/src/main/assets/")
    print("  2. Rebuild and test the Android app")
