<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\EasyOCRService;
use App\Support\TesseractLang;

class PdfProcessingService
{
    protected $tesseractPath;
    protected $popplerPath;
    protected $openAiKey;
    protected $easyOCRService;

    public function __construct()
    {
        // Try to find Tesseract binary (Windows default path or system PATH)
        $this->tesseractPath = $this->findTesseractBinary();
        $this->popplerPath = $this->findPopplerBinary();
        $this->openAiKey = config('services.openai.key');
        $this->easyOCRService = new EasyOCRService();
    }

    /**
     * Fast processing - Tesseract only, lower DPI, no EasyOCR (for immediate response).
     *
     * @param string $pdfPath Absolute path to PDF file
     * @return string Extracted text
     */
    public function processFast(string $pdfPath): string
    {
        // 1️⃣ Try normal text extraction first (text-based PDFs)
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = trim($pdf->getText() ?? '');

        if ($text !== '') {
            return $text;
        }

        // 2️⃣ Fast OCR: Tesseract only, lower DPI (200 instead of 300)
        return $this->processWithOCRFast($pdfPath);
    }

    /**
     * Process PDF and extract all text (text-based or scanned).
     *
     * @param string $pdfPath Absolute path to PDF file
     * @return string Extracted text
     */
    public function process(string $pdfPath): string
    {
        // 1️⃣ Try normal text extraction first (text-based PDFs)
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = trim($pdf->getText() ?? '');

        if ($text !== '') {
            Log::info('PDF text extracted using parser (text-based PDF)', [
                'path' => $pdfPath,
                'length' => strlen($text),
            ]);
            return $text;
        }

        // 2️⃣ OCR fallback for scanned PDFs
        Log::info('PDF has no selectable text, attempting OCR for scanned PDF', [
            'path' => $pdfPath,
        ]);

        return $this->processWithOCR($pdfPath);
    }

    /**
     * Fast OCR processing - Tesseract only, lower DPI (200), no EasyOCR.
     * Used for immediate response when queue is not available.
     *
     * @param string $pdfPath Absolute path to PDF file
     * @return string Extracted text from all pages
     */
    private function processWithOCRFast(string $pdfPath): string
    {
        if (!$this->popplerPath || !$this->tesseractPath) {
            return '';
        }

        $finalText = [];
        $tempDir = Storage::disk('public')->path('tmp/pdf_ocr_fast_' . uniqid());

        try {
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $totalPages = $this->getPdfPageCount($pdfPath);
            if ($totalPages === 0) {
                return '';
            }

            // Process first 5 pages only for fast response (or all if less than 5)
            $maxPages = min($totalPages, 5);
            
            for ($pageNum = 1; $pageNum <= $maxPages; $pageNum++) {
                // Lower DPI (200 instead of 300) for faster processing
                $outputPrefix = $tempDir . '/page-' . $pageNum;
                $command = escapeshellarg($this->popplerPath) . ' -f ' . $pageNum . ' -l ' . $pageNum . ' -png -r 200 ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputPrefix) . ' 2>&1';
                @shell_exec($command);

                $imagePath = $this->findGeneratedImagePath($outputPrefix);
                if (!$imagePath || !file_exists($imagePath)) {
                    continue;
                }

                // Tesseract only (fast)
                $pageText = $this->ocrWithTesseractAdvanced($imagePath);
                if (trim($pageText) !== '') {
                    $finalText[] = "=== PAGE $pageNum ===\n" . trim($pageText);
                }

                @unlink($imagePath);
            }
        } catch (\Throwable $e) {
            Log::warning('Fast OCR processing error', ['error' => $e->getMessage()]);
        } finally {
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*');
                foreach ($files as $file) {
                    @unlink($file);
                }
                @rmdir($tempDir);
            }
        }

        return implode("\n\n\n", $finalText);
    }

    /**
     * Process scanned PDF using OCR (Poppler + Tesseract, with optional AI fallback).
     *
     * @param string $pdfPath Absolute path to PDF file
     * @return string Extracted text from all pages
     */
    private function processWithOCR(string $pdfPath): string
    {
        if (!$this->popplerPath || !$this->tesseractPath) {
            Log::warning('Poppler or Tesseract not available for OCR', [
                'pdf_path' => $pdfPath,
                'poppler_available' => !empty($this->popplerPath),
                'tesseract_available' => !empty($this->tesseractPath),
            ]);
            return '';
        }

        $finalText = [];
        $tempDir = Storage::disk('public')->path('tmp/pdf_ocr_' . uniqid());

        try {
            // Create temporary directory
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Get total pages using pdfinfo (more reliable)
            $totalPages = $this->getPdfPageCount($pdfPath);
            
            if ($totalPages === 0) {
                Log::warning('Could not determine PDF page count', ['path' => $pdfPath]);
                return '';
            }

            Log::info('Starting OCR processing for scanned PDF', [
                'path' => $pdfPath,
                'total_pages' => $totalPages,
            ]);

            // Process each page individually
            for ($pageNum = 1; $pageNum <= $totalPages; $pageNum++) {
                Log::info('=== Processing PDF Page ===', [
                    'page_number' => $pageNum,
                    'total_pages' => $totalPages,
                    'pdf_path' => basename($pdfPath),
                ]);

                // Convert single page to PNG image (200 DPI for faster processing, still good quality)
                $outputPrefix = $tempDir . '/page-' . $pageNum;
                $command = escapeshellarg($this->popplerPath) . ' -f ' . $pageNum . ' -l ' . $pageNum . ' -png -r 200 ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputPrefix) . ' 2>&1';
                $output = @shell_exec($command);
                
                Log::info('Page image conversion', [
                    'page' => $pageNum,
                    'poppler_command_output' => substr($output, 0, 200), // First 200 chars
                ]);

                // Find generated image (pdftoppm creates different formats on different OS)
                // Try multiple possible naming patterns
                $possiblePaths = [
                    $outputPrefix . '-1.png',  // Windows/Linux standard
                    $outputPrefix . '-01.png', // Sometimes with leading zero
                    $outputPrefix . '.png',    // Sometimes without dash
                ];
                
                $imagePath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $imagePath = $path;
                        break;
                    }
                }

                // If not found, try glob pattern
                if (!$imagePath) {
                    $globPattern = $outputPrefix . '*.png';
                    $found = glob($globPattern);
                    if (!empty($found)) {
                        $imagePath = $found[0];
                    }
                }

                if (!$imagePath || !file_exists($imagePath)) {
                    Log::warning('Page image not generated', [
                        'page' => $pageNum,
                        'expected_paths' => $possiblePaths,
                        'poppler_output' => $output,
                    ]);
                    continue;
                }

                Log::info('Page image generated, starting OCR', [
                    'page' => $pageNum,
                    'image_path' => basename($imagePath),
                    'image_size_bytes' => file_exists($imagePath) ? filesize($imagePath) : 0,
                ]);

                // OCR Pipeline: Tesseract + EasyOCR for every page (no skipping)
                // 1. Tesseract first (fast, ~5-10 seconds per page)
                // 2. EasyOCR always runs (better for handwritten text, ~60-150 seconds per page)
                // 3. Combine best results from both
                // 4. Skip AI OCR (quota issue + slow)
                
                $pageText = '';
                $ocrMethods = [];
                $tesseractText = '';
                $easyOCRText = '';
                $tesseractLength = 0;
                $easyOCRLength = 0;
                
                // Step 1: Try Tesseract first (FAST - 5-10 seconds)
                Log::info('--- Tesseract OCR (Step 1) ---', ['page' => $pageNum]);
                
                $startTime = microtime(true);
                $tesseractText = $this->ocrWithTesseractAdvanced($imagePath);
                $tesseractLength = strlen(trim($tesseractText));
                $tesseractTime = round(microtime(true) - $startTime, 2);
                
                $tesseractContainsNumbers = preg_match('/\d{3,}/', $tesseractText);
                
                if ($tesseractLength > 0) {
                    $pageText = $tesseractText;
                    $ocrMethods[] = 'Tesseract';
                    
                    Log::info('✅ Tesseract OCR: Text extracted', [
                        'page' => $pageNum,
                        'text_length' => $tesseractLength,
                        'processing_time_seconds' => $tesseractTime,
                        'contains_numbers' => (bool)$tesseractContainsNumbers,
                    ]);
                } else {
                    Log::info('⚠️ Tesseract OCR: Empty result', [
                        'page' => $pageNum,
                        'processing_time_seconds' => $tesseractTime,
                    ]);
                }
                
                // Step 2: EasyOCR temporarily disabled (commented out)
                // TODO: Uncomment when needed for handwritten text detection
                /*
                if ($this->easyOCRService->isAvailable()) {
                    Log::info('🟢 EasyOCR: Starting (running for every page)', [
                        'page' => $pageNum,
                        'image_path' => basename($imagePath),
                    ]);
                    
                    $startTime = microtime(true);
                    try {
                        // Set timeout for EasyOCR (max 120 seconds per page for better accuracy)
                        $easyOCRText = $this->easyOCRService->ocrImage($imagePath, 120);
                        $easyOCRLength = strlen(trim($easyOCRText));
                        $easyOCRTime = round(microtime(true) - $startTime, 2);
                        
                        $easyOCRContainsNumbers = preg_match('/\d{3,}/', $easyOCRText);
                        
                        if ($easyOCRLength > 0) {
                            // Combine both Tesseract and EasyOCR results for better coverage
                            // This is especially important for handwritten numbers like "1798"
                            $combinedText = $tesseractText;
                            
                            // Extract all numbers from EasyOCR that aren't in Tesseract
                            // This helps catch handwritten numbers that Tesseract might miss
                            preg_match_all('/\b\d{3,}\b/', $easyOCRText, $easyOCRNumbers);
                            preg_match_all('/\b\d{3,}\b/', $tesseractText, $tesseractNumbers);
                            
                            $easyOCRNumbersSet = array_unique($easyOCRNumbers[0] ?? []);
                            $tesseractNumbersSet = array_unique($tesseractNumbers[0] ?? []);
                            
                            // Add numbers from EasyOCR that aren't in Tesseract
                            $missingNumbers = array_diff($easyOCRNumbersSet, $tesseractNumbersSet);
                            if (!empty($missingNumbers)) {
                                $combinedText .= "\n" . implode("\n", $missingNumbers);
                                Log::info('🔢 Combined numbers from EasyOCR', [
                                    'page' => $pageNum,
                                    'missing_numbers' => $missingNumbers,
                                    'contains_1798' => in_array('1798', $missingNumbers),
                                ]);
                            }
                            
                            // Also add EasyOCR text if it's significantly longer (might have more text)
                            if ($easyOCRLength > $tesseractLength * 1.2) {
                                // EasyOCR has significantly more text, merge it
                                $combinedText = $easyOCRText . "\n" . $tesseractText;
                                $pageText = $combinedText;
                                $ocrMethods = ['EasyOCR + Tesseract (merged)'];
                            } else {
                                // Use Tesseract as base, add missing numbers from EasyOCR
                                $pageText = $combinedText;
                                $ocrMethods[] = 'EasyOCR (numbers merged)';
                            }
                            
                            Log::info('📊 EasyOCR: Results combined with Tesseract', [
                                'page' => $pageNum,
                                'easyocr_length' => $easyOCRLength,
                                'tesseract_length' => $tesseractLength,
                                'combined_length' => strlen($combinedText),
                                'processing_time_seconds' => $easyOCRTime,
                                'contains_numbers' => (bool)$easyOCRContainsNumbers,
                                'missing_numbers_count' => count($missingNumbers),
                            ]);
                        } else {
                            Log::warning('⚠️ EasyOCR: Returned empty text', [
                                'page' => $pageNum,
                                'processing_time_seconds' => $easyOCRTime,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        $easyOCRTime = round(microtime(true) - $startTime, 2);
                        Log::error('❌ EasyOCR: Failed', [
                            'page' => $pageNum,
                            'error' => $e->getMessage(),
                            'processing_time_seconds' => $easyOCRTime,
                        ]);
                        // Keep Tesseract result if available
                    }
                } else {
                    Log::warning('⚠️ EasyOCR: Not available (skipped)', [
                        'page' => $pageNum,
                    ]);
                }
                */
                
                // Use only Tesseract result when EasyOCR is disabled
                if (empty($pageText) && $tesseractLength > 0) {
                    $pageText = $tesseractText;
                }
                
                // Skip AI OCR (quota issue + slow)
                // AI OCR removed for performance
                
                // Final summary for this page
                $finalTextLength = strlen(trim($pageText));
                $tesseractLength = strlen(trim($tesseractText));
                $easyOCRLength = 0; // EasyOCR disabled
                $contains1798 = stripos($pageText, '1798') !== false;
                
                Log::info('=== PAGE OCR SUMMARY ===', [
                    'page_number' => $pageNum,
                    'ocr_methods_used' => !empty($ocrMethods) ? implode(' + ', $ocrMethods) : 'None',
                    'tesseract_text_length' => $tesseractLength,
                    'easyocr_text_length' => $easyOCRLength,
                    'final_text_length' => $finalTextLength,
                    'final_method' => !empty($ocrMethods) ? end($ocrMethods) : 'None',
                    'contains_1798' => $contains1798,
                    'handwritten_text_detected' => $contains1798 || preg_match('/\d{3,}/', $pageText),
                ]);

                if (trim($pageText) !== '') {
                    $finalText[] = "=== PAGE $pageNum ===\n" . trim($pageText);
                    Log::info('Page text extracted successfully', [
                        'page' => $pageNum,
                        'text_length' => strlen(trim($pageText)),
                    ]);
                } else {
                    Log::warning('No text extracted from page', [
                        'page' => $pageNum,
                    ]);
                }

                // Delete temporary image immediately (memory efficient)
                @unlink($imagePath);

                // Log progress for large PDFs
                if ($pageNum % 5 === 0 || $pageNum === $totalPages) {
                    Log::info('OCR progress', [
                        'pdf_path' => $pdfPath,
                        'processed_pages' => $pageNum,
                        'total_pages' => $totalPages,
                        'pages_with_text' => count($finalText),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error('Error during OCR processing', [
                'pdf_path' => $pdfPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            // Clean up: remove all remaining files and directory
            if (is_dir($tempDir)) {
                $remainingFiles = glob($tempDir . '/*');
                foreach ($remainingFiles as $file) {
                    @unlink($file);
                }
                @rmdir($tempDir);
            }
        }

        $combinedText = implode("\n\n\n", $finalText);
        
        Log::info('OCR processing completed', [
            'pdf_path' => $pdfPath,
            'total_pages' => $totalPages,
            'pages_processed' => count($finalText),
            'extracted_length' => strlen($combinedText),
        ]);

        if (empty($combinedText)) {
            Log::warning('No text extracted from any page of scanned PDF', [
                'pdf_path' => $pdfPath,
                'total_pages' => $totalPages,
            ]);
        }

        return $combinedText;
    }

    /**
     * Get PDF page count using multiple methods for reliability.
     *
     * @param string $pdfPath Absolute path to PDF file
     * @return int Page count, or 0 if unable to determine
     */
    private function getPdfPageCount(string $pdfPath): int
    {
        // Method 1: Try pdfinfo first (most reliable)
        $pdfinfoPath = $this->findPdfInfoBinary();
        if ($pdfinfoPath) {
            $command = escapeshellarg($pdfinfoPath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
            $output = @shell_exec($command);
            
            if (preg_match('/Pages:\s+(\d+)/i', $output, $matches)) {
                $pageCount = (int) $matches[1];
                Log::info('PDF page count detected via pdfinfo', [
                    'path' => $pdfPath,
                    'pages' => $pageCount,
                ]);
                return $pageCount;
            }
        }

        // Method 2: Use Smalot PDF Parser to get page count
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $details = $pdf->getDetails();
            
            // Try different possible keys
            $pageCount = null;
            if (isset($details['Pages'])) {
                $pageCount = (int) $details['Pages'];
            } elseif (isset($details['pages'])) {
                $pageCount = (int) $details['pages'];
            } elseif (isset($details['PageCount'])) {
                $pageCount = (int) $details['PageCount'];
            }
            
            if ($pageCount && $pageCount > 0) {
                Log::info('PDF page count detected via PDF Parser', [
                    'path' => $pdfPath,
                    'pages' => $pageCount,
                ]);
                return $pageCount;
            }
        } catch (\Throwable $e) {
            Log::warning('Could not get page count from PDF parser', [
                'error' => $e->getMessage(),
            ]);
        }

        // Method 3: Try pdftoppm to convert all pages and count images
        if ($this->popplerPath) {
            $tempDir = Storage::disk('public')->path('tmp/page_count_' . uniqid());
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            try {
                $outputPrefix = $tempDir . '/page';
                $command = escapeshellarg($this->popplerPath) . ' -png -r 50 ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputPrefix) . ' 2>&1';
                @shell_exec($command);
                
                $pageImages = glob($tempDir . '/page-*.png');
                $pageCount = count($pageImages);
                
                // Cleanup
                foreach ($pageImages as $file) {
                    @unlink($file);
                }
                @rmdir($tempDir);
                
                if ($pageCount > 0) {
                    Log::info('PDF page count detected via pdftoppm conversion', [
                        'path' => $pdfPath,
                        'pages' => $pageCount,
                    ]);
                    return $pageCount;
                }
            } catch (\Throwable $e) {
                // Cleanup on error
                if (is_dir($tempDir)) {
                    $files = glob($tempDir . '/*');
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                    @rmdir($tempDir);
                }
            }
        }

        Log::warning('Could not determine PDF page count, defaulting to 1', [
            'path' => $pdfPath,
        ]);
        
        // Default to 1 if we can't determine (will process at least first page)
        return 1;
    }

    /**
     * OCR an image using Tesseract with optimized settings for better accuracy.
     *
     * @param string $imagePath Absolute path to image file
     * @return string Extracted text
     */
    private function ocrWithTesseract(string $imagePath): string
    {
        if (!$this->tesseractPath) {
            return '';
        }

        // Tesseract command with optimized settings:
        // -l eng+urd: English + Urdu language support
        // --psm 6: Assume a single uniform block of text (better for documents)
        // --oem 3: Default OCR Engine Mode (LSTM + Legacy)
        $command = escapeshellarg($this->tesseractPath) . ' ' . 
                   escapeshellarg($imagePath) . ' stdout ' .
                   '-l eng ' . // Try English first, add 'urd' if Urdu Tesseract data installed
                   '--psm 6 ' . // Uniform block of text
                   '--oem 3 ' . // LSTM OCR Engine
                   '2>&1';
        
        $output = @shell_exec($command);

        if (is_string($output) && trim($output) !== '') {
            // Filter out error messages
            if (!str_contains($output, 'Error') && 
                !str_contains($output, 'not recognized') &&
                !str_contains($output, 'command not found') &&
                !str_contains($output, 'Warning')) {
                return trim($output);
            }
        }

        return '';
    }

    /**
     * Advanced OCR with multiple PSM modes for handwritten text detection.
     * Tries different Page Segmentation Modes to get best results.
     *
     * @param string $imagePath Absolute path to image file
     * @return string Extracted text (best result from multiple attempts)
     */
    private function ocrWithTesseractAdvanced(string $imagePath): string
    {
        if (!$this->tesseractPath) {
            return '';
        }

        $bestResult = '';
        $bestLength = 0;
        $numberResults = ''; // Store numbers separately

        // PSM modes to try (in order of preference for documents with handwritten text):
        // 6: Uniform block of text (default for documents)
        // 11: Sparse text (handwritten in forms) - BEST for handwritten numbers
        // 12: OSD (Orientation and Script Detection) with sparse text
        // 4: Single column of text
        // 3: Fully automatic (fallback)
        $psmModes = [11, 6, 12, 4, 3]; // Prioritize PSM 11 for handwritten/sparse text

        foreach ($psmModes as $psm) {
            $command = escapeshellarg($this->tesseractPath) . ' ' . 
                       escapeshellarg($imagePath) . ' stdout ' .
                       '-l eng ' .
                       '--psm ' . $psm . ' ' .
                       '--oem 3 ' .
                       '2>&1';
            
            $output = @shell_exec($command);

            if (is_string($output) && trim($output) !== '') {
                // Filter out error messages
                if (!str_contains($output, 'Error') && 
                    !str_contains($output, 'not recognized') &&
                    !str_contains($output, 'command not found') &&
                    !str_contains($output, 'Warning')) {
                    
                    $text = trim($output);
                    $length = strlen($text);
                    
                    // Keep the result with most text (usually more accurate)
                    if ($length > $bestLength) {
                        $bestResult = $text;
                        $bestLength = $length;
                    }
                }
            }
        }

        // Additional pass: Specifically for numbers only (handwritten numbers detection)
        // Use allowlist to only detect digits 0-9
        $numberPSMModes = [11, 6]; // Best modes for numbers
        foreach ($numberPSMModes as $psm) {
            $command = escapeshellarg($this->tesseractPath) . ' ' . 
                       escapeshellarg($imagePath) . ' stdout ' .
                       '-l eng ' .
                       '--psm ' . $psm . ' ' .
                       '--oem 3 ' .
                       '-c tessedit_char_whitelist=0123456789 ' . // Only detect numbers
                       '2>&1';
            
            $output = @shell_exec($command);

            if (is_string($output) && trim($output) !== '') {
                // Filter out error messages
                if (!str_contains($output, 'Error') && 
                    !str_contains($output, 'not recognized') &&
                    !str_contains($output, 'command not found') &&
                    !str_contains($output, 'Warning')) {
                    
                    $numberText = trim($output);
                    if (!empty($numberText)) {
                        $numberResults .= ' ' . $numberText;
                    }
                }
            }
        }

        // Combine: main text + numbers (remove duplicates)
        if (!empty($numberResults)) {
            $numberResults = trim($numberResults);
            // Check if numbers are already in main result
            $numbersArray = preg_split('/\s+/', $numberResults);
            foreach ($numbersArray as $num) {
                $num = trim($num);
                // Prioritize 4-digit numbers (like "1798") - always add if not found
                if (strlen($num) == 4 && ctype_digit($num)) {
                    if (stripos($bestResult, $num) === false) {
                        $bestResult .= "\n" . $num;
                        Log::debug('4-digit number detected and added', [
                            'number' => $num,
                            'image_path' => basename($imagePath),
                        ]);
                    }
                } elseif (strlen($num) >= 3 && stripos($bestResult, $num) === false) {
                    // Add other 3+ digit numbers that aren't already detected
                    $bestResult .= "\n" . $num;
                }
            }
        }
        
        // Final check: Search for "1798" pattern in the combined text
        if (stripos($bestResult, '1798') === false) {
            // Try to find similar patterns (handwritten might be detected as similar numbers)
            $patterns = ['1798', '1 798', '17 98', '179 8', 'l798', 'I798', '179B', '1798'];
            foreach ($patterns as $pattern) {
                if (stripos($bestResult, $pattern) !== false) {
                    Log::debug('Found similar pattern to 1798', [
                        'pattern' => $pattern,
                        'image_path' => basename($imagePath),
                    ]);
                    break;
                }
            }
        }

        // If we got results, log which PSM mode worked best
        if (strlen(trim($bestResult)) > 0) {
            Log::debug('Best OCR result obtained', [
                'image_path' => $imagePath,
                'text_length' => $bestLength,
            ]);
        }

        return $bestResult;
    }

    /**
     * Process image with OpenAI Vision API for better OCR accuracy.
     * Especially useful for Urdu/English mixed text and complex layouts.
     *
     * @param string $imagePath Absolute path to image file
     * @return string Extracted text
     */
    private function processWithAI(string $imagePath): string
    {
        if (!$this->openAiKey) {
            return '';
        }

        try {
            $imageContent = file_get_contents($imagePath);
            if (!$imageContent) {
                Log::warning('Could not read image file for AI OCR', [
                    'image_path' => $imagePath,
                ]);
                return '';
            }

            $imageBase64 = base64_encode($imageContent);
            $imageSize = strlen($imageBase64);

            // Check image size (OpenAI has limits)
            if ($imageSize > 20 * 1024 * 1024) { // 20MB base64 limit
                Log::warning('Image too large for AI OCR, skipping', [
                    'image_path' => $imagePath,
                    'size_bytes' => $imageSize,
                ]);
                return '';
            }

            $response = Http::timeout(60) // Increased timeout for large images
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->openAiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o', // Using gpt-4o for better OCR accuracy
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Extract ALL text from this image/document page. Preserve the exact formatting, line breaks, and structure. Include both Urdu and English text exactly as shown. Extract text from tables, forms, headers, footers, and all sections. Do not summarize or skip any text.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:image/png;base64,' . $imageBase64,
                                        'detail' => 'high' // High detail for better OCR
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 8000, // Increased for longer documents
                    'temperature' => 0.1, // Low temperature for accurate extraction
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['choices'][0]['message']['content'] ?? '';

                Log::info('AI OCR completed successfully', [
                    'image_path' => $imagePath,
                    'text_length' => strlen($text),
                    'tokens_used' => $result['usage']['total_tokens'] ?? 'unknown',
                ]);

                return trim($text);
            } else {
                $errorBody = $response->body();
                Log::warning('AI OCR API request failed', [
                    'image_path' => $imagePath,
                    'status' => $response->status(),
                    'error' => $errorBody,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('AI OCR exception', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return '';
    }

    /**
     * Find Tesseract binary in system PATH or common locations.
     *
     * @return string|null Binary path or null if not found
     */
    private function findTesseractBinary(): ?string
    {
        // Common Windows path
        $commonPaths = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        $binaries = ['tesseract', 'tesseract.exe'];
        foreach ($binaries as $binary) {
            $cmd = PHP_OS_FAMILY === 'Windows' 
                ? 'where ' . escapeshellarg($binary) . ' 2>&1'
                : 'which ' . escapeshellarg($binary) . ' 2>&1';
            
            $output = @shell_exec($cmd);
            
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim(explode("\n", $output)[0]);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Find Poppler pdftoppm binary in system PATH.
     *
     * @return string|null Binary path or null if not found
     */
    private function findPopplerBinary(): ?string
    {
        $binaries = ['pdftoppm', 'pdftoppm.exe'];
        
        foreach ($binaries as $binary) {
            $cmd = PHP_OS_FAMILY === 'Windows' 
                ? 'where ' . escapeshellarg($binary) . ' 2>&1'
                : 'which ' . escapeshellarg($binary) . ' 2>&1';
            
            $output = @shell_exec($cmd);
            
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim(explode("\n", $output)[0]);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Find Poppler pdfinfo binary in system PATH.
     *
     * @return string|null Binary path or null if not found
     */
    private function findPdfInfoBinary(): ?string
    {
        $binaries = ['pdfinfo', 'pdfinfo.exe'];
        
        foreach ($binaries as $binary) {
            $cmd = PHP_OS_FAMILY === 'Windows' 
                ? 'where ' . escapeshellarg($binary) . ' 2>&1'
                : 'which ' . escapeshellarg($binary) . ' 2>&1';
            
            $output = @shell_exec($cmd);
            
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim(explode("\n", $output)[0]);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }
}
