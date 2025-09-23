#!/usr/bin/env python3
"""
Simple CNN Training Script for 3-Class Malnutrition Detection
Works with: normal, moderate_acute_malnutrition, stunting
"""

import argparse
import random
from pathlib import Path
from typing import Dict, List, Tuple

import matplotlib.pyplot as plt
import numpy as np
import torch
import torch.nn as nn
import torch.optim as optim
from sklearn.metrics import classification_report, confusion_matrix
from torch.optim.lr_scheduler import CosineAnnealingLR
from torch.utils.data import DataLoader
from torchvision import datasets, models, transforms
from tqdm import tqdm

def set_seed(seed: int) -> None:
    """Set random seeds for reproducibility"""
    random.seed(seed)
    np.random.seed(seed)
    torch.manual_seed(seed)
    if torch.cuda.is_available():
        torch.cuda.manual_seed_all(seed)

def get_device() -> torch.device:
    """Get the best available device"""
    if torch.cuda.is_available():
        return torch.device("cuda")
    elif torch.backends.mps.is_available():
        return torch.device("mps")
    return torch.device("cpu")

def build_transforms(img_size: int) -> Tuple[transforms.Compose, transforms.Compose]:
    """Build data augmentation transforms"""
    mean = [0.485, 0.456, 0.406]
    std = [0.229, 0.224, 0.225]

    train_tfms = transforms.Compose([
        transforms.Resize(int(img_size * 1.1)),
        transforms.RandomCrop(img_size),
        transforms.RandomHorizontalFlip(p=0.5),
        transforms.RandomRotation(degrees=5),
        transforms.ColorJitter(brightness=0.1, contrast=0.1),
        transforms.ToTensor(),
        transforms.Normalize(mean, std),
    ])

    val_tfms = transforms.Compose([
        transforms.Resize(int(img_size * 1.1)),
        transforms.CenterCrop(img_size),
        transforms.ToTensor(),
        transforms.Normalize(mean, std),
    ])
    
    return train_tfms, val_tfms

def create_model(num_classes: int, use_pretrained: bool = True) -> nn.Module:
    """Create ResNet18 model"""
    model = models.resnet18(weights=models.ResNet18_Weights.DEFAULT if use_pretrained else None)
    in_features = model.fc.in_features
    model.fc = nn.Linear(in_features, num_classes)
    return model

def train_one_epoch(model: nn.Module, dataloader: DataLoader, criterion, optimizer, device: torch.device) -> Tuple[float, float]:
    """Train for one epoch"""
    model.train()
    running_loss = 0.0
    correct = 0
    total = 0
    
    for inputs, labels in tqdm(dataloader, desc="Training", leave=False):
        inputs = inputs.to(device)
        labels = labels.to(device)
        
        optimizer.zero_grad()
        outputs = model(inputs)
        loss = criterion(outputs, labels)
        loss.backward()
        optimizer.step()
        
        running_loss += loss.item() * inputs.size(0)
        _, predicted = torch.max(outputs.data, 1)
        total += labels.size(0)
        correct += (predicted == labels).sum().item()
    
    epoch_loss = running_loss / total
    epoch_acc = correct / total
    
    return epoch_loss, epoch_acc

def evaluate(model: nn.Module, dataloader: DataLoader, criterion, device: torch.device) -> Tuple[float, float, List[int], List[int]]:
    """Evaluate the model"""
    model.eval()
    running_loss = 0.0
    correct = 0
    total = 0
    all_preds = []
    all_labels = []
    
    with torch.no_grad():
        for inputs, labels in tqdm(dataloader, desc="Evaluating", leave=False):
            inputs = inputs.to(device)
            labels = labels.to(device)
            
            outputs = model(inputs)
            loss = criterion(outputs, labels)
            
            running_loss += loss.item() * inputs.size(0)
            _, predicted = torch.max(outputs.data, 1)
            
            total += labels.size(0)
            correct += (predicted == labels).sum().item()
            
            all_preds.extend(predicted.cpu().numpy())
            all_labels.extend(labels.cpu().numpy())
    
    epoch_loss = running_loss / total
    epoch_acc = correct / total
    
    return epoch_loss, epoch_acc, all_labels, all_preds

def plot_confusion_matrix(cm: np.ndarray, class_names: List[str], output_path: Path) -> None:
    """Plot confusion matrix"""
    plt.figure(figsize=(8, 6))
    plt.imshow(cm, interpolation='nearest', cmap='Blues')
    plt.title('Confusion Matrix')
    plt.colorbar()
    
    tick_marks = np.arange(len(class_names))
    plt.xticks(tick_marks, class_names, rotation=45)
    plt.yticks(tick_marks, class_names)
    
    thresh = cm.max() / 2.0
    for i in range(cm.shape[0]):
        for j in range(cm.shape[1]):
            plt.text(j, i, format(cm[i, j], 'd'), ha="center", va="center",
                    color="white" if cm[i, j] > thresh else "black")
    
    plt.ylabel('True label')
    plt.xlabel('Predicted label')
    plt.tight_layout()
    plt.savefig(output_path, dpi=150, bbox_inches='tight')
    plt.close()

def main():
    parser = argparse.ArgumentParser(description="Train CNN for 3-Class Malnutrition Detection")
    parser.add_argument('--data_dir', type=str, default='malnutrition_dataset', help='Dataset directory')
    parser.add_argument('--output_dir', type=str, default='runs/malnutrition_cnn', help='Output directory')
    parser.add_argument('--epochs', type=int, default=10, help='Number of epochs')
    parser.add_argument('--batch_size', type=int, default=16, help='Batch size')
    parser.add_argument('--lr', type=float, default=1e-3, help='Learning rate')
    parser.add_argument('--img_size', type=int, default=224, help='Image size')
    parser.add_argument('--seed', type=int, default=42, help='Random seed')
    
    args = parser.parse_args()
    
    # Set up
    set_seed(args.seed)
    device = get_device()
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    
    print(f"🚀 Starting 3-Class Malnutrition CNN Training")
    print(f"📱 Device: {device}")
    print(f"📊 Dataset: {args.data_dir}")
    
    # Load datasets
    train_tfms, val_tfms = build_transforms(args.img_size)
    
    train_dir = Path(args.data_dir) / "train"
    val_dir = Path(args.data_dir) / "val"
    
    train_dataset = datasets.ImageFolder(root=str(train_dir), transform=train_tfms)
    val_dataset = datasets.ImageFolder(root=str(val_dir), transform=val_tfms)
    
    class_names = list(train_dataset.class_to_idx.keys())
    num_classes = len(class_names)
    
    print(f"📊 Classes: {class_names}")
    print(f"📈 Train samples: {len(train_dataset)}")
    print(f"📈 Val samples: {len(val_dataset)}")
    
    # Create data loaders
    train_loader = DataLoader(train_dataset, batch_size=args.batch_size, shuffle=True, num_workers=2)
    val_loader = DataLoader(val_dataset, batch_size=args.batch_size, shuffle=False, num_workers=2)
    
    # Create model
    model = create_model(num_classes, use_pretrained=True)
    model.to(device)
    
    # Loss function and optimizer
    criterion = nn.CrossEntropyLoss()
    optimizer = optim.Adam(model.parameters(), lr=args.lr)
    scheduler = CosineAnnealingLR(optimizer, T_max=args.epochs)
    
    # Training loop
    best_val_acc = 0.0
    
    print(f"\n🎯 Starting training for {args.epochs} epochs...")
    
    for epoch in range(1, args.epochs + 1):
        print(f"\n📅 Epoch {epoch}/{args.epochs}")
        
        # Train
        train_loss, train_acc = train_one_epoch(model, train_loader, criterion, optimizer, device)
        
        # Validate
        val_loss, val_acc, y_true, y_pred = evaluate(model, val_loader, criterion, device)
        
        # Update scheduler
        scheduler.step()
        
        print(f"📊 Train: Loss={train_loss:.4f}, Acc={train_acc:.4f}")
        print(f"📊 Val:   Loss={val_loss:.4f}, Acc={val_acc:.4f}")
        
        # Save checkpoint
        checkpoint = {
            'epoch': epoch,
            'model_state_dict': model.state_dict(),
            'optimizer_state_dict': optimizer.state_dict(),
            'scheduler_state_dict': scheduler.state_dict(),
            'class_names': class_names,
            'class_to_idx': train_dataset.class_to_idx,
            'val_acc': val_acc
        }
        
        torch.save(checkpoint, output_dir / f'epoch_{epoch:03d}.pt')
        
        # Save best model
        if val_acc > best_val_acc:
            best_val_acc = val_acc
            torch.save(checkpoint, output_dir / 'best_model.pt')
            print(f"💾 New best model saved! (Acc: {best_val_acc:.4f})")
    
    # Final evaluation
    print(f"\n🏁 Training completed!")
    print(f"📊 Best validation accuracy: {best_val_acc:.4f}")
    
    # Generate final reports
    final_cm = confusion_matrix(y_true, y_pred, labels=list(range(num_classes)))
    plot_confusion_matrix(final_cm, class_names, output_dir / 'confusion_matrix.png')
    
    # Classification report
    report = classification_report(y_true, y_pred, target_names=class_names)
    with open(output_dir / 'classification_report.txt', 'w') as f:
        f.write(report)
    
    print(report)
    
    # Create Android-compatible model export
    model.eval()
    android_model_path = output_dir / 'malnutrition_model_android.pt'
    torch.save({
        'model_state_dict': model.state_dict(),
        'class_names': class_names,
        'class_to_idx': train_dataset.class_to_idx,
        'img_size': args.img_size,
        'model_name': 'resnet18',
        'num_classes': num_classes
    }, android_model_path)
    
    print(f"📱 Android model saved to: {android_model_path}")
    print(f"📊 Output directory: {output_dir}")

if __name__ == '__main__':
    main()
