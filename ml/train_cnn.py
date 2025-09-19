#!/usr/bin/env python3

import argparse
import random
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List, Tuple

import matplotlib.pyplot as plt
import numpy as np
import torch
import torch.nn as nn
import torch.optim as optim
from sklearn.metrics import classification_report, confusion_matrix
from torch.optim.lr_scheduler import CosineAnnealingLR
from torch.utils.data import DataLoader, random_split
from torchvision import datasets, models, transforms
from tqdm import tqdm


@dataclass
class TrainConfig:
	data_dir: Path
	output_dir: Path
	epochs: int
	batch_size: int
	learning_rate: float
	img_size: int
	weight_decay: float
	model_name: str
	use_pretrained: bool
	val_split: float
	num_workers: int
	seed: int
	mixed_precision: bool


def set_seed(seed: int) -> None:
	random.seed(seed)
	np.random.seed(seed)
	torch.manual_seed(seed)
	torch.cuda.manual_seed_all(seed)


def get_device() -> torch.device:
	if torch.cuda.is_available():
		return torch.device("cuda")
	if torch.backends.mps.is_available():
		return torch.device("mps")
	return torch.device("cpu")


def build_transforms(img_size: int) -> Tuple[transforms.Compose, transforms.Compose]:
	mean = [0.485, 0.456, 0.406]
	std = [0.229, 0.224, 0.225]

	train_tfms = transforms.Compose([
		transforms.Resize(int(img_size * 1.1)),
		transforms.RandomCrop(img_size),
		transforms.RandomHorizontalFlip(),
		transforms.RandomRotation(10),
		transforms.ColorJitter(brightness=0.1, contrast=0.1, saturation=0.1, hue=0.02),
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


def load_datasets(cfg: TrainConfig) -> Tuple[datasets.ImageFolder, datasets.ImageFolder]:
	train_tfms, val_tfms = build_transforms(cfg.img_size)

	train_dir = cfg.data_dir / "train"
	val_dir = cfg.data_dir / "val"

	if train_dir.exists() and val_dir.exists():
		train_dataset = datasets.ImageFolder(root=str(train_dir), transform=train_tfms)
		val_dataset = datasets.ImageFolder(root=str(val_dir), transform=val_tfms)
		val_dataset.class_to_idx = train_dataset.class_to_idx
	else:
		full_dataset = datasets.ImageFolder(root=str(cfg.data_dir), transform=train_tfms)
		class_to_idx = full_dataset.class_to_idx
		val_size = int(len(full_dataset) * cfg.val_split)
		train_size = len(full_dataset) - val_size
		train_subset, val_subset = random_split(full_dataset, [train_size, val_size],
			generator=torch.Generator().manual_seed(cfg.seed))
		train_dataset = datasets.ImageFolder(root=str(cfg.data_dir), transform=train_tfms)
		train_dataset.samples = [train_dataset.samples[i] for i in train_subset.indices]
		train_dataset.targets = [train_dataset.targets[i] for i in train_subset.indices]
		train_dataset.class_to_idx = class_to_idx

		val_dataset = datasets.ImageFolder(root=str(cfg.data_dir), transform=val_tfms)
		val_dataset.samples = [val_dataset.samples[i] for i in val_subset.indices]
		val_dataset.targets = [val_dataset.targets[i] for i in val_subset.indices]
		val_dataset.class_to_idx = class_to_idx

	return train_dataset, val_dataset


def create_model(model_name: str, num_classes: int, use_pretrained: bool) -> nn.Module:
	model_name = model_name.lower()
	if model_name == "resnet18":
		model = models.resnet18(weights=models.ResNet18_Weights.DEFAULT if use_pretrained else None)
		in_features = model.fc.in_features
		model.fc = nn.Linear(in_features, num_classes)
	elif model_name == "resnet50":
		model = models.resnet50(weights=models.ResNet50_Weights.DEFAULT if use_pretrained else None)
		in_features = model.fc.in_features
		model.fc = nn.Linear(in_features, num_classes)
	elif model_name == "mobilenet_v3_small":
		model = models.mobilenet_v3_small(weights=models.MobileNet_V3_Small_Weights.DEFAULT if use_pretrained else None)
		in_features = model.classifier[-1].in_features
		model.classifier[-1] = nn.Linear(in_features, num_classes)
	else:
		raise ValueError(f"Unsupported model: {model_name}")
	return model


def train_one_epoch(model: nn.Module, dataloader: DataLoader, criterion, optimizer, device: torch.device, use_amp: bool) -> Tuple[float, float]:
	model.train()
	running_loss = 0.0
	correct = 0
	total = 0
	device_type = "cuda" if device.type == "cuda" else ("mps" if device.type == "mps" else "cpu")
	for inputs, labels in tqdm(dataloader, desc="Train", leave=False):
		inputs = inputs.to(device)
		labels = labels.to(device)
		optimizer.zero_grad(set_to_none=True)
		if use_amp and device_type in ("cuda", "mps"):
			with torch.autocast(device_type=device_type, dtype=torch.float16):
				outputs = model(inputs)
				loss = criterion(outputs, labels)
		else:
			outputs = model(inputs)
			loss = criterion(outputs, labels)
		loss.backward()
		optimizer.step()
		running_loss += loss.item() * inputs.size(0)
		_, preds = torch.max(outputs, 1)
		correct += (preds == labels).sum().item()
		total += labels.size(0)

	avg_loss = running_loss / max(total, 1)
	acc = correct / max(total, 1)
	return avg_loss, acc


def evaluate(model: nn.Module, dataloader: DataLoader, criterion, device: torch.device) -> Tuple[float, float, List[int], List[int]]:
	model.eval()
	running_loss = 0.0
	correct = 0
	total = 0
	all_labels: List[int] = []
	all_preds: List[int] = []
	with torch.no_grad():
		for inputs, labels in tqdm(dataloader, desc="Val  ", leave=False):
			inputs = inputs.to(device)
			labels = labels.to(device)
			outputs = model(inputs)
			loss = criterion(outputs, labels)
			running_loss += loss.item() * inputs.size(0)
			_, preds = torch.max(outputs, 1)
			correct += (preds == labels).sum().item()
			total += labels.size(0)
			all_labels.extend(labels.cpu().numpy().tolist())
			all_preds.extend(preds.cpu().numpy().tolist())

	avg_loss = running_loss / max(total, 1)
	acc = correct / max(total, 1)
	return avg_loss, acc, all_labels, all_preds


def plot_confusion_matrix(cm: np.ndarray, class_names: List[str], out_path: Path) -> None:
	fig, ax = plt.subplots(figsize=(8, 6))
	im = ax.imshow(cm, interpolation='nearest', cmap=plt.cm.Blues)
	ax.figure.colorbar(im, ax=ax)
	ax.set(xticks=np.arange(cm.shape[1]), yticks=np.arange(cm.shape[0]), xticklabels=class_names, yticklabels=class_names, ylabel='True label', xlabel='Predicted label', title='Confusion Matrix')
	plt.setp(ax.get_xticklabels(), rotation=45, ha="right", rotation_mode="anchor")
	thresh = cm.max() / 2.0
	for i in range(cm.shape[0]):
		for j in range(cm.shape[1]):
			ax.text(j, i, format(cm[i, j], 'd'), ha="center", va="center", color="white" if cm[i, j] > thresh else "black")
	fig.tight_layout()
	fig.savefig(out_path, bbox_inches="tight", dpi=150)
	plt.close(fig)


def save_class_indices(class_to_idx: Dict[str, int], out_path: Path) -> None:
	with out_path.open('w') as f:
		for cls, idx in class_to_idx.items():
			f.write(f"{idx}\t{cls}\n")


def main() -> None:
	parser = argparse.ArgumentParser(description="Train a CNN on images in folder structure")
	parser.add_argument('--data_dir', type=str, required=True, help='Path to dataset directory. Use subfolders per class or train/val splits.')
	parser.add_argument('--output_dir', type=str, default='runs/cnn', help='Where to save checkpoints and logs')
	parser.add_argument('--epochs', type=int, default=15)
	parser.add_argument('--batch_size', type=int, default=32)
	parser.add_argument('--lr', type=float, default=3e-4)
	parser.add_argument('--img_size', type=int, default=224)
	parser.add_argument('--weight_decay', type=float, default=1e-4)
	parser.add_argument('--model', type=str, default='resnet18', choices=['resnet18', 'resnet50', 'mobilenet_v3_small'])
	parser.add_argument('--no_pretrained', action='store_true', help='Disable pretrained weights')
	parser.add_argument('--val_split', type=float, default=0.2, help='Only used when dataset has no explicit val folder')
	parser.add_argument('--num_workers', type=int, default=4)
	parser.add_argument('--seed', type=int, default=42)
	parser.add_argument('--no_amp', action='store_true', help='Disable mixed precision even if supported')

	args = parser.parse_args()
	cfg = TrainConfig(
		data_dir=Path(args.data_dir),
		output_dir=Path(args.output_dir),
		epochs=args.epochs,
		batch_size=args.batch_size,
		learning_rate=args.lr,
		img_size=args.img_size,
		weight_decay=args.weight_decay,
		model_name=args.model,
		use_pretrained=not args.no_pretrained,
		val_split=args.val_split,
		num_workers=args.num_workers,
		seed=args.seed,
		mixed_precision=not args.no_amp,
	)

	set_seed(cfg.seed)
	device = get_device()
	cfg.output_dir.mkdir(parents=True, exist_ok=True)

	train_dataset, val_dataset = load_datasets(cfg)
	class_names = list(train_dataset.class_to_idx.keys())
	save_class_indices(train_dataset.class_to_idx, cfg.output_dir / 'class_indices.tsv')

	train_loader = DataLoader(train_dataset, batch_size=cfg.batch_size, shuffle=True, num_workers=cfg.num_workers, pin_memory=(device.type != 'cpu'))
	val_loader = DataLoader(val_dataset, batch_size=cfg.batch_size, shuffle=False, num_workers=cfg.num_workers, pin_memory=(device.type != 'cpu'))

	model = create_model(cfg.model_name, num_classes=len(class_names), use_pretrained=cfg.use_pretrained)
	model.to(device)

	criterion = nn.CrossEntropyLoss()
	optimizer = optim.AdamW(model.parameters(), lr=cfg.learning_rate, weight_decay=cfg.weight_decay)
	scheduler = CosineAnnealingLR(optimizer, T_max=max(cfg.epochs - 1, 1))

	best_val_acc = 0.0
	best_path = cfg.output_dir / 'best_model.pt'
	for epoch in range(1, cfg.epochs + 1):
		print(f"\nEpoch {epoch}/{cfg.epochs} - Device: {device}")
		train_loss, train_acc = train_one_epoch(model, train_loader, criterion, optimizer, device, use_amp=cfg.mixed_precision)
		val_loss, val_acc, y_true, y_pred = evaluate(model, val_loader, criterion, device)
		scheduler.step()

		print(f"Train: loss={train_loss:.4f} acc={train_acc:.4f} | Val: loss={val_loss:.4f} acc={val_acc:.4f}")

		ckpt = {
			'epoch': epoch,
			'model_state_dict': model.state_dict(),
			'optimizer_state_dict': optimizer.state_dict(),
			'scheduler_state_dict': scheduler.state_dict(),
			'class_names': class_names,
			'config': cfg.__dict__,
		}
		torch.save(ckpt, cfg.output_dir / f'epoch_{epoch:03d}.pt')

		if val_acc > best_val_acc:
			best_val_acc = val_acc
			torch.save(ckpt, best_path)
			print(f"Saved new best model to {best_path} (acc={best_val_acc:.4f})")

	cm = confusion_matrix(y_true, y_pred, labels=list(range(len(class_names))))
	plot_confusion_matrix(cm, class_names, cfg.output_dir / 'confusion_matrix.png')
	report = classification_report(y_true, y_pred, target_names=class_names)
	with (cfg.output_dir / 'classification_report.txt').open('w') as f:
		f.write(report)
	print(report)
	print(f"Training complete. Best val acc: {best_val_acc:.4f}. Outputs in: {cfg.output_dir}")


if __name__ == '__main__':
	main()


