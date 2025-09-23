#!/usr/bin/env python3
"""
Download Roboflow Dataset using API
Downloads the Deteksi Stunting dataset from the provided URL
"""

import os
import sys
from pathlib import Path
import shutil

try:
    from roboflow import Roboflow
except ImportError:
    print("❌ Roboflow package not installed. Installing...")
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "roboflow"])
    from roboflow import Roboflow

def download_roboflow_dataset():
    """Download the Roboflow Deteksi Stunting dataset"""
    
    print("🌐 Downloading Roboflow Deteksi Stunting Dataset via API")
    print("=" * 60)
    
    try:
        # Initialize Roboflow (using public API key for public datasets)
        rf = Roboflow(api_key="your-api-key-here")  # Will use public access
        
        # Extract project info from URL
        workspace = "database-ayu"
        project_name = "deteksi-stunting"
        version = 1  # Usually version 1 for most projects
        
        print(f"📋 Project: {workspace}/{project_name}")
        print(f"🔗 URL: https://universe.roboflow.com/{workspace}/{project_name}")
        
        # Try to access the project
        try:
            project = rf.workspace(workspace).project(project_name)
            print(f"✅ Successfully accessed project: {project}")
            
            # Get dataset version
            dataset = project.version(version)
            print(f"✅ Successfully accessed dataset version: {version}")
            
            # Download dataset
            print("📥 Downloading dataset...")
            dataset.download("yolo", location="collected_datasets/")
            
            print("✅ Dataset downloaded successfully!")
            return True
            
        except Exception as e:
            print(f"❌ Error accessing project: {e}")
            print("\n🔑 This dataset may require authentication.")
            print("Please try the manual download method:")
            print_manual_instructions()
            return False
            
    except Exception as e:
        print(f"❌ Error with Roboflow API: {e}")
        print_manual_instructions()
        return False

def print_manual_instructions():
    """Print manual download instructions"""
    
    print("\n📋 Manual Download Instructions:")
    print("=" * 40)
    print("1. 🌐 Open your browser and go to:")
    print("   https://universe.roboflow.com/database-ayu/deteksi-stunting")
    print()
    print("2. 👤 Create a free Roboflow account if you don't have one")
    print()
    print("3. 📥 Download the dataset:")
    print("   - Click 'Download Dataset' button")
    print("   - Select format: 'YOLO'")
    print("   - Select size: '640' (recommended)")
    print("   - Click 'Download ZIP'")
    print()
    print("4. 💾 Save the downloaded file as 'deteksi-stunting-1.zip'")
    print("   Save it to: /Users/jasminpingol/Downloads/thesis75/nutrisaur11/collected_datasets/")
    print()
    print("5. 🔄 Run the processing script:")
    print("   python ml/process_roboflow_download.py")

def process_downloaded_roboflow():
    """Process the downloaded Roboflow dataset"""
    
    print("\n🔄 Processing downloaded Roboflow dataset...")
    
    # Look for downloaded files
    collected_dir = Path("collected_datasets")
    roboflow_files = list(collected_dir.glob("*deteksi-stunting*")) + list(collected_dir.glob("*roboflow*"))
    
    if not roboflow_files:
        print("❌ No Roboflow files found in collected_datasets/")
        return False
    
    print(f"📁 Found files: {[f.name for f in roboflow_files]}")
    
    # Process each found file
    for file_path in roboflow_files:
        if file_path.is_file() and file_path.suffix == '.zip':
            print(f"📦 Processing ZIP file: {file_path.name}")
            
            # Extract
            extract_path = collected_dir / "roboflow_stunting"
            extract_path.mkdir(parents=True, exist_ok=True)
            
            import zipfile
            with zipfile.ZipFile(file_path, 'r') as zip_ref:
                zip_ref.extractall(extract_path)
            
            print(f"✅ Extracted to: {extract_path}")
            
            # Check structure
            check_and_process_structure(extract_path)
            
            return True
    
    return False

def check_and_process_structure(dataset_path):
    """Check and process the dataset structure"""
    
    print(f"\n📊 Checking dataset structure in: {dataset_path}")
    
    # Look for common structures
    found_dirs = [d for d in dataset_path.iterdir() if d.is_dir()]
    print(f"📁 Found directories: {[d.name for d in found_dirs]}")
    
    # Look for train/val/test splits
    splits = ["train", "valid", "val", "test"]
    found_splits = [d for d in found_dirs if d.name.lower() in splits]
    
    if found_splits:
        print(f"🎯 Found splits: {[d.name for d in found_splits]}")
        process_yolo_structure(dataset_path)
    else:
        print("📁 No standard splits found, looking for class directories...")
        process_class_structure(dataset_path)

def process_yolo_structure(dataset_path):
    """Process YOLO format dataset"""
    
    print("\n🎯 Processing YOLO format dataset...")
    
    # Create our training structure
    output_dir = Path("malnutrition_dataset")
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Clear existing data
    if output_dir.exists():
        shutil.rmtree(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Create class directories
    classes = ["normal", "moderate_acute_malnutrition", "stunting"]
    for split in ["train", "val"]:
        for class_name in classes:
            (output_dir / split / class_name).mkdir(parents=True, exist_ok=True)
    
    # Process each split
    for split_name in ["train", "valid", "val"]:
        split_dir = dataset_path / split_name
        if not split_dir.exists():
            continue
            
        images_dir = split_dir / "images"
        labels_dir = split_dir / "labels"
        
        if not (images_dir.exists() and labels_dir.exists()):
            print(f"⚠️ {split_name} directory missing images or labels")
            continue
        
        target_split = "train" if split_name == "train" else "val"
        print(f"📊 Processing {split_name} -> {target_split}")
        
        # Map YOLO class IDs to our class names
        # This mapping may need adjustment based on actual dataset
        class_mapping = {
            0: "normal",  # Assuming class 0 is healthy
            1: "moderate_acute_malnutrition",  # Assuming class 1 is malnutrition
            2: "stunting"  # Assuming class 2 is stunting
        }
        
        processed_count = 0
        
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
                        else:
                            print(f"⚠️ Unknown class ID {class_id} in {image_file.name}")
                            
                except Exception as e:
                    print(f"⚠️ Error processing {image_file}: {e}")
        
        print(f"✅ Processed {processed_count} images for {split_name}")

def process_class_structure(dataset_path):
    """Process class-based directory structure"""
    
    print("\n📁 Processing class-based structure...")
    
    # Create our training structure
    output_dir = Path("malnutrition_dataset")
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Clear existing data
    if output_dir.exists():
        shutil.rmtree(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Create class directories
    classes = ["normal", "moderate_acute_malnutrition", "stunting"]
    for split in ["train", "val"]:
        for class_name in classes:
            (output_dir / split / class_name).mkdir(parents=True, exist_ok=True)
    
    # Map class names
    class_mapping = {
        "healthy": "normal",
        "normal": "normal",
        "malnutrisi": "moderate_acute_malnutrition",
        "malnutrition": "moderate_acute_malnutrition",
        "stunting": "stunting"
    }
    
    processed_count = 0
    
    for item in dataset_path.iterdir():
        if not item.is_dir():
            continue
            
        class_name = item.name.lower()
        print(f"📁 Processing directory: {class_name}")
        
        # Find matching class
        target_class = None
        for key, value in class_mapping.items():
            if key in class_name:
                target_class = value
                break
        
        if target_class is None:
            print(f"⚠️ Unknown class: {class_name}")
            continue
        
        # Process images
        image_files = list(item.glob("*.jpg")) + list(item.glob("*.png"))
        print(f"🖼️ Found {len(image_files)} images")
        
        # Split into train/val (80/20)
        train_count = int(len(image_files) * 0.8)
        
        for i, image_file in enumerate(image_files):
            target_split = "train" if i < train_count else "val"
            target_path = output_dir / target_split / target_class / image_file.name
            shutil.copy2(image_file, target_path)
            processed_count += 1
        
        print(f"✅ Mapped {len(image_files)} images from {class_name} -> {target_class}")
    
    print(f"✅ Total processed: {processed_count} images")

def main():
    """Main function"""
    
    print("🚀 Roboflow Deteksi Stunting Dataset Downloader")
    print("=" * 60)
    
    # Try API download first
    if download_roboflow_dataset():
        print("✅ API download successful!")
    else:
        print("⚠️ API download failed, checking for manual downloads...")
        
        # Check for manually downloaded files
        if process_downloaded_roboflow():
            print("✅ Manual download processing successful!")
        else:
            print("❌ No dataset found. Please download manually.")
    
    # Generate final report
    output_dir = Path("malnutrition_dataset")
    if output_dir.exists():
        print(f"\n📊 Final Dataset Report:")
        print(f"📁 Dataset location: {output_dir.absolute()}")
        
        total_images = 0
        for split in ["train", "val"]:
            split_dir = output_dir / split
            if split_dir.exists():
                split_count = 0
                for class_dir in split_dir.iterdir():
                    if class_dir.is_dir():
                        image_count = len(list(class_dir.glob("*.jpg")) + list(class_dir.glob("*.png")))
                        split_count += image_count
                        print(f"  {split}/{class_dir.name}: {image_count} images")
                total_images += split_count
                print(f"  {split} total: {split_count} images")
        
        print(f"📊 Total images: {total_images}")
        
        if total_images > 0:
            print("\n🎉 Dataset ready for training!")
            print("Run: python ml/train_simple_cnn.py")
        else:
            print("\n❌ No images found in dataset")
    else:
        print("\n❌ Dataset not created")

if __name__ == "__main__":
    main()
