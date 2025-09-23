#!/usr/bin/env python3
"""
Download and Process Roboflow Deteksi Stunting Dataset
Direct access to the dataset you provided: https://universe.roboflow.com/database-ayu/deteksi-stunting
"""

import os
import sys
import requests
import json
from pathlib import Path
import zipfile
import shutil

class RoboflowDatasetDownloader:
    def __init__(self, base_dir: Path):
        self.base_dir = base_dir
        self.dataset_url = "https://universe.roboflow.com/database-ayu/deteksi-stunting"
        self.output_dir = base_dir / "malnutrition_dataset"
        
        # Create output directory structure
        self.setup_directories()
        
        # Dataset information from the Roboflow page
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
        """Create the directory structure for the dataset"""
        directories = [
            "malnutrition_dataset/train/normal",
            "malnutrition_dataset/train/moderate_acute_malnutrition", 
            "malnutrition_dataset/train/stunting",
            "malnutrition_dataset/val/normal",
            "malnutrition_dataset/val/moderate_acute_malnutrition",
            "malnutrition_dataset/val/stunting",
            "collected_datasets/roboflow_stunting"
        ]
        
        for dir_path in directories:
            Path(dir_path).mkdir(parents=True, exist_ok=True)
        
        print("📁 Directory structure created")
    
    def download_dataset_instructions(self):
        """Provide instructions for downloading the dataset"""
        instructions = f"""
🚀 Roboflow Deteksi Stunting Dataset Download Instructions
========================================================

Dataset URL: {self.dataset_url}

📋 Step-by-Step Instructions:
1. Visit the URL above in your browser
2. Create a free Roboflow account (if you don't have one)
3. Click "Download Dataset" button
4. Select format: "YOLO" 
5. Choose "Download ZIP"
6. Save the file as: roboflow_stunting.zip
7. Place it in: {self.base_dir}/collected_datasets/
8. Run this script again to process the dataset

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
collected_datasets/
└── roboflow_stunting/
    ├── train/
    │   ├── images/
    │   └── labels/
    └── valid/
        ├── images/
        └── labels/
"""
        print(instructions)
        return instructions
    
    def check_downloaded_dataset(self):
        """Check if the dataset has been downloaded"""
        dataset_path = self.base_dir / "collected_datasets" / "roboflow_stunting.zip"
        extracted_path = self.base_dir / "collected_datasets" / "roboflow_stunting"
        
        if dataset_path.exists():
            print(f"✅ Found dataset ZIP: {dataset_path}")
            return True
        elif extracted_path.exists():
            print(f"✅ Found extracted dataset: {extracted_path}")
            return True
        else:
            print(f"❌ Dataset not found. Please download from: {self.dataset_url}")
            return False
    
    def extract_dataset(self):
        """Extract the downloaded dataset"""
        zip_path = self.base_dir / "collected_datasets" / "roboflow_stunting.zip"
        extract_path = self.base_dir / "collected_datasets" / "roboflow_stunting"
        
        if not zip_path.exists():
            print("❌ ZIP file not found")
            return False
        
        print(f"📦 Extracting dataset from {zip_path}")
        
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(extract_path)
        
        print(f"✅ Dataset extracted to {extract_path}")
        return True
    
    def process_yolo_dataset(self):
        """Process YOLO format dataset into our structure"""
        source_path = self.base_dir / "collected_datasets" / "roboflow_stunting"
        
        if not source_path.exists():
            print("❌ Extracted dataset not found")
            return False
        
        print("🔄 Processing YOLO format dataset...")
        
        # Process train and validation splits
        for split in ["train", "valid"]:
            if split == "valid":
                target_split = "val"
            else:
                target_split = split
            
            images_dir = source_path / split / "images"
            labels_dir = source_path / split / "labels"
            
            if not (images_dir.exists() and labels_dir.exists()):
                print(f"⚠️ {split} directory structure not found, checking for other formats...")
                self.process_alternative_formats(source_path, target_split)
                continue
            
            self.process_yolo_split(images_dir, labels_dir, target_split)
        
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

# Install dependencies if needed
pip install torch torchvision matplotlib scikit-learn pillow tqdm seaborn

# Train the model
python ml/train_malnutrition_cnn.py \\
    --data_dir malnutrition_dataset \\
    --output_dir runs/malnutrition_cnn_roboflow \\
    --epochs 30 \\
    --batch_size 32 \\
    --lr 1e-4 \\
    --model efficientnet_b0 \\
    --img_size 224 \\
    --val_split 0.2 \\
    --seed 42

echo "✅ Training completed!"
echo "📱 Model saved to: runs/malnutrition_cnn_roboflow/best_model.pt"
echo "📱 Android model: runs/malnutrition_cnn_roboflow/malnutrition_model_android.pt"
'''
        
        script_path = self.base_dir / "train_roboflow_model.sh"
        with open(script_path, "w") as f:
            f.write(script_content)
        
        os.chmod(script_path, 0o755)
        print(f"📜 Training script created: {script_path}")
    
    def run_download_and_process(self):
        """Run the complete download and processing pipeline"""
        print("🚀 Roboflow Deteksi Stunting Dataset Pipeline")
        print("=" * 50)
        
        # Check if dataset exists
        if not self.check_downloaded_dataset():
            self.download_dataset_instructions()
            return False
        
        # Extract if needed
        if not (self.base_dir / "collected_datasets" / "roboflow_stunting").exists():
            self.extract_dataset()
        
        # Process dataset
        self.process_yolo_dataset()
        
        # Generate report
        report = self.generate_dataset_report()
        
        # Create training script
        self.create_training_script()
        
        print("\n🎉 Dataset Processing Complete!")
        print("=" * 50)
        print("Next steps:")
        print("1. Review dataset_report.json for statistics")
        print("2. Run training: bash train_roboflow_model.sh")
        print("3. Integrate model with FavoritesActivity.java")
        
        return True


def main():
    base_dir = Path(".")
    downloader = RoboflowDatasetDownloader(base_dir)
    downloader.run_download_and_process()


if __name__ == "__main__":
    main()
