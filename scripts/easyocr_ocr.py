#!/usr/bin/env python3
"""
EasyOCR OCR Script for Handwritten Text Detection
This script uses EasyOCR to extract text from images, especially good for handwritten text.
"""

import sys
import json
import os
import io
from contextlib import redirect_stderr, redirect_stdout

# Suppress EasyOCR progress messages by redirecting stderr
class SuppressOutput:
    """Context manager to suppress stdout/stderr."""
    def __enter__(self):
        self._original_stdout = sys.stdout
        self._original_stderr = sys.stderr
        sys.stdout = io.StringIO()
        sys.stderr = io.StringIO()
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        sys.stdout = self._original_stdout
        sys.stderr = self._original_stderr
        return False

try:
    import easyocr
except ImportError as e:
    error_msg = f"EasyOCR not installed. Install with: pip install easyocr. Error: {str(e)}"
    print(json.dumps({"error": error_msg}))
    sys.exit(1)
except Exception as e:
    error_msg = f"Error importing EasyOCR: {str(e)}"
    print(json.dumps({"error": error_msg}))
    sys.exit(1)

def ocr_image(image_path):
    """
    Extract text from image using EasyOCR.
    
    Args:
        image_path: Path to image file
        
    Returns:
        dict: {"text": "extracted text"} or {"error": "error message"}
    """
    try:
        # Check if image file exists
        if not os.path.exists(image_path):
            return {"error": f"Image file not found: {image_path}"}
        
        # Initialize EasyOCR reader (English + Urdu if available)
        # Suppress progress messages during initialization
        # First run will download models, subsequent runs are faster
        try:
            # Suppress stdout/stderr during reader initialization to avoid progress messages
            with SuppressOutput():
                try:
                    reader = easyocr.Reader(['en', 'ur'], gpu=False, verbose=False)  # verbose=False suppresses messages
                except:
                    # Fallback to English only if Urdu model not available
                    reader = easyocr.Reader(['en'], gpu=False, verbose=False)
        except Exception as init_error:
            # If initialization fails, try with English only
            try:
                with SuppressOutput():
                    reader = easyocr.Reader(['en'], gpu=False, verbose=False)
            except Exception as e:
                return {"error": f"Failed to initialize EasyOCR reader: {str(e)}"}
        
        # Read text from image (suppress output during processing)
        # Use multiple passes for better number detection
        all_results = []
        
        with SuppressOutput():
            # First pass: Normal detection with very low thresholds for handwritten text
            results1 = reader.readtext(
                image_path,
                paragraph=False,  # Don't merge into paragraphs, keep individual detections
                width_ths=0.3,    # Very low threshold for width (better for small/handwritten numbers)
                height_ths=0.3,   # Very low threshold for height
                allowlist=None,   # Allow all characters including numbers
            )
            all_results.extend(results1)
            
            # Second pass: Specifically for numbers (0-9) with even lower thresholds
            # This helps catch handwritten numbers that might be missed
            try:
                results2 = reader.readtext(
                    image_path,
                    paragraph=False,
                    width_ths=0.3,    # Very low for small handwritten numbers
                    height_ths=0.3,
                    allowlist='0123456789',  # Only numbers
                )
                # Add numbers that weren't already detected (lower confidence threshold)
                existing_texts = {text for (_, text, _) in all_results}
                for (bbox, text, confidence) in results2:
                    if text not in existing_texts and confidence > 0.1:  # Lowered from 0.2 to 0.1
                        all_results.append((bbox, text, confidence))
            except:
                pass  # If second pass fails, continue with first pass results
            
            # Third pass: Ultra-aggressive for 4-digit numbers (like "1798")
            # Use even lower thresholds and accept very low confidence
            try:
                results3 = reader.readtext(
                    image_path,
                    paragraph=False,
                    width_ths=0.2,    # Ultra-low for tiny handwritten numbers
                    height_ths=0.2,
                    allowlist='0123456789',  # Only numbers
                )
                # Add any numbers not already detected (accept very low confidence)
                existing_texts = {text for (_, text, _) in all_results}
                for (bbox, text, confidence) in results3:
                    # For 4-digit numbers specifically, accept even lower confidence
                    is_4_digit = len(text.strip()) == 4 and text.strip().isdigit()
                    min_conf = 0.05 if is_4_digit else 0.1
                    if text not in existing_texts and confidence > min_conf:
                        all_results.append((bbox, text, confidence))
            except:
                pass  # If third pass fails, continue with previous results
        
        # Extract text from results
        # results is a list of tuples: (bbox, text, confidence)
        extracted_text = []
        seen_texts = set()  # Avoid duplicates
        
        # Sort by confidence (higher first) to prioritize better detections
        all_results.sort(key=lambda x: x[2], reverse=True)
        
        for (bbox, text, confidence) in all_results:
            # Very low confidence threshold for numbers (handwritten numbers can have very low confidence)
            # For pure numbers (especially 4-digit), accept very low confidence (0.05 = 5%)
            # For mixed text with numbers, use 0.1 (10%)
            # For pure text, use 0.2 (20%)
            text_clean = text.strip()
            is_pure_number = text_clean.isdigit()
            is_4_digit = is_pure_number and len(text_clean) == 4
            has_numbers = any(c.isdigit() for c in text_clean)
            
            if is_4_digit:
                min_confidence = 0.05  # Very low for 4-digit numbers like "1798"
            elif is_pure_number:
                min_confidence = 0.08  # Low for other pure numbers
            elif has_numbers:
                min_confidence = 0.1   # Low for text with numbers
            else:
                min_confidence = 0.2   # Higher for pure text
            
            if confidence > min_confidence:
                # Normalize text (remove extra spaces)
                normalized_text = ' '.join(text.split())
                
                # Avoid exact duplicates
                if normalized_text not in seen_texts:
                    extracted_text.append(normalized_text)
                    seen_texts.add(normalized_text)
        
        # Join all text with newlines
        full_text = '\n'.join(extracted_text)
        
        return {"text": full_text}
        
    except Exception as e:
        return {"error": str(e)}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python easyocr_ocr.py <image_path>"}))
        sys.exit(1)
    
    image_path = sys.argv[1]
    result = ocr_image(image_path)
    # Only print JSON result to stdout (no other output)
    print(json.dumps(result))
