# Roboflow Deteksi Stunting Dataset Guide

## 🎯 Dataset Information
- **URL**: https://universe.roboflow.com/database-ayu/deteksi-stunting
- **Name**: Deteksi Stunting
- **Classes**: Healthy, MalNutrisi, Stunting
- **Format**: YOLO
- **License**: CC BY 4.0
- **Size**: ~800 images

## 📥 Download Instructions

### Step 1: Access the Dataset
1. Visit: https://universe.roboflow.com/database-ayu/deteksi-stunting
2. Create a free Roboflow account
3. Click "Download Dataset"

### Step 2: Download Configuration
- **Format**: Select "YOLO"
- **Size**: Select "640" (recommended)
- **Download**: Click "Download ZIP"

### Step 3: Place the File
1. Save the downloaded file as `roboflow_stunting.zip`
2. Place it in: `collected_datasets/roboflow_stunting.zip`
3. Run the processing script

## 🚀 Quick Start Commands

```bash
# 1. Download the dataset manually (see instructions above)
# 2. Place roboflow_stunting.zip in collected_datasets/

# 3. Process the dataset
cd /Users/jasminpingol/Downloads/thesis75/nutrisaur11
source ml_env/bin/activate
python ml/download_roboflow_dataset.py

# 4. Train the model
bash train_roboflow_model.sh

# 5. Check results
ls -la runs/malnutrition_cnn_roboflow/
```

## 📊 Expected Dataset Structure

After processing, you'll have:
```
malnutrition_dataset/
├── train/
│   ├── normal/                    # Healthy children
│   ├── moderate_acute_malnutrition/  # MalNutrisi
│   └── stunting/                  # Stunted children
└── val/
    ├── normal/
    ├── moderate_acute_malnutrition/
    └── stunting/
```

## 🎯 Class Mapping

| Roboflow Class | Our Class | WHO Standard |
|----------------|-----------|--------------|
| Healthy | normal | Normal growth |
| MalNutrisi | moderate_acute_malnutrition | MAM (Moderate Acute Malnutrition) |
| Stunting | stunting | Chronic malnutrition |

## 📱 Android Integration

The trained model will be saved as:
- `malnutrition_model_android.pt` - For PyTorch Mobile
- `best_model.pt` - For further training

## 🔧 Troubleshooting

### Dataset Not Found
```bash
# Check if file exists
ls -la collected_datasets/roboflow_stunting.zip

# If not found, download manually and place in correct location
```

### Processing Issues
```bash
# Check extracted contents
ls -la collected_datasets/roboflow_stunting/

# Should contain train/ and valid/ directories with images/ and labels/
```

### Training Issues
```bash
# Check dataset report
cat malnutrition_dataset/dataset_report.json

# Should show non-zero image counts
```

## 📈 Expected Results

With this dataset, you should achieve:
- **Training Accuracy**: 85-95%
- **Validation Accuracy**: 80-90%
- **Model Size**: ~25MB
- **Inference Time**: ~500ms on mobile

## 🎉 Next Steps

1. **Download the dataset** following the instructions above
2. **Process the dataset** using our script
3. **Train the model** with the provided script
4. **Integrate with FavoritesActivity.java** (see integration guide)
5. **Test the model** in your Android app

## 📞 Support

If you encounter issues:
1. Check the dataset report in `malnutrition_dataset/dataset_report.json`
2. Verify the ZIP file is in the correct location
3. Ensure you have a Roboflow account with access to the dataset
4. Check that the dataset format matches YOLO structure

This dataset provides an excellent foundation for malnutrition detection in your Nutrisaur11 app!
