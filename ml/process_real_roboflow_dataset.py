#!/usr/bin/env python3
"""
Process Real Roboflow Deteksi Stunting Dataset
Converts YOLO format to our CNN training format
"""

import os
import sys
import shutil
from pathlib import Path
import json
import random

def process_roboflow_dataset():
    """Process the real Roboflow dataset"""
    
    print("🚀 Processing Real Roboflow Deteksi Stunting Dataset")
    print("=" * 60)
    
    # Source dataset path
    source_path = Path("collected_datasets/Deteksi Stunting.v1i.yolov5pytorch")
    
    if not source_path.exists():
        print(f"❌ Dataset not found at: {source_path}")
        return False
    
    print(f"📁 Source dataset: {source_path}")
    
    # Create output directory
    output_dir = Path("malnutrition_dataset")
    if output_dir.exists():
        shutil.rmtree(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Create class directories
    classes = ["normal", "moderate_acute_malnutrition", "stunting"]
    for split in ["train", "val"]:
        for class_name in classes:
            (output_dir / split / class_name).mkdir(parents=True, exist_ok=True)
    
    # Class mapping from YOLO to our format
    class_mapping = {
        0: "normal",                      # Healthy
        1: "moderate_acute_malnutrition", # MalNutrisi  
        2: "stunting"                     # Stunting
    }
    
    # Process train and validation splits
    splits = [
        ("train", "train"),
        ("valid", "val")
    ]
    
    total_processed = 0
    
    for source_split, target_split in splits:
        print(f"\n📊 Processing {source_split} split...")
        
        images_dir = source_path / source_split / "images"
        labels_dir = source_path / source_split / "labels"
        
        if not (images_dir.exists() and labels_dir.exists()):
            print(f"⚠️ {source_split} directory missing images or labels")
            continue
        
        processed_count = 0
        class_counts = {0: 0, 1: 0, 2: 0}  # Track counts per class
        
        # Process each image
        for image_file in images_dir.iterdir():
            if image_file.suffix.lower() not in ['.jpg', '.jpeg', '.png']:
                continue
                
            label_file = labels_dir / f"{image_file.stem}.txt"
            
            if label_file.exists():
                try:
                    # Read YOLO label file
                    with open(label_file, 'r') as f:
                        lines = f.readlines()
                    
                    if lines:
                        # Get the first class (assuming single object per image)
                        class_id = int(lines[0].split()[0])
                        if class_id in class_mapping:
                            target_class = class_mapping[class_id]
                            
                            # Copy image to target directory
                            target_path = output_dir / target_split / target_class / image_file.name
                            shutil.copy2(image_file, target_path)
                            processed_count += 1
                            class_counts[class_id] += 1
                        else:
                            print(f"⚠️ Unknown class ID {class_id} in {image_file.name}")
                            
                except Exception as e:
                    print(f"⚠️ Error processing {image_file}: {e}")
        
        print(f"✅ Processed {processed_count} images for {source_split}")
        print(f"   - Healthy (class 0): {class_counts[0]} images")
        print(f"   - MalNutrisi (class 1): {class_counts[1]} images") 
        print(f"   - Stunting (class 2): {class_counts[2]} images")
        
        total_processed += processed_count
    
    print(f"\n🎉 Total processed: {total_processed} images")
    
    # Generate final report
    generate_dataset_report(output_dir)
    
    return True

def generate_dataset_report(output_dir):
    """Generate dataset report"""
    
    print(f"\n📊 Final Dataset Report")
    print("=" * 40)
    
    report = {
        "dataset_type": "roboflow_deteksi_stunting_real",
        "total_images": 0,
        "classes": {},
        "splits": {"train": 0, "val": 0}
    }
    
    total_images = 0
    
    for split in ["train", "val"]:
        split_dir = output_dir / split
        if split_dir.exists():
            split_count = 0
            for class_dir in split_dir.iterdir():
                if class_dir.is_dir():
                    class_name = class_dir.name
                    image_count = len(list(class_dir.glob("*.jpg")) + list(class_dir.glob("*.png")))
                    split_count += image_count
                    
                    if class_name not in report["classes"]:
                        report["classes"][class_name] = {"train": 0, "val": 0}
                    
                    report["classes"][class_name][split] = image_count
                    print(f"  {split}/{class_name}: {image_count} images")
            
            total_images += split_count
            report["splits"][split] = split_count
            print(f"  {split} total: {split_count} images")
    
    report["total_images"] = total_images
    
    # Save report
    with open(output_dir / "dataset_report.json", "w") as f:
        json.dump(report, f, indent=2)
    
    print(f"\n📊 Total images: {total_images}")
    print(f"📄 Report saved to: {output_dir / 'dataset_report.json'}")
    
    if total_images > 0:
        print("\n🎉 Real dataset ready for training!")
        print("Run: python ml/train_simple_cnn.py")
    else:
        print("\n❌ No images found in dataset")
    
    return report

def main():
    """Main function"""
    
    success = process_roboflow_dataset()
    
    if success:
        print("\n🎉 Dataset processing complete!")
        print("\n📋 Next steps:")
        print("1. Train model with real data")
        print("2. Export Android model")
        print("3. Integrate with your app")
    else:
        print("\n❌ Dataset processing failed")

if __name__ == "__main__":
    main()
