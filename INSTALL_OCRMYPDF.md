# Install OCRmyPDF for Searchable PDF Conversion

OCRmyPDF converts scanned PDFs into searchable PDFs by adding an invisible text layer.

## Installation

### Windows (using pip)

1. **Install OCRmyPDF:**
   ```bash
   py -m pip install ocrmypdf
   ```

   Or if using direct Python:
   ```bash
   python -m pip install ocrmypdf
   ```

2. **Verify Installation:**
   ```bash
   py -m ocrmypdf --version
   ```

### Linux/Mac

```bash
pip3 install ocrmypdf
```

Or using system package manager:
```bash
# Ubuntu/Debian
sudo apt-get install ocrmypdf

# macOS (with Homebrew)
brew install ocrmypdf
```

## Dependencies

OCRmyPDF requires:
- **Tesseract OCR** (already installed)
- **Python 3.7+** (already installed)
- **Ghostscript** (for PDF processing)

### Install Ghostscript (if not already installed)

**Windows:**
- Download from: https://www.ghostscript.com/download/gsdnld.html
- Install and add to PATH

**Linux:**
```bash
sudo apt-get install ghostscript
```

**macOS:**
```bash
brew install ghostscript
```

## How It Works

1. **Upload scanned PDF** → System detects it's scanned (no text layer)
2. **Background job runs** → OCRmyPDF processes the PDF
3. **OCR extraction** → Text is extracted using Tesseract
4. **Searchable PDF created** → New PDF with invisible text layer
5. **Original replaced** → Searchable PDF replaces the original
6. **Text stored** → Extracted text saved in database for search

## Features

- ✅ Converts scanned PDFs to searchable PDFs
- ✅ Preserves original image quality
- ✅ Adds invisible text layer (text is selectable/searchable)
- ✅ Supports English and Urdu text
- ✅ Automatic deskewing and image cleanup
- ✅ PDF optimization

## Testing

After installation, test with:
```bash
py -m ocrmypdf input.pdf output.pdf
```

## Troubleshooting

### OCRmyPDF not found
- Make sure Python is in PATH
- Verify installation: `py -m ocrmypdf --version`
- Check logs for detailed error messages

### Ghostscript errors
- Install Ghostscript
- Add Ghostscript to system PATH

### Permission errors
- Ensure write permissions for storage directory
- Check file permissions
