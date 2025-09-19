### CNN Training Quickstart

- **Dataset layout (two options):**
  - **Option A (recommended):**
    - `your_dataset/train/<class_a>/*.jpg`
    - `your_dataset/train/<class_b>/*.jpg`
    - `your_dataset/val/<class_a>/*.jpg`
    - `your_dataset/val/<class_b>/*.jpg`
  - **Option B (auto split):**
    - `your_dataset/<class_a>/*.jpg`
    - `your_dataset/<class_b>/*.jpg`
    - The script will split train/val by `--val_split`.

- **Install requirements (prefer a virtualenv):**
```bash
python3 -m venv .venv && source .venv/bin/activate
pip install -r ml/requirements-ml.txt
```

- **Train (examples):**
```bash
# Minimal
python ml/train_cnn.py --data_dir your_dataset --output_dir runs/cnn

# Larger model, custom settings
python ml/train_cnn.py \
  --data_dir your_dataset \
  --output_dir runs/cnn_resnet50 \
  --model resnet50 \
  --epochs 20 \
  --batch_size 32 \
  --lr 3e-4 \
  --img_size 224
```

- **Notes:**
  - Uses PyTorch with pretrained `resnet18` by default.
  - Saves checkpoints per epoch and the best model at `best_model.pt` inside `--output_dir`.
  - Writes `classification_report.txt` and `confusion_matrix.png` after training.
  - Runs on GPU if available (CUDA or Apple Metal). Otherwise uses CPU.

- **Exporting/Inference:**
  - To use the model later, load the checkpoint and class names:
```python
import torch
from torchvision import models
ckpt = torch.load('runs/cnn/best_model.pt', map_location='cpu')
class_names = ckpt['class_names']
# Recreate model
model = models.resnet18(weights=None)
model.fc = torch.nn.Linear(model.fc.in_features, len(class_names))
model.load_state_dict(ckpt['model_state_dict'])
model.eval()
```


