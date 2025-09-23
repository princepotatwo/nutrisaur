#!/usr/bin/env python3
"""
Create Synthetic Malnutrition Dataset for Testing
This creates a test dataset while you download the real Roboflow dataset
"""

import os
import sys
import numpy as np
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
import random
import shutil

class SyntheticDatasetCreator:
    def __init__(self, base_dir: Path):
        self.base_dir = base_dir
        self.output_dir = base_dir / "malnutrition_dataset"
        self.image_size = (224, 224)
        
        # Create directories
        self.setup_directories()
        
        # Class information
        self.classes = {
            "normal": {"color": (0, 255, 0), "description": "Healthy child"},
            "moderate_acute_malnutrition": {"color": (255, 165, 0), "description": "Moderate malnutrition"},
            "stunting": {"color": (255, 0, 0), "description": "Stunted growth"}
        }
    
    def setup_directories(self):
        """Create directory structure"""
        directories = [
            "malnutrition_dataset/train/normal",
            "malnutrition_dataset/train/moderate_acute_malnutrition",
            "malnutrition_dataset/train/stunting",
            "malnutrition_dataset/val/normal",
            "malnutrition_dataset/val/moderate_acute_malnutrition",
            "malnutrition_dataset/val/stunting"
        ]
        
        for dir_path in directories:
            Path(dir_path).mkdir(parents=True, exist_ok=True)
        
        print("📁 Directory structure created")
    
    def create_synthetic_image(self, class_name: str, image_id: int):
        """Create a synthetic image for a given class"""
        # Create base image
        img = Image.new('RGB', self.image_size, color='white')
        draw = ImageDraw.Draw(img)
        
        class_info = self.classes[class_name]
        color = class_info["color"]
        description = class_info["description"]
        
        # Draw a simple figure representing a child
        # Body
        body_width = 60
        body_height = 100
        body_x = (self.image_size[0] - body_width) // 2
        body_y = (self.image_size[1] - body_height) // 2
        
        # Adjust body size based on class
        if class_name == "normal":
            # Normal proportions
            pass
        elif class_name == "moderate_acute_malnutrition":
            # Slightly thinner
            body_width = int(body_width * 0.8)
            body_height = int(body_height * 0.9)
        elif class_name == "stunting":
            # Shorter and thinner
            body_width = int(body_width * 0.7)
            body_height = int(body_height * 0.7)
        
        # Recalculate position
        body_x = (self.image_size[0] - body_width) // 2
        body_y = (self.image_size[1] - body_height) // 2
        
        # Draw body
        draw.rectangle([body_x, body_y, body_x + body_width, body_y + body_height], 
                      fill=color, outline=(0, 0, 0), width=2)
        
        # Draw head
        head_radius = body_width // 3
        head_x = body_x + body_width // 2
        head_y = body_y - head_radius - 5
        draw.ellipse([head_x - head_radius, head_y - head_radius, 
                     head_x + head_radius, head_y + head_radius], 
                    fill=color, outline=(0, 0, 0), width=2)
        
        # Draw arms
        arm_length = body_height // 3
        arm_y = body_y + 20
        
        # Left arm
        draw.line([body_x, arm_y, body_x - arm_length, arm_y], fill=(0, 0, 0), width=3)
        # Right arm
        draw.line([body_x + body_width, arm_y, body_x + body_width + arm_length, arm_y], fill=(0, 0, 0), width=3)
        
        # Draw legs
        leg_length = body_height // 2
        leg_y = body_y + body_height
        
        # Left leg
        draw.line([body_x + body_width // 3, leg_y, body_x + body_width // 3, leg_y + leg_length], fill=(0, 0, 0), width=3)
        # Right leg
        draw.line([body_x + 2 * body_width // 3, leg_y, body_x + 2 * body_width // 3, leg_y + leg_length], fill=(0, 0, 0), width=3)
        
        # Add some variation
        variation = random.randint(-10, 10)
        if variation != 0:
            img = img.rotate(variation, fillcolor='white')
        
        # Add class label
        try:
            # Try to use default font
            font = ImageFont.load_default()
        except:
            font = None
        
        # Draw class label
        label_text = f"{class_name.replace('_', ' ').title()}"
        if font:
            bbox = draw.textbbox((0, 0), label_text, font=font)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]
        else:
            text_width = len(label_text) * 6
            text_height = 10
        
        text_x = (self.image_size[0] - text_width) // 2
        text_y = self.image_size[1] - 20
        
        draw.rectangle([text_x - 2, text_y - 2, text_x + text_width + 2, text_y + text_height + 2], 
                      fill='white', outline='black')
        
        if font:
            draw.text((text_x, text_y), label_text, fill='black', font=font)
        else:
            draw.text((text_x, text_y), label_text, fill='black')
        
        return img
    
    def create_dataset(self, images_per_class: int = 50):
        """Create synthetic dataset"""
        print(f"🎨 Creating synthetic dataset with {images_per_class} images per class...")
        
        # Split ratio: 80% train, 20% val
        train_count = int(images_per_class * 0.8)
        val_count = images_per_class - train_count
        
        for class_name in self.classes.keys():
            print(f"📊 Creating images for class: {class_name}")
            
            # Create training images
            for i in range(train_count):
                img = self.create_synthetic_image(class_name, i)
                img_path = self.output_dir / "train" / class_name / f"train_{i:03d}.jpg"
                img.save(img_path, "JPEG", quality=90)
            
            # Create validation images
            for i in range(val_count):
                img = self.create_synthetic_image(class_name, i + train_count)
                img_path = self.output_dir / "val" / class_name / f"val_{i:03d}.jpg"
                img.save(img_path, "JPEG", quality=90)
            
            print(f"✅ Created {train_count} train + {val_count} val images for {class_name}")
        
        total_images = len(self.classes) * images_per_class
        print(f"🎉 Synthetic dataset created with {total_images} total images")
    
    def generate_report(self):
        """Generate dataset report"""
        report = {
            "dataset_type": "synthetic",
            "total_images": 0,
            "classes": {},
            "splits": {"train": 0, "val": 0}
        }
        
        # Count images
        for split in ["train", "val"]:
            split_dir = self.output_dir / split
            if split_dir.exists():
                for class_dir in split_dir.iterdir():
                    if class_dir.is_dir():
                        class_name = class_dir.name
                        image_count = len(list(class_dir.glob("*.jpg")))
                        
                        if class_name not in report["classes"]:
                            report["classes"][class_name] = {"train": 0, "val": 0}
                        
                        report["classes"][class_name][split] = image_count
                        report["splits"][split] += image_count
                        report["total_images"] += image_count
        
        # Save report
        import json
        report_path = self.output_dir / "dataset_report.json"
        with open(report_path, "w") as f:
            json.dump(report, f, indent=2)
        
        # Print summary
        print("\n📊 Synthetic Dataset Report:")
        print(f"Total Images: {report['total_images']}")
        print(f"Train Images: {report['splits']['train']}")
        print(f"Validation Images: {report['splits']['val']}")
        print("\nClass Distribution:")
        for class_name, counts in report["classes"].items():
            total = counts["train"] + counts["val"]
            print(f"  {class_name}: {total} images ({counts['train']} train, {counts['val']} val)")
        
        return report


def main():
    base_dir = Path(".")
    creator = SyntheticDatasetCreator(base_dir)
    
    # Create synthetic dataset
    creator.create_dataset(images_per_class=50)
    
    # Generate report
    creator.generate_report()
    
    print("\n🎯 Next Steps:")
    print("1. This synthetic dataset can be used for testing")
    print("2. Download the real Roboflow dataset for production training")
    print("3. Run training: bash train_roboflow_model.sh")
    print("4. Integrate with your Android app")


if __name__ == "__main__":
    main()
