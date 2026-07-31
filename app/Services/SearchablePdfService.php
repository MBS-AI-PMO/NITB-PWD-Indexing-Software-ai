<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SearchablePdfService
{
    protected $pythonPath;
    protected $ocrmyPdfPath;

    public function __construct()
    {
        $this->pythonPath = $this->findPythonBinary();
        $this->ocrmyPdfPath = $this->findOcrmyPdf();
    }

    /**
     * Convert scanned PDF to searchable PDF using OCRmyPDF.
     *
     * @param string $pdfPath Absolute path to scanned PDF
     * @return string|null Path to searchable PDF, or null if conversion failed
     */
    public function convertToSearchable(string $pdfPath): ?string
    {
        if (!$this->isAvailable()) {
            Log::warning('OCRmyPDF not available for PDF conversion', [
                'pdf_path' => basename($pdfPath),
                'python_path' => $this->pythonPath ?: 'Not found',
                'ocrmyPdf_available' => !empty($this->ocrmyPdfPath),
            ]);
            return null;
        }

        try {
            // Check if PDF already has text (is already searchable)
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = trim($pdf->getText() ?? '');

            if ($text !== '' && strlen($text) > 50) {
                // PDF already has text, no need to convert
                Log::info('PDF already searchable, skipping conversion', [
                    'pdf_path' => basename($pdfPath),
                    'text_length' => strlen($text),
                ]);
                return $pdfPath; // Return original path
            }

            // Create output path for searchable PDF
            $outputDir = dirname($pdfPath);
            $originalName = pathinfo($pdfPath, PATHINFO_FILENAME);
            $outputPath = $outputDir . '/' . $originalName . '_searchable.pdf';

            Log::info('Starting PDF to searchable PDF conversion', [
                'input_path' => basename($pdfPath),
                'output_path' => basename($outputPath),
            ]);

            $startTime = microtime(true);

            // Build OCRmyPDF command
            // Options:
            // -l eng+urd: English and Urdu languages
            // --deskew: Correct page skew
            // --clean: Clean up images
            // --remove-background: Remove background from images
            // --optimize: Optimize PDF size
            // --force-ocr: Force OCR even if text layer exists
            $command = escapeshellarg($this->pythonPath) . ' -m ocrmypdf ' .
                       '--language eng+urd ' .
                       '--deskew ' .
                       '--clean ' .
                       '--optimize 1 ' .
                       '--force-ocr ' .
                       escapeshellarg($pdfPath) . ' ' .
                       escapeshellarg($outputPath) . ' 2>&1';

            Log::debug('OCRmyPDF command', ['command' => $command]);

            $output = @shell_exec($command);
            $executionTime = round(microtime(true) - $startTime, 2);

            // Check if output file was created
            if (file_exists($outputPath) && filesize($outputPath) > 0) {
                Log::info('PDF converted to searchable successfully', [
                    'input_path' => basename($pdfPath),
                    'output_path' => basename($outputPath),
                    'input_size' => filesize($pdfPath),
                    'output_size' => filesize($outputPath),
                    'execution_time_seconds' => $executionTime,
                ]);

                // Verify the output PDF is searchable
                $parser = new \Smalot\PdfParser\Parser();
                $outputPdf = $parser->parseFile($outputPath);
                $outputText = trim($outputPdf->getText() ?? '');

                if (strlen($outputText) > 0) {
                    Log::info('Searchable PDF verified - contains text', [
                        'output_path' => basename($outputPath),
                        'text_length' => strlen($outputText),
                    ]);
                    return $outputPath;
                } else {
                    Log::warning('Searchable PDF created but no text found', [
                        'output_path' => basename($outputPath),
                    ]);
                    // Still return it, might have invisible text layer
                    return $outputPath;
                }
            } else {
                Log::error('OCRmyPDF conversion failed - output file not created', [
                    'input_path' => basename($pdfPath),
                    'output_path' => basename($outputPath),
                    'command_output' => substr($output ?? '', 0, 500),
                    'execution_time_seconds' => $executionTime,
                ]);
                return null;
            }

        } catch (\Throwable $e) {
            Log::error('Error converting PDF to searchable', [
                'pdf_path' => basename($pdfPath),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Check if OCRmyPDF is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->pythonPath) && !empty($this->ocrmyPdfPath);
    }

    /**
     * Find Python binary.
     *
     * @return string|null
     */
    private function findPythonBinary(): ?string
    {
        $commonPaths = PHP_OS_FAMILY === 'Windows' 
            ? ['py', 'python', 'python3']  // Windows: py launcher first
            : ['python3', 'python'];

        foreach ($commonPaths as $python) {
            $cmd = PHP_OS_FAMILY === 'Windows' 
                ? 'where ' . escapeshellarg($python) . ' 2>&1'
                : 'which ' . escapeshellarg($python) . ' 2>&1';
            
            $output = @shell_exec($cmd);
            
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim(explode("\n", $output)[0]);
                
                // For Windows 'py' launcher, we need to use it directly
                if ($python === 'py' && PHP_OS_FAMILY === 'Windows') {
                    $versionCmd = 'py --version 2>&1';
                    $versionOutput = @shell_exec($versionCmd);
                    if ($versionOutput && preg_match('/Python\s+3\./', $versionOutput)) {
                        return 'py';
                    }
                } elseif (file_exists($path)) {
                    $versionCmd = escapeshellarg($path) . ' --version 2>&1';
                    $versionOutput = @shell_exec($versionCmd);
                    if ($versionOutput && preg_match('/Python\s+3\./', $versionOutput)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if OCRmyPDF is installed.
     *
     * @return bool
     */
    private function findOcrmyPdf(): bool
    {
        if (!$this->pythonPath) {
            return false;
        }

        // Try to import ocrmypdf module
        $command = escapeshellarg($this->pythonPath) . ' -m ocrmypdf --version 2>&1';
        $output = @shell_exec($command);

        if ($output && (str_contains($output, 'OCRmyPDF') || str_contains($output, 'ocrmypdf'))) {
            Log::info('OCRmyPDF found', [
                'python_path' => $this->pythonPath,
                'version_output' => trim($output),
            ]);
            return true;
        }

        Log::warning('OCRmyPDF not found', [
            'python_path' => $this->pythonPath,
            'command_output' => substr($output ?? '', 0, 200),
        ]);

        return false;
    }
}
