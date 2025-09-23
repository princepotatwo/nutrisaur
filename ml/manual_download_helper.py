#!/usr/bin/env python3
"""
Manual Download Helper for Roboflow Dataset
Since Roboflow has Cloudflare protection, this script helps you download manually
"""

import os
import sys
import zipfile
import shutil
from pathlib import Path
import json

def print_download_instructions():
    """Print clear instructions for manual download"""
    
    print("🌐 MANUAL DOWNLOAD INSTRUCTIONS")
    print("=" * 60)
    print()
    print("Since Roboflow has Cloudflare protection, please download manually:")
    print()
    print("1. 🌐 Open your web browser")
    print("2. 🔗 Go to: https://universe.roboflow.com/database-ayu/deteksi-stunting")
    print("3. 👤 Create a free Roboflow account if needed")
    print("4. 📥 Click 'Download Dataset' button")
    print("5. ⚙️  Select these options:")
    print("   - Format: YOLO")
    print("   - Size: 640 (recommended)")
    print("6. 💾 Save the downloaded ZIP file")
    print()
    print("📁 Expected file names:")
    print("   - deteksi-stunting-1.zip")
    print("   - or similar with 'deteksi-stunting' in the name")
    print()
    print("7. 📂 Copy the ZIP file to this location:")
    print(f"   {Path('collected_datasets').absolute()}/")
    print()
    print("8. 🔄 Then run this script again to process it")
    print()
    print("⏱️  This should take 2-5 minutes")
    print("💾 Expected file size: 50-100 MB")

def find_downloaded_files():
    """Find any downloaded Roboflow files"""
    
    collected_dir = Path("collected_datasets")
    if not collected_dir.exists():
        collected_dir.mkdir(parents=True, exist_ok=True)
    
    # Look for various possible file names
    possible_names = [
        "deteksi-stunting-1.zip",
        "deteksi-stunting.zip", 
        "roboflow_stunting.zip",
        "*deteksi*.zip",
        "*stunting*.zip"
    ]
    
    found_files = []
    
    for pattern in possible_names:
        if "*" in pattern:
            found_files.extend(list(collected_dir.glob(pattern)))
        else:
            file_path = collected_dir / pattern
            if file_path.exists():
                found_files.append(file_path)
    
    return found_files

def process_downloaded_file(zip_path):
    """Process a downloaded ZIP file"""
    
    print(f"\n🔄 Processing: {zip_path.name}")
    
    # Create extraction directory
    extract_dir = Path("collected_datasets/roboflow_stunting")
    if extract_dir.exists():
        shutil.rmtree(extract_dir)
    extract_dir.mkdir(parents=True, exist_ok=True)
    
    try:
        # Extract ZIP file
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(extract_dir)
        
        print(f"✅ Extracted to: {extract_dir}")
        
        # Check structure
        check_dataset_structure(extract_dir)
        
        # Process for training
        process_for_training(extract_dir)
        
        return True
        
    except Exception as e:
        print(f"❌ Error processing {zip_path}: {e}")
        return False

def check_dataset_structure(dataset_path):
    """Check the structure of the extracted dataset"""
    
    print(f"\n📊 Checking dataset structure...")
    
    # Look for common directory structures
    items = list(dataset_path.iterdir())
    directories = [d for d in items if d.is_dir()]
    files = [f for f in items if f.is_file()]
    
    print(f"📁 Directories found: {[d.name for d in directories]}")
    print(f"📄 Files found: {[f.name for f in files]}")
    
    # Look for train/val/test splits
    splits = ["train", "valid", "val", "test"]
    found_splits = [d for d in directories if d.name.lower() in splits]
    
    if found_splits:
        print(f"🎯 Found splits: {[d.name for d in found_splits]}")
        
        for split in found_splits:
            split_path = dataset_path / split
            print(f"\n📊 {split} directory contents:")
            
            # Check for images and labels subdirectories
            subdirs = [d for d in split_path.iterdir() if d.is_dir()]
            if subdirs:
                print(f"   📁 Subdirectories: {[d.name for d in subdirs]}")
                
                # Check if it's YOLO format
                if "images" in [d.name for d in subdirs] and "labels" in [d.name for d in subdirs]:
                    images_dir = split_path / "images"
                    labels_dir = split_path / "labels"
                    
                    image_files = list(images_dir.glob("*.jpg")) + list(images_dir.glob("*.png"))
                    label_files = list(labels_dir.glob("*.txt"))
                    
                    print(f"   🖼️  Images: {len(image_files)}")
                    print(f"   📄 Labels: {len(label_files)}")
                    
                    # Check first label file
                    if label_files:
                        try:
                            with open(label_files[0], 'r') as f:
                                content = f.read().strip()
                                if content:
                                    parts = content.split()
                                    if len(parts) >= 5:
                                        class_id = parts[0]
                                        print(f"   🏷️  Sample label class: {class_id}")
                        except:
                            pass
            else:
                # Check for class directories
                image_files = list(split_path.glob("*.jpg")) + list(split_path.glob("*.png"))
                print(f"   🖼️  Images: {len(image_files)}")
    
    return found_splits

def process_for_training(dataset_path):
    """Process the dataset for our training format"""
    
    print(f"\n🔄 Processing dataset for training...")
    
    # Create our training structure
    output_dir = Path("malnutrition_dataset")
    if output_dir.exists():
        shutil.rmtree(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Create class directories
    classes = ["normal", "moderate_acute_malnutrition", "stunting"]
    for split in ["train", "val"]:
        for class_name in classes:
            (output_dir / split / class_name).mkdir(parents=True, exist_ok=True)
    
    # Look for YOLO format first
    yolo_processed = process_yolo_format(dataset_path, output_dir)
    
    if not yolo_processed:
        # Try folder-based format
        process_folder_format(dataset_path, output_dir)
    
    # Generate final report
    generate_final_report(output_dir)

def process_yolo_format(dataset_path, output_dir):
    """Process YOLO format dataset"""
    
    print("🎯 Looking for YOLO format...")
    
    # Look for train/valid splits with images/labels subdirs
    splits = ["train", "valid", "val"]
    found_yolo = False
    
    for split_name in splits:
        split_dir = dataset_path / split_name
        if not split_dir.exists():
            continue
            
        images_dir = split_dir / "images"
        labels_dir = split_dir / "labels"
        
        if not (images_dir.exists() and labels_dir.exists()):
            continue
        
        found_yolo = True
        target_split = "train" if split_name == "train" else "val"
        
        print(f"📊 Processing YOLO {split_name} -> {target_split}")
        
        # Map YOLO class IDs to our class names
        # This may need adjustment based on actual dataset
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
    
    return found_yolo

def process_folder_format(dataset_path, output_dir):
    """Process folder-based dataset structure"""
    
    print("📁 Processing folder-based structure...")
    
    # Look for class directories
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

def generate_final_report(output_dir):
    """Generate final dataset report"""
    
    print(f"\n📊 Final Dataset Report")
    print("=" * 40)
    
    total_images = 0
    class_counts = {}
    
    for split in ["train", "val"]:
        split_dir = output_dir / split
        if split_dir.exists():
            split_count = 0
            for class_dir in split_dir.iterdir():
                if class_dir.is_dir():
                    image_count = len(list(class_dir.glob("*.jpg")) + list(class_dir.glob("*.png")))
                    split_count += image_count
                    
                    if class_dir.name not in class_counts:
                        class_counts[class_dir.name] = {"train": 0, "val": 0}
                    class_counts[class_dir.name][split] = image_count
                    
                    print(f"  {split}/{class_dir.name}: {image_count} images")
            
            total_images += split_count
            print(f"  {split} total: {split_count} images")
    
    print(f"\n📊 Total images: {total_images}")
    
    # Save report
    report = {
        "total_images": total_images,
        "class_distribution": class_counts,
        "dataset_type": "roboflow_deteksi_stunting"
    }
    
    with open(output_dir / "dataset_report.json", "w") as f:
        json.dump(report, f, indent=2)
    
    if total_images > 0:
        print("\n🎉 Dataset ready for training!")
        print("Run: python ml/train_simple_cnn.py")
    else:
        print("\n❌ No images found in dataset")

def main():
    """Main function"""
    
    print("🚀 Roboflow Manual Download Helper")
    print("=" * 60)
    
    # Check for existing downloads
    found_files = find_downloaded_files()
    
    if found_files:
        print(f"✅ Found {len(found_files)} downloaded file(s):")
        for file_path in found_files:
            size_mb = file_path.stat().st_size / (1024 * 1024)
            print(f"   📁 {file_path.name} ({size_mb:.1f} MB)")
        
        print("\n🔄 Processing downloaded files...")
        
        success = False
        for file_path in found_files:
            if file_path.suffix == '.zip':
                if process_downloaded_file(file_path):
                    success = True
                    break
        
        if success:
            print("\n🎉 Dataset processing complete!")
        else:
            print("\n❌ Failed to process downloaded files")
    
    else:
        print("❌ No downloaded files found")
        print_download_instructions()

if __name__ == "__main__":
    main()
