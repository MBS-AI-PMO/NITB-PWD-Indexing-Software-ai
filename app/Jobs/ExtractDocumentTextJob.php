<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\PdfProcessingService;
use App\Services\SearchablePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ExtractDocumentTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2; // Retry once if fails
    public $timeout = 600; // 10 minutes max per job

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $documentId
    ) {
        // Set queue name
        $this->onQueue('document-extraction');
    }

    /**
     * Execute the job.
     */
    public function handle(PdfProcessingService $pdfService, SearchablePdfService $searchablePdfService): void
    {
        Log::info('🚀 ExtractDocumentTextJob: Job started', [
            'document_id' => $this->documentId,
            'job_id' => $this->job->getJobId(),
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
        ]);

        $document = Document::find($this->documentId);

        if (!$document || !$document->file_path) {
            Log::warning('❌ ExtractDocumentTextJob: Document not found or no file path', [
                'document_id' => $this->documentId,
            ]);
            return;
        }

        try {
            Log::info('📄 ExtractDocumentTextJob: Starting text extraction and searchable PDF conversion', [
                'document_id' => $this->documentId,
                'file_path' => $document->file_path,
            ]);

            $absolutePath = Storage::disk('public')->path($document->file_path);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            $extractedData = null;
            $searchablePdfPath = null;

            if ($extension === 'pdf') {
                // Step 1: Convert scanned PDF to searchable PDF (if not already done during upload)
                // Check if PDF is already searchable by checking if it has text
                $parser = new Parser();
                $pdf = $parser->parseFile($absolutePath);
                $existingText = trim($pdf->getText() ?? '');
                $isAlreadySearchable = strlen($existingText) > 50;

                if (!$isAlreadySearchable && $searchablePdfService->isAvailable()) {
                    Log::info('ExtractDocumentTextJob: Converting PDF to searchable (not done during upload)', [
                        'document_id' => $this->documentId,
                    ]);

                    $searchablePdfPath = $searchablePdfService->convertToSearchable($absolutePath);

                    if ($searchablePdfPath && $searchablePdfPath !== $absolutePath) {
                        // Replace original file with searchable PDF
                        $storagePath = Storage::disk('public')->path('');
                        $relativePath = str_replace($storagePath, '', $searchablePdfPath);
                        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

                        // Delete original file
                        $oldPath = $document->file_path;
                        Storage::disk('public')->delete($oldPath);

                        // Update document with new searchable PDF path
                        $document->file_path = $relativePath;
                        $document->save();

                        Log::info('ExtractDocumentTextJob: Original PDF replaced with searchable PDF', [
                            'document_id' => $this->documentId,
                            'old_path' => $oldPath,
                            'new_path' => $relativePath,
                        ]);

                        // Update absolute path for text extraction
                        $absolutePath = $searchablePdfPath;
                    }
                } else {
                    Log::info('ExtractDocumentTextJob: PDF already searchable, skipping conversion', [
                        'document_id' => $this->documentId,
                        'existing_text_length' => strlen($existingText),
                    ]);
                }

                // Step 2: Extract text from PDF (now searchable)
                $extractedText = $pdfService->process($absolutePath);
                
                if ($extractedText !== '') {
                    $extractedText = $this->cleanExtractedText($extractedText);
                    $extractedData = [
                        'type' => 'pdf',
                        'text' => $extractedText,
                        'is_searchable' => $searchablePdfPath !== null,
                    ];
                }
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                // Image OCR
                $tesseractBinary = $this->findTesseractBinary();
                if ($tesseractBinary) {
                    $cmd = escapeshellarg($tesseractBinary) . ' ' . 
                           escapeshellarg($absolutePath) . ' stdout -l eng --psm 6 2>&1';
                    $output = @shell_exec($cmd);
                    
                    if (is_string($output) && trim($output) !== '' && !str_contains($output, 'Error')) {
                        $output = $this->cleanExtractedText(trim($output));
                        $extractedData = [
                            'type' => 'image',
                            'text' => $output,
                        ];
                    }
                }
            }

            // Update document with extracted data
            if ($extractedData) {
                $document->extracted_data = $extractedData;
                $document->save();

                Log::info('ExtractDocumentTextJob: Text extraction completed successfully', [
                    'document_id' => $this->documentId,
                    'text_length' => strlen($extractedData['text'] ?? ''),
                    'is_searchable_pdf' => $searchablePdfPath !== null,
                ]);
            } else {
                Log::warning('ExtractDocumentTextJob: No text extracted', [
                    'document_id' => $this->documentId,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('ExtractDocumentTextJob: Failed', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            
            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Clean extracted text for safe JSON storage.
     */
    private function cleanExtractedText(string $text): string
    {
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        // Ensure valid UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            try {
                $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
                if (is_string($converted)) {
                    $text = $converted;
                }
            } catch (\Throwable $e) {
                $text = preg_replace('/[^\P{C}\n]+/u', '', $text) ?? '';
            }
        }
        
        return $text;
    }

    /**
     * Find Tesseract binary.
     */
    private function findTesseractBinary(): ?string
    {
        $binaries = ['tesseract', 'tesseract.exe'];
        foreach ($binaries as $binary) {
            $cmd = PHP_OS_FAMILY === 'Windows'
                ? 'where ' . escapeshellarg($binary) . ' 2>&1'
                : 'which ' . escapeshellarg($binary) . ' 2>&1';
            $output = @shell_exec($cmd);
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim($output);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        return null;
    }
}
