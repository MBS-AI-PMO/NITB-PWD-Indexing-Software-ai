#!/usr/bin/env python3
"""
Quick script to check if EasyOCR is installed and working.
Run: python scripts/check_easyocr.py
"""

import sys
import json

def check_easyocr():
    """Check EasyOCR installation."""
    result = {
        "python_version": sys.version,
        "easyocr_installed": False,
        "error": None
    }
    
    try:
        import easyocr
        result["easyocr_installed"] = True
        result["easyocr_version"] = easyocr.__version__ if hasattr(easyocr, '__version__') else "unknown"
        result["message"] = "EasyOCR is installed and ready to use!"
    except ImportError as e:
        result["error"] = f"EasyOCR not installed: {str(e)}"
        result["message"] = "Install with: pip install easyocr"
    except Exception as e:
        result["error"] = f"Error: {str(e)}"
    
    return result

if __name__ == "__main__":
    result = check_easyocr()
    print(json.dumps(result, indent=2))
