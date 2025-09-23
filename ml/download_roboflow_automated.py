#!/usr/bin/env python3
"""
Automated Roboflow Dataset Download and Processing
This script will help you download and process the Roboflow Deteksi Stunting dataset
"""

import os
import sys
import requests
import json
import zipfile
import shutil
from pathlib import Path
import subprocess
import time

class RoboflowDownloader:
    def __init__(self, base_dir: Path):
        self.base_dir = base_dir
        self.dataset_url = "https://universe.roboflow.com/database-ayu/deteksi-stunting"
        self.output_dir = base_dir / "malnutrition_dataset"
        self.collected_dir = base_dir / "collected_datasets"
        
        # Create directories
        self.setup_directories()
        
        # Dataset information
        self.dataset_info = {
            "name": "Deteksi Stunting",
            "description": "Indonesian stunting detection dataset",
            "classes": ["Healthy", "MalNutrisi", "Stunting"],
            "class_mapping": {
                "Healthy": "normal",
                "MalNutrisi": "moderate_acute_malnutrition",
                "Stunting": "stunting"
            },
            "format": "YOLO",
            "license": "CC BY 4.0"
        }
    
    def setup_directories(self):
        """Create necessary directories"""
        directories = [
            "collected_datasets",
            "malnutrition_dataset/train/normal",
            "malnutrition_dataset/train/moderate_acute_malnutrition",
            "malnutrition_dataset/train/stunting",
            "malnutrition_dataset/val/normal",
            "malnutrition_dataset/val/moderate_acute_malnutrition",
            "malnutrition_dataset/val/stunting",
            "runs/malnutrition_cnn"
        ]
        
        for dir_path in directories:
            Path(dir_path).mkdir(parents=True, exist_ok=True)
        
        print("📁 Directory structure created")
    
    def check_existing_dataset(self):
        """Check if dataset already exists"""
        zip_path = self.collected_dir / "roboflow_stunting.zip"
        extracted_path = self.collected_dir / "roboflow_stunting"
        
        if zip_path.exists():
            print(f"✅ Found dataset ZIP: {zip_path}")
            return True
        elif extracted_path.exists():
            print(f"✅ Found extracted dataset: {extracted_path}")
            return True
        else:
            return False
    
    def create_download_instructions(self):
        """Create detailed download instructions"""
        instructions = f"""
🚀 Roboflow Deteksi Stunting Dataset Download Instructions
========================================================

Dataset URL: {self.dataset_url}

📋 Step-by-Step Instructions:

1. 🌐 Open your web browser and visit:
   {self.dataset_url}

2. 👤 Create a free Roboflow account:
   - Click "Sign Up" or "Get Started"
   - Use your email to create account
   - Verify your email address

3. 📥 Download the dataset:
   - Click "Download Dataset" button
   - Select format: "YOLO"
   - Choose size: "640" (recommended)
   - Click "Download ZIP"

4. 💾 Save the file:
   - Save as: roboflow_stunting.zip
   - Place in: {self.collected_dir}/

5. 🔄 Run this script again to process the dataset

📊 Dataset Information:
- Name: {self.dataset_info['name']}
- Classes: {', '.join(self.dataset_info['classes'])}
- Format: {self.dataset_info['format']}
- License: {self.dataset_info['license']}

🎯 Class Mapping:
- Healthy → normal
- MalNutrisi → moderate_acute_malnutrition  
- Stunting → stunting

📁 Expected File Structure After Download:
{self.collected_dir}/
└── roboflow_stunting.zip

After Processing:
malnutrition_dataset/
├── train/
│   ├── normal/
│   ├── moderate_acute_malnutrition/
│   └── stunting/
└── val/
    ├── normal/
    ├── moderate_acute_malnutrition/
    └── stunting/

⏱️ Estimated Download Time: 2-5 minutes (depending on internet speed)
💾 Estimated File Size: 50-100 MB
"""
        print(instructions)
        
        # Save instructions to file
        with open(self.base_dir / "ROBOFLOW_DOWNLOAD_INSTRUCTIONS.txt", "w") as f:
            f.write(instructions)
        
        print(f"📄 Instructions saved to: ROBOFLOW_DOWNLOAD_INSTRUCTIONS.txt")
    
    def extract_dataset(self):
        """Extract the downloaded dataset"""
        zip_path = self.collected_dir / "roboflow_stunting.zip"
        extract_path = self.collected_dir / "roboflow_stunting"
        
        if not zip_path.exists():
            print("❌ ZIP file not found")
            return False
        
        print(f"📦 Extracting dataset from {zip_path}")
        
        try:
            with zipfile.ZipFile(zip_path, 'r') as zip_ref:
                zip_ref.extractall(extract_path)
            
            print(f"✅ Dataset extracted to {extract_path}")
            return True
        except Exception as e:
            print(f"❌ Error extracting dataset: {e}")
            return False
    
    def process_dataset(self):
        """Process the extracted dataset"""
        source_path = self.collected_dir / "roboflow_stunting"
        
        if not source_path.exists():
            print("❌ Extracted dataset not found")
            return False
        
        print("🔄 Processing dataset...")
        
        # Process train and validation splits
        for split in ["train", "valid"]:
            if split == "valid":
                target_split = "val"
            else:
                target_split = split
            
            images_dir = source_path / split / "images"
            labels_dir = source_path / split / "labels"
            
            if images_dir.exists() and labels_dir.exists():
                self.process_yolo_split(images_dir, labels_dir, target_split)
            else:
                print(f"⚠️ {split} directory structure not found, checking for other formats...")
                self.process_alternative_formats(source_path, target_split)
        
        return True
    
    def process_yolo_split(self, images_dir: Path, labels_dir: Path, target_split: str):
        """Process a single YOLO split"""
        class_mapping = self.dataset_info["class_mapping"]
        target_classes = list(class_mapping.values())
        
        print(f"📊 Processing {target_split} split...")
        
        processed_count = 0
        
        for image_file in images_dir.glob("*.jpg"):
            label_file = labels_dir / f"{image_file.stem}.txt"
            
            if label_file.exists():
                try:
                    # Read YOLO label file
                    with open(label_file, 'r') as f:
                        lines = f.readlines()
                    
                    if lines:
                        # Get the first class (assuming single object per image)
                        class_id = int(lines[0].split()[0])
                        if class_id < len(target_classes):
                            target_class = target_classes[class_id]
                            
                            # Copy image to target directory
                            target_path = self.output_dir / target_split / target_class / image_file.name
                            shutil.copy2(image_file, target_path)
                            processed_count += 1
                except Exception as e:
                    print(f"⚠️ Error processing {image_file}: {e}")
        
        print(f"✅ Processed {processed_count} images for {target_split}")
    
    def process_alternative_formats(self, source_path: Path, target_split: str):
        """Process alternative dataset formats"""
        print(f"🔍 Looking for alternative formats in {source_path}")
        
        # Look for ImageFolder format
        for item in source_path.iterdir():
            if item.is_dir():
                class_name = item.name.lower()
                
                # Map class names
                if any(keyword in class_name for keyword in ["healthy", "normal"]):
                    target_class = "normal"
                elif any(keyword in class_name for keyword in ["malnutrisi", "malnutrition"]):
                    target_class = "moderate_acute_malnutrition"
                elif "stunting" in class_name:
                    target_class = "stunting"
                else:
                    continue
                
                # Copy images
                target_dir = self.output_dir / target_split / target_class
                target_dir.mkdir(parents=True, exist_ok=True)
                
                image_files = list(item.glob("*.jpg")) + list(item.glob("*.png"))
                
                for image_file in image_files:
                    shutil.copy2(image_file, target_dir / image_file.name)
                
                print(f"✅ Copied {len(image_files)} images from {item.name} to {target_class}")
    
    def generate_dataset_report(self):
        """Generate a report of the processed dataset"""
        report = {
            "dataset_info": self.dataset_info,
            "total_images": 0,
            "classes": {},
            "splits": {"train": 0, "val": 0}
        }
        
        # Count images by class and split
        for split in ["train", "val"]:
            split_dir = self.output_dir / split
            if split_dir.exists():
                for class_dir in split_dir.iterdir():
                    if class_dir.is_dir():
                        class_name = class_dir.name
                        image_count = len(list(class_dir.glob("*.jpg")) + list(class_dir.glob("*.png")))
                        
                        if class_name not in report["classes"]:
                            report["classes"][class_name] = {"train": 0, "val": 0}
                        
                        report["classes"][class_name][split] = image_count
                        report["splits"][split] += image_count
                        report["total_images"] += image_count
        
        # Save report
        report_path = self.output_dir / "dataset_report.json"
        with open(report_path, "w") as f:
            json.dump(report, f, indent=2)
        
        # Print summary
        print("\n📊 Dataset Processing Report:")
        print(f"Total Images: {report['total_images']}")
        print(f"Train Images: {report['splits']['train']}")
        print(f"Validation Images: {report['splits']['val']}")
        print("\nClass Distribution:")
        for class_name, counts in report["classes"].items():
            total = counts["train"] + counts["val"]
            print(f"  {class_name}: {total} images ({counts['train']} train, {counts['val']} val)")
        
        return report
    
    def create_training_script(self):
        """Create a training script for the processed dataset"""
        script_content = f'''#!/bin/bash
# Train Malnutrition CNN with Roboflow Dataset
# Dataset: {self.dataset_info['name']}

echo "🚀 Training Malnutrition CNN with Roboflow Dataset"
echo "=================================================="

# Activate virtual environment
source ml_env/bin/activate

# Install additional dependencies if needed
pip install seaborn

# Train the model
python ml/train_cnn.py \\
    --data_dir malnutrition_dataset \\
    --output_dir runs/malnutrition_cnn_roboflow \\
    --epochs 30 \\
    --batch_size 32 \\
    --lr 1e-4 \\
    --model resnet18 \\
    --img_size 224 \\
    --val_split 0.2 \\
    --seed 42

echo "✅ Training completed!"
echo "📱 Model saved to: runs/malnutrition_cnn_roboflow/best_model.pt"
echo "📱 Android model: runs/malnutrition_cnn_roboflow/malnutrition_model_android.pt"

# Copy model to Android assets
if [ -f "runs/malnutrition_cnn_roboflow/malnutrition_model_android.pt" ]; then
    cp runs/malnutrition_cnn_roboflow/malnutrition_model_android.pt app/src/main/assets/
    echo "✅ Model copied to Android assets"
else
    echo "❌ Android model not found"
fi
'''
        
        script_path = self.base_dir / "train_roboflow_model.sh"
        with open(script_path, "w") as f:
            f.write(script_content)
        
        os.chmod(script_path, 0o755)
        print(f"📜 Training script created: {script_path}")
    
    def run_pipeline(self):
        """Run the complete download and processing pipeline"""
        print("🚀 Roboflow Deteksi Stunting Dataset Pipeline")
        print("=" * 50)
        
        # Check if dataset exists
        if not self.check_existing_dataset():
            print("❌ Dataset not found. Please download manually.")
            self.create_download_instructions()
            return False
        
        # Extract if needed
        if not (self.collected_dir / "roboflow_stunting").exists():
            if not self.extract_dataset():
                return False
        
        # Process dataset
        if not self.process_dataset():
            return False
        
        # Generate report
        report = self.generate_dataset_report()
        
        # Create training script
        self.create_training_script()
        
        print("\n🎉 Dataset Processing Complete!")
        print("=" * 50)
        print("Next steps:")
        print("1. Review dataset_report.json for statistics")
        print("2. Run training: bash train_roboflow_model.sh")
        print("3. Model will be automatically copied to Android assets")
        print("4. Integrate with FavoritesActivity.java")
        
        return True


def main():
    base_dir = Path(".")
    downloader = RoboflowDownloader(base_dir)
    
    if not downloader.run_pipeline():
        print("\n📋 Manual Download Required")
        print("Please follow the instructions above to download the dataset manually.")
        print("Then run this script again to process it.")


if __name__ == "__main__":
    main()
