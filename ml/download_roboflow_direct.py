#!/usr/bin/env python3
"""
Direct Roboflow Dataset Download Script
Downloads the Deteksi Stunting dataset from the provided URL
"""

import os
import sys
import requests
import zipfile
import shutil
from pathlib import Path
import time

def download_roboflow_dataset():
    """Download the Roboflow Deteksi Stunting dataset"""
    
    print("🌐 Downloading Roboflow Deteksi Stunting Dataset")
    print("=" * 60)
    
    # Dataset URL provided by user
    dataset_url = "https://universe.roboflow.com/database-ayu/deteksi-stunting"
    
    print(f"📋 Dataset URL: {dataset_url}")
    print("\n📝 Manual Download Required:")
    print("Since Roboflow requires authentication, please follow these steps:")
    print()
    print("1. 🌐 Open your browser and go to:")
    print(f"   {dataset_url}")
    print()
    print("2. 👤 Create a free Roboflow account if you don't have one")
    print()
    print("3. 📥 Download the dataset:")
    print("   - Click 'Download Dataset'")
    print("   - Select format: 'YOLO'")
    print("   - Select size: '640' (recommended)")
    print("   - Click 'Download ZIP'")
    print()
    print("4. 💾 Save the downloaded file as 'roboflow_stunting.zip'")
    print("   Save it to: /Users/jasminpingol/Downloads/thesis75/nutrisaur11/collected_datasets/")
    print()
    print("5. 🔄 Run this script again to process the downloaded dataset")
    print()
    print("⏱️  This should take 2-5 minutes depending on your internet speed")
    print("💾 Expected file size: 50-100 MB")
    
    # Check if file already exists
    zip_path = Path("collected_datasets/roboflow_stunting.zip")
    if zip_path.exists():
        print(f"\n✅ Found existing download: {zip_path}")
        print("Processing existing dataset...")
        return process_downloaded_dataset(zip_path)
    else:
        print(f"\n❌ Dataset not found at: {zip_path}")
        print("Please download manually as instructed above.")
        return False

def process_downloaded_dataset(zip_path):
    """Process the downloaded Roboflow dataset"""
    
    print(f"\n🔄 Processing dataset: {zip_path}")
    
    # Create extraction directory
    extract_path = Path("collected_datasets/roboflow_stunting")
    extract_path.mkdir(parents=True, exist_ok=True)
    
    try:
        # Extract ZIP file
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(extract_path)
        
        print(f"✅ Dataset extracted to: {extract_path}")
        
        # Check structure
        check_dataset_structure(extract_path)
        
        # Process for our model
        process_for_training(extract_path)
        
        return True
        
    except Exception as e:
        print(f"❌ Error processing dataset: {e}")
        return False

def check_dataset_structure(dataset_path):
    """Check the structure of the downloaded dataset"""
    
    print("\n📊 Checking dataset structure...")
    
    # Common Roboflow structures
    possible_structures = [
        ["train", "valid", "test"],
        ["train", "val", "test"],
        ["images", "labels"],
        ["data", "labels"]
    ]
    
    found_structure = []
    
    for item in dataset_path.iterdir():
        if item.is_dir():
            found_structure.append(item.name)
            print(f"📁 Found directory: {item.name}")
            
            # Check subdirectories
            for subitem in item.iterdir():
                if subitem.is_dir():
                    print(f"   📂 Subdirectory: {subitem.name}")
                elif subitem.suffix.lower() in ['.jpg', '.jpeg', '.png']:
                    print(f"   🖼️  Image file: {subitem.name}")
                elif subitem.suffix.lower() in ['.txt', '.json', '.xml']:
                    print(f"   📄 Label file: {subitem.name}")
    
    print(f"\n📋 Detected structure: {found_structure}")
    
    # Check for YOLO format
    yolo_files = list(dataset_path.rglob("*.txt"))
    if yolo_files:
        print(f"🎯 Found {len(yolo_files)} YOLO label files")
        # Check first label file
        try:
            with open(yolo_files[0], 'r') as f:
                content = f.read().strip()
                if content:
                    parts = content.split()
                    if len(parts) >= 5:
                        class_id = parts[0]
                        print(f"📊 Sample label: class {class_id}")
        except:
            pass
    
    # Check for images
    image_files = list(dataset_path.rglob("*.jpg")) + list(dataset_path.rglob("*.png"))
    print(f"🖼️  Found {len(image_files)} image files")
    
    return found_structure

def process_for_training(dataset_path):
    """Process the dataset for our training format"""
    
    print("\n🔄 Processing dataset for training...")
    
    # Create our training structure
    output_dir = Path("malnutrition_dataset")
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Create class directories
    classes = ["normal", "moderate_acute_malnutrition", "stunting"]
    for split in ["train", "val"]:
        for class_name in classes:
            (output_dir / split / class_name).mkdir(parents=True, exist_ok=True)
    
    # Process YOLO format if found
    yolo_files = list(dataset_path.rglob("*.txt"))
    image_files = list(dataset_path.rglob("*.jpg")) + list(dataset_path.rglob("*.png"))
    
    if yolo_files and image_files:
        print("🎯 Processing YOLO format dataset...")
        process_yolo_dataset(dataset_path, output_dir, classes)
    else:
        print("📁 Processing folder-based dataset...")
        process_folder_dataset(dataset_path, output_dir, classes)
    
    print("✅ Dataset processing complete!")

def process_yolo_dataset(dataset_path, output_dir, classes):
    """Process YOLO format dataset"""
    
    # Map YOLO class IDs to our class names
    class_mapping = {
        0: "normal",  # Assuming class 0 is healthy/normal
        1: "moderate_acute_malnutrition",  # Assuming class 1 is malnutrition
        2: "stunting"  # Assuming class 2 is stunting
    }
    
    processed_count = 0
    
    # Process each split
    for split_name in ["train", "valid", "val"]:
        split_dir = dataset_path / split_name
        if not split_dir.exists():
            continue
            
        images_dir = split_dir / "images"
        labels_dir = split_dir / "labels"
        
        if not (images_dir.exists() and labels_dir.exists()):
            continue
        
        target_split = "train" if split_name == "train" else "val"
        
        print(f"📊 Processing {split_name} split...")
        
        for image_file in images_dir.iterdir():
            if image_file.suffix.lower() not in ['.jpg', '.jpeg', '.png']:
                continue
                
            label_file = labels_dir / f"{image_file.stem}.txt"
            
            if label_file.exists():
                try:
                    # Read YOLO label
                    with open(label_file, 'r') as f:
                        lines = f.readlines()
                    
                    if lines:
                        # Get first class (assuming single object per image)
                        class_id = int(lines[0].split()[0])
                        if class_id in class_mapping:
                            target_class = class_mapping[class_id]
                            
                            # Copy image to target directory
                            target_path = output_dir / target_split / target_class / image_file.name
                            shutil.copy2(image_file, target_path)
                            processed_count += 1
                            
                except Exception as e:
                    print(f"⚠️ Error processing {image_file}: {e}")
    
    print(f"✅ Processed {processed_count} images")

def process_folder_dataset(dataset_path, output_dir, classes):
    """Process folder-based dataset"""
    
    processed_count = 0
    
    # Look for class folders
    for item in dataset_path.iterdir():
        if not item.is_dir():
            continue
            
        class_name = item.name.lower()
        
        # Map class names
        target_class = None
        if any(keyword in class_name for keyword in ["healthy", "normal"]):
            target_class = "normal"
        elif any(keyword in class_name for keyword in ["malnutrisi", "malnutrition"]):
            target_class = "moderate_acute_malnutrition"
        elif "stunting" in class_name:
            target_class = "stunting"
        
        if target_class is None:
            continue
        
        # Process images
        image_files = list(item.glob("*.jpg")) + list(item.glob("*.png"))
        
        # Split into train/val (80/20)
        train_count = int(len(image_files) * 0.8)
        
        for i, image_file in enumerate(image_files):
            target_split = "train" if i < train_count else "val"
            target_path = output_dir / target_split / target_class / image_file.name
            shutil.copy2(image_file, target_path)
            processed_count += 1
        
        print(f"✅ Processed {len(image_files)} images from {item.name} -> {target_class}")
    
    print(f"✅ Total processed: {processed_count} images")

def main():
    """Main function"""
    
    print("🚀 Roboflow Deteksi Stunting Dataset Downloader")
    print("=" * 60)
    
    success = download_roboflow_dataset()
    
    if success:
        print("\n🎉 Dataset download and processing complete!")
        print("\n📋 Next steps:")
        print("1. Review the processed dataset structure")
        print("2. Train model with real data: python ml/train_simple_cnn.py")
        print("3. Integrate with your Android app")
    else:
        print("\n📋 Manual download required:")
        print("Please download the dataset from the provided URL")
        print("Then run this script again to process it")

if __name__ == "__main__":
    main()
