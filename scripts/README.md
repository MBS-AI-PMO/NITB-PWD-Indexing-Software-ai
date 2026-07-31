# EasyOCR Installation Guide

EasyOCR is used for better handwritten text detection in PDF documents.

## Installation Steps

### 1. Install Python 3.7+ (if not already installed)

**Windows:**
- Download from: https://www.python.org/downloads/
- Make sure to check "Add Python to PATH" during installation

**Linux/macOS:**
```bash
# Usually pre-installed, verify with:
python3 --version
```

### 2. Install EasyOCR

**Windows (Laragon):**
```bash
# Option 1: Use py launcher (recommended)
py -m pip install easyocr

# Option 2: Use full Python path
C:\laragon\bin\python\python-3.10\python.exe -m pip install easyocr

# Option 3: Use batch script
scripts\install_easyocr.bat
```

**Linux/macOS:**
```bash
pip install easyocr
# Or
python3 -m pip install easyocr
```

**Verify Installation:**
```bash
# Windows
py scripts/check_easyocr.py

# Linux/macOS
python3 scripts/check_easyocr.py
```

### 3. First Run

On first run, EasyOCR will automatically download language models:
- English model (~50MB)
- Urdu model (optional, ~50MB)

This happens automatically when you first use EasyOCR.

### 4. Verify Installation

Test the Python script:
```bash
python scripts/easyocr_ocr.py path/to/test_image.png
```

## Troubleshooting

### Python not found
- Make sure Python is in your system PATH
- On Windows, try `py` instead of `python`
- Verify with: `python --version` or `python3 --version`

### EasyOCR import error
```bash
pip install --upgrade easyocr
```

### GPU Support (Optional)
If you have NVIDIA GPU with CUDA:
```bash
pip install easyocr[gpu]
```

### Memory Issues
EasyOCR can use significant memory. If you encounter issues:
- Close other applications
- Process smaller batches
- Use CPU mode (default)

## Performance Notes

- **First run**: Slower (downloads models)
- **Subsequent runs**: Faster
- **CPU mode**: Good for most cases
- **GPU mode**: Much faster if available
